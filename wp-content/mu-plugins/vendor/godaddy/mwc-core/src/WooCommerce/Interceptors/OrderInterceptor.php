<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Repositories\OrdersRepository;
use WC_Order;

/**
 * A WooCommerce interceptor to hook on order actions and filters.
 */
class OrderInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
                ->setGroup('woocommerce_new_order')
                ->setHandler([$this, 'onWooCommerceNewOrder'])
                ->setArgumentsCount(2)
                ->execute();

        Register::action()
                ->setGroup('woocommerce_update_order')
                ->setHandler([$this, 'onWooCommerceUpdateOrder'])
                ->setArgumentsCount(2)
                ->execute();
    }

    /**
     * Handles the WooCommerce order created event.
     *
     * @internal
     *
     * @param int|null $orderId
     * @param WC_Order|null $wcOrder
     * @throws Exception
     */
    public function onWooCommerceNewOrder($orderId, $wcOrder = null)
    {
        if ($order = $this->getConvertedOrder($orderId, $wcOrder)) {
            $order->save();
        }
    }

    /**
     * Handles the WooCommerce order updated event.
     *
     * @internal
     *
     * @param int|null $orderId
     * @param WC_Order|null $wcOrder
     * @throws Exception
     */
    public function onWooCommerceUpdateOrder($orderId, $wcOrder = null)
    {
        // admin order that wasn't saved yet
        if (ArrayHelper::contains(['new', 'auto-draft', 'draft'], get_post_status($orderId))) {
            return;
        }

        if ($order = $this->getConvertedOrder($orderId, $wcOrder)) {
            $order->update();
        }
    }

    /**
     * Converts a WooCommerce order object into a native order object.
     *
     * @param int|mixed $orderId
     * @param WC_Order|null $wcOrder
     * @throws Exception
     * @return Order|null
     */
    protected function getConvertedOrder($orderId, $wcOrder)
    {
        if (! $wcOrder instanceof WC_Order) {
            $wcOrder = is_numeric($orderId) ? OrdersRepository::get((int) $orderId) : null;
        }

        return $wcOrder instanceof WC_Order ? $this->convertOrder($wcOrder) : null;
    }

    /**
     * Converts a WooCommerce order to a native order object.
     *
     * @param WC_Order $wcOrder
     * @return Order
     * @throws Exception
     */
    protected function convertOrder(WC_Order $wcOrder) : Order
    {
        return (new OrderAdapter($wcOrder))->convertFromSource();
    }
}
