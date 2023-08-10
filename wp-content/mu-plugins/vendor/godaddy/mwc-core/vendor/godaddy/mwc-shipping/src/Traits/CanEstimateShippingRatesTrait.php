<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;

/**
 * Provides methods to an object to estimate shipping rates.
 *
 * @see ShippingRate
 *
 * @since 0.1.0
 */
trait CanEstimateShippingRatesTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> class name of the adapter */
    protected $estimateRatesShipmentAdapter;

    /**
     * Estimates shipping rates for shipments.
     *
     * @since 0.1.0
     *
     * @param ShipmentContract[] $shipments
     * @return mixed[]
     * @throws ShippingExceptionContract
     */
    public function estimate(array $shipments) : array
    {
        return $this->doAdaptedRequest(new $this->estimateRatesShipmentAdapter($shipments));
    }
}
