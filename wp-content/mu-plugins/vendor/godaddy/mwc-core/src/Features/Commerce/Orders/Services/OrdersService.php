<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Exceptions\MissingOrderRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\Contracts\OrdersProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\CreateOrderInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\Status;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Order as OrderDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrderOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\UpdateOrderInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts\CreateOrderOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Operations\Contracts\UpdateOrderOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Responses\Contracts\CreateOrderResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Responses\CreateOrderResponse;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class OrdersService implements OrdersServiceContract
{
    protected CommerceContextContract $commerceContext;
    protected OrdersProviderContract $ordersProvider;
    protected OrdersMappingServiceContract $ordersMappingService;
    protected OrderItemsMappingService $orderItemsMappingService;
    protected OrderAdapter $orderAdapter;

    public function __construct(
        CommerceContextContract $commerceContext,
        OrdersProviderContract $ordersProvider,
        OrdersMappingServiceContract $ordersMappingService,
        OrderItemsMappingService $orderItemsMappingService,
        OrderAdapter $orderAdapter
    ) {
        $this->commerceContext = $commerceContext;
        $this->ordersProvider = $ordersProvider;
        $this->ordersMappingService = $ordersMappingService;
        $this->orderItemsMappingService = $orderItemsMappingService;
        $this->orderAdapter = $orderAdapter;
    }

    /**
     * {@inheritDoc}
     */
    public function createOrder(CreateOrderOperationContract $operation) : CreateOrderResponseContract
    {
        $orderOutput = $this->createOrderInRemoteService($operation);

        if (! $remoteId = $orderOutput->order->id) {
            throw new MissingOrderRemoteIdException('The returned order data has no ID.');
        }

        $this->mapOrderToRemoteResource($operation->getOrder(), $remoteId);
        $this->mapOrderItemsToRemoteResources($operation->getOrder(), $orderOutput->order);

        return new CreateOrderResponse($remoteId);
    }

    /**
     * Creates an order in the remote Orders service.
     *
     * @throws CommerceExceptionContract
     */
    protected function createOrderInRemoteService(CreateOrderOperationContract $operation) : OrderOutput
    {
        return $this->ordersProvider->orders()->create($this->getCreateOrderInput($operation));
    }

    protected function getCreateOrderInput(CreateOrderOperationContract $operation) : CreateOrderInput
    {
        return new CreateOrderInput([
            'order'   => $this->convertOrderToDataObject($operation->getOrder()),
            'storeId' => $this->commerceContext->getStoreId(),
        ]);
    }

    /**
     * Convert Order to data object.
     *
     * @note The adapter's remoteId is always set, even if empty string, to avoid a case where reusing this method
     *      without a remoteId would unintentionally reuse a previous remoteId set on the adapter.
     *
     * @param Order $order
     * @param non-empty-string|null $remoteId
     *
     * @return OrderDataObject
     */
    protected function convertOrderToDataObject(Order $order, ?string $remoteId = null) : OrderDataObject
    {
        return $this->orderAdapter
            ->setStoreId($this->commerceContext->getStoreId())
            ->setRemoteId($remoteId ?? '')
            ->convertToSource($order);
    }

    /**
     * {@inheritDoc}
     */
    public function updateOrder(UpdateOrderOperationContract $operation) : void
    {
        $orderDataObject = $this->getOrderDataObjectForUpdate($operation);

        if ($this->shouldUpdateOrderStatus($operation, $orderDataObject)) {
            $this->updateOrderStatusInRemoteService($orderDataObject);
        }
    }

    /**
     * Gets {@see OrderDataObject}, with a remote ID, converted from the given operation's {@see Order} model.
     *
     * @param UpdateOrderOperationContract $operation
     * @throws MissingOrderRemoteIdException
     */
    protected function getOrderDataObjectForUpdate(UpdateOrderOperationContract $operation) : OrderDataObject
    {
        return $this->convertOrderToDataObject(
            $operation->getOrder(),
            $this->getRemoteIdForUpdate($operation)
        );
    }

    /**
     * Gets the remote ID for the order model associated with the given operation.
     *
     * @param UpdateOrderOperationContract $operation
     *
     * @return non-empty-string
     * @throws MissingOrderRemoteIdException
     */
    protected function getRemoteIdForUpdate(UpdateOrderOperationContract $operation) : string
    {
        if (! $remoteId = $this->ordersMappingService->getRemoteId($operation->getOrder())) {
            throw new MissingOrderRemoteIdException('The order has no remote ID mapped.');
        }

        return $remoteId;
    }

    /**
     * Checks if status changed to a value that requires an order status update request.
     *
     * @param UpdateOrderOperationContract $operation
     * @param OrderDataObject $orderDataObject
     *
     * @return bool
     */
    protected function shouldUpdateOrderStatus(UpdateOrderOperationContract $operation, OrderDataObject $orderDataObject) : bool
    {
        if ($operation->getOldWooCommerceOrderStatus() === $operation->getNewWooCommerceOrderStatus()) {
            return false;
        }

        if (! in_array($orderDataObject->statuses->status, [Status::Completed, Status::Canceled], true)) {
            return false;
        }

        return true;
    }

    /**
     * Updates the order status in the Order Service.
     *
     * @param OrderDataObject $orderDataObject
     *
     * @return OrderOutput
     * @throws CommerceExceptionContract
     */
    protected function updateOrderStatusInRemoteService(OrderDataObject $orderDataObject) : OrderOutput
    {
        return $this->ordersProvider->orders()->updateOrderStatus($this->getUpdateOrderInput($orderDataObject));
    }

    protected function getUpdateOrderInput(OrderDataObject $orderDataObject) : UpdateOrderInput
    {
        return new UpdateOrderInput([
            'order'   => $orderDataObject,
            'storeId' => $this->commerceContext->getStoreId(),
        ]);
    }

    /**
     * Maps the {@see Order} instance with the given remote ID.
     *
     * @param Order $order
     * @param non-empty-string $remoteId
     * @throws CommerceExceptionContract
     */
    protected function mapOrderToRemoteResource(Order $order, string $remoteId) : void
    {
        $this->ordersMappingService->saveRemoteId($order, $remoteId);
    }

    /**
     * Maps the {@see Order} items with the given remote IDs of the items in the given order data object.
     *
     * @param Order $order
     * @param OrderDataObject $commerceOrder
     */
    protected function mapOrderItemsToRemoteResources(Order $order, OrderDataObject $commerceOrder) : void
    {
        $this->orderItemsMappingService->saveOrderItemsRemoteIds($order, $commerceOrder);
    }
}
