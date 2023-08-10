<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Transformers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\AbstractEventTransformer;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;

class SitePropertiesTransformer extends AbstractEventTransformer
{
    /**
     * {@inheritDoc}
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof EventBridgeEventContract;
    }

    /**
     * @param EventBridgeEventContract|EventContract $event
     * @return void
     * @throws Exception
     */
    public function handle(EventContract $event) : void
    {
        /** @var EventBridgeEventContract $event */
        $data = $event->getData();

        ArrayHelper::set($data, 'site.isGdpEligible', GoDaddyPaymentsGateway::isSiteEligible());

        $event->setData($data);
    }
}
