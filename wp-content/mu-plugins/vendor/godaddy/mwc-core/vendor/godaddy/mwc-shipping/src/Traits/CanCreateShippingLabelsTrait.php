<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingLabel;

/**
 * Provides methods to an object to create shipping labels.
 *
 * @see ShippingLabel
 *
 * @since 0.1.0
 */
trait CanCreateShippingLabelsTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> class name of the adapter */
    protected $createLabelShipmentAdapter;

    /**
     * Creates shipping labels for shipments.
     *
     * @since 0.1.0
     *
     * @param ShipmentContract[] $shipments
     * @return mixed[]
     * @throws ShippingExceptionContract
     */
    public function create(array $shipments) : array
    {
        return $this->doAdaptedRequest(new $this->createLabelShipmentAdapter($shipments));
    }
}
