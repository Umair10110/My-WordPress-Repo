<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\Events\Transformers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Events\Transformers\AbstractOrderEventTransformer;

/**
 * Transformer to add Cost of Goods related data to order events.
 */
class OrderEventTransformer extends AbstractOrderEventTransformer
{
    /**
     * Handles and perhaps modifies the event.
     *
     * @param ModelEvent|EventContract $event the event, perhaps modified by the method
     */
    public function handle(EventContract $event) : void
    {
        $data = $event->getData();
        $orderId = ArrayHelper::get($data, 'resource.id');

        ArrayHelper::set($data, 'resource.productTotalCost', get_post_meta($orderId, '_wc_cog_order_total_cost', true));

        $event->setData($data);
    }
}
