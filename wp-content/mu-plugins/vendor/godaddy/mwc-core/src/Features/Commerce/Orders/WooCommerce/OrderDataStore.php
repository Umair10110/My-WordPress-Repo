<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\CanGenerateIdContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Exceptions\MissingOrderRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\OrdersIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\WooOrderCartIdProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrderReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts\UpdateOrderOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\CreateOrderOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\UpdateOrderOperation;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Order;
use WC_Order_Data_Store_CPT;

class OrderDataStore extends WC_Order_Data_Store_CPT
{
    protected OrdersServiceContract $ordersService;

    protected OrderReservationsServiceContract $orderReservationsService;

    protected WooOrderCartIdProvider $wooOrderCartIdProvider;

    protected CanGenerateIdContract $idProvider;

    public function __construct(
        OrdersServiceContract $ordersService,
        OrderReservationsServiceContract $orderReservationsService,
        WooOrderCartIdProvider $wooOrderCartIdProvider,
        CanGenerateIdContract $idProvider
    ) {
        $this->ordersService = $ordersService;
        $this->orderReservationsService = $orderReservationsService;
        $this->wooOrderCartIdProvider = $wooOrderCartIdProvider;
        $this->idProvider = $idProvider;
    }

    /**
     * Creates an order in the Commerce platform and WooCommerce's database.
     *
     * @param mixed $order
     */
    public function create(&$order) : void
    {
        if ($this->shouldCreateWooCommerceOrderInPlatform($order)) {
            $this->createWooCommerceOrderInPlatform($order);
        } else {
            parent::create($order);
        }
    }

    /**
     * Determines whether we should use the given input to create a WooCommerce order in the Commerce platform.
     *
     * @param mixed $wooOrder
     * @return bool
     * @phpstan-assert-if-true WC_Order $wooOrder
     */
    protected function shouldCreateWooCommerceOrderInPlatform($wooOrder) : bool
    {
        return $wooOrder instanceof WC_Order && OrdersIntegration::hasCommerceCapability(Commerce::CAPABILITY_WRITE);
    }

    /**
     * Determines whether we should use the given input to update a WooCommerce order in the Commerce platform.
     *
     * @param mixed $wooOrder
     * @return bool
     * @phpstan-assert-if-true WC_Order $wooOrder
     */
    protected function shouldUpdateWooCommerceOrderInPlatform($wooOrder) : bool
    {
        return $this->shouldCreateWooCommerceOrderInPlatform($wooOrder);
    }

    /**
     * Creates an order in the Commerce platform and WooCommerce's database.
     */
    protected function createWooCommerceOrderInPlatform(WC_Order &$wooOrder) : void
    {
        $wooOrder = $this->prepareOrderForPlatform($wooOrder);

        $order = $this->convertOrderForPlatform($wooOrder);

        if ($order) {
            $this->tryToCreateOrderInPlatform($order);
        }

        $this->callParentCreate($wooOrder);

        if ($order) {
            // TODO: set local IDs of all order items as well -- {wvega 2022-04-28}
            $this->processCreatedOrder($order->setId($wooOrder->get_id()));
        }
    }

    /**
     * Tries to create an order in the Commerce platform.
     */
    protected function tryToCreateOrderInPlatform(Order $order) : void
    {
        try {
            $this->createOrderInPlatform($order);
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance(
                'An error occurred trying to create a remote record for an order.',
                $exception
            );
        }
    }

    /**
     * Creates an order in the Commerce platform.
     *
     * @throws CommerceExceptionContract
     */
    protected function createOrderInPlatform(Order $order) : void
    {
        $this->orderReservationsService->createOrUpdateReservations($order);
        $this->ordersService->createOrder(CreateOrderOperation::fromOrder($order));
    }

    /**
     * Prepares a WooCommerce order to be used as the source for a Commerce order.
     */
    protected function prepareOrderForPlatform(WC_Order $wooOrder) : WC_Order
    {
        return $this->generateCartIdIfNotSet($wooOrder);
    }

    /**
     * Converts a WooCommerce order into an instance of the {@see Order} model.
     */
    protected function convertOrderForPlatform(WC_Order $wooOrder) : ?Order
    {
        try {
            return OrderAdapter::getNewInstance($wooOrder)->convertFromSource();
        } catch (AdapterException $exception) {
            SentryException::getNewInstance('An error occurred trying to convert the WooCommerce order into an Order instance.', $exception);
        }

        return null;
    }

    /**
     * Generates a cartId for the given WooCommerce order if one is not already set.
     *
     * @param WC_Order $wooOrder
     * @return WC_Order
     */
    protected function generateCartIdIfNotSet(WC_Order $wooOrder) : WC_Order
    {
        if (! $this->wooOrderCartIdProvider->getCartId($wooOrder)) {
            $this->wooOrderCartIdProvider->setCartId($wooOrder, $this->idProvider->generateId());
        }

        return $wooOrder;
    }

    /**
     * Obtains the old (previous) order status from the specified WC_Order.
     *
     * @param WC_Order $wooOrder
     *
     * @return string
     */
    protected function getOldWooCommerceOrderStatusForOrder(WC_Order $wooOrder) : string
    {
        return TypeHelper::string(ArrayHelper::get($wooOrder->get_data(), 'status', $wooOrder->get_status()), '');
    }

    /**
     * Runs order operations that must be executed after the local order is created.
     *
     * This method assumes that the given order has local IDs for all items that support a local ID.
     */
    protected function processCreatedOrder(Order $order) : void
    {
        // TODO: persist order and order items remote IDs -- {wvega 2022-04-28}
    }

    /**
     * Updates an order in the Commerce platform and WooCommerce's database.
     *
     * @param mixed $order
     */
    public function update(&$order) : void
    {
        if ($this->shouldUpdateWooCommerceOrderInPlatform($order)) {
            $this->updateWooCommerceOrderInPlatform($order);
        }

        parent::update($order);
    }

    /**
     * Attempts to update the giving WooCommerce order in the Commerce platform.
     *
     * @param WC_Order $wooOrder
     */
    protected function updateWooCommerceOrderInPlatform(WC_Order &$wooOrder) : void
    {
        $wooOrder = $this->prepareOrderForPlatform($wooOrder);

        if ($order = $this->convertOrderForPlatform($wooOrder)) {
            $this->tryToUpdateOrderInPlatform($this->makeUpdateOrderOperation($order, $wooOrder));
        }
    }

    /**
     * Updates the order on the MWCS platform.
     *
     * @param UpdateOrderOperationContract $updateOrderOperation The operation to use when updating the order.
     *
     * @return void
     */
    protected function tryToUpdateOrderInPlatform(UpdateOrderOperationContract $updateOrderOperation) : void
    {
        try {
            $this->ordersService->updateOrder($updateOrderOperation);
        } catch (MissingOrderRemoteIdException $exception) {
            // No-op for now. If we don't catch this exception, the error will be reported to sentry for a known unsupported case: every update of every order that doesn't have a mapped remote (commerce) ID.
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance(
                'An error occurred trying to update a remote record for an order.',
                $exception
            );
        }
    }

    /**
     * Builds an instance of {@see UpdateOrderOperation} with given data.
     *
     * @param Order $order
     * @param WC_Order $wooOrder
     * @return UpdateOrderOperationContract
     */
    protected function makeUpdateOrderOperation(Order $order, WC_Order $wooOrder) : UpdateOrderOperationContract
    {
        return (new UpdateOrderOperation())
            ->setOrder($order)
            ->setNewWooCommerceOrderStatus($wooOrder->get_status())
            ->setOldWooCommerceOrderStatus($this->getOldWooCommerceOrderStatusForOrder($wooOrder));
    }

    /**
     * Isolate the parent::create() call to be able to mock the expected sequence it is called.
     *
     * @param WC_Order $wooOrder
     */
    protected function callParentCreate(WC_Order &$wooOrder) : void
    {
        parent::create($wooOrder);
    }
}
