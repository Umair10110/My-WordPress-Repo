<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Http\Request;
use GoDaddy\WordPress\MWC\Shipping\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ListCarriersOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Carrier;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;

class ListCarriersRequestAdapter extends AbstractGatewayRequestAdapter
{
    /** @var ListCarriersOperationContract */
    protected $operation;

    public function __construct(ListCarriersOperationContract $operation)
    {
        $this->operation = $operation;
    }

    /** {@inheritDoc} */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setPath('/shipping/proxy/shipengine/v1/carriers')
            ->setMethod('get')
            ->setQuery([
                'externalAccountId' => $this->operation->getAccount()->getId(),
            ]);
    }

    /** {@inheritDoc} */
    protected function convertResponse(ResponseContract $response)
    {
        $this->operation->setCarriers(...$this->getCarriers($response));

        return $this->operation;
    }

    /**
     * Creates a list of {@see CarrierContract} instances using data from the response.
     *
     * @param ResponseContract $response
     * @return CarrierContract[]
     */
    protected function getCarriers(ResponseContract $response) : array
    {
        $carriers = [];

        foreach (ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'carriers')) as $data) {
            if (ArrayHelper::accessible($data)) {
                $carriers[] = $this->getCarrier(ArrayHelper::wrap($data));
            }
        }

        return $carriers;
    }

    /**
     * Creates a {@see CarrierContract} instance using the given data.
     *
     * @param array<string, mixed> $data
     * @return CarrierContract
     */
    protected function getCarrier(array $data) : CarrierContract
    {
        return (new Carrier())
            ->setId($this->getStringValue($data, 'carrier_id'))
            ->setName($this->getStringValue($data, 'carrier_code'))
            ->setLabel($this->getStringValue($data, 'friendly_name'));
    }

    /**
     * Gets a string value from the given array.
     *
     * Returns an empty string if the value cannot be converted to string.
     *
     * @param array<string, mixed> $stored
     * @param string $key
     * @return string
     */
    protected function getStringValue(array $stored, string $key) : string
    {
        return (string) StringHelper::ensureScalar(ArrayHelper::get($stored, $key));
    }
}
