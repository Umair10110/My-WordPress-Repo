<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\DataSources\Adapters;

use BadMethodCallException;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShippingServiceContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Carrier;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateItemContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRateItem;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingService;

class ShippingRateAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> */
    protected $source;

    /**
     * @param array<string, mixed> $source
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * Converts an array of data into a {@see ShippingRateContract} instance.
     *
     * @return ShippingRateContract
     */
    public function convertFromSource() : ShippingRateContract
    {
        $items = $this->convertItemsFromSource();

        return (new ShippingRate())
            ->setId($this->getStringValue($this->source, 'rate_id'))
            ->setRemoteId($this->getStringValue($this->source, 'rate_id'))
            ->setService($this->convertServiceFromSource())
            ->setCarrier($this->convertCarrierFromSource())
            ->addItems(...$items)
            ->setIsTrackable((bool) ArrayHelper::get($this->source, 'trackable', false))
            ->setDeliveryDays((int) ArrayHelper::get($this->source, 'delivery_days', 0))
            ->setTotal($this->calculateTotal($items));
    }

    /**
     * Converts the source data into an array of {@see ShippingRateItemContract} instances.
     *
     * @return ShippingRateItemContract[]
     */
    protected function convertItemsFromSource() : array
    {
        $items = [];

        if ($data = ArrayHelper::get($this->source, 'shipping_amount')) {
            $items[] = $this->convertItemFromSource('shipping_amount', 'Shipping Amount', $data);
        }

        if ($data = ArrayHelper::get($this->source, 'insurance_amount')) {
            $items[] = $this->convertItemFromSource('insurance_amount', 'Insurance Amount', $data);
        }

        if ($data = ArrayHelper::get($this->source, 'confirmation_amount')) {
            $items[] = $this->convertItemFromSource('confirmation_amount', 'Confirmation Amount', $data);
        }

        if ($data = ArrayHelper::get($this->source, 'tax_amount')) {
            $items[] = $this->convertItemFromSource('tax_amount', 'Tax Amount', $data);
        }

        if ($data = ArrayHelper::get($this->source, 'other_amount')) {
            $items[] = $this->convertItemFromSource('other_amount', 'Other Amount', $data);
        }

        return $items;
    }

    /**
     * Converts the given data into an instance of {@see ShippingRateItemContract}.
     *
     * @param string $name
     * @param string $label
     * @param array{currency: string, amount: int|float} $data
     * @return ShippingRateItemContract
     */
    protected function convertItemFromSource(string $name, string $label, array $data) : ShippingRateItemContract
    {
        $price = (new CurrencyAmountAdapter(
            $this->getFloatValue($data, 'amount'),
            $this->getStringValue($data, 'currency')
        ))->convertFromSource();

        return (new ShippingRateItem())
            ->setName($name)
            ->setLabel($label)
            ->setPrice($price);
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

    /**
     * Gets a float value from the given array.
     *
     * Returns an 0.0 if the value cannot be converted to float.
     *
     * @param array<string, mixed> $stored
     * @param string $key
     * @return float
     */
    protected function getFloatValue(array $stored, string $key) : float
    {
        $value = ArrayHelper::get($stored, $key);

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Converts the source data into an instance of {@see ShippingServiceContract}.
     *
     * @return ShippingServiceContract
     */
    protected function convertServiceFromSource() : ShippingServiceContract
    {
        return (new ShippingService())
            ->setName($this->getStringValue($this->source, 'service_code'))
            ->setLabel($this->getStringValue($this->source, 'service_type'));
    }

    /**
     * Converts the source data into an instance of {@see CarrierContract}.
     *
     * @return CarrierContract
     */
    protected function convertCarrierFromSource() : CarrierContract
    {
        return (new Carrier())
            ->setId($this->getStringValue($this->source, 'carrier_id'))
            ->setName($this->getStringValue($this->source, 'carrier_code'))
            ->setLabel($this->getStringValue($this->source, 'carrier_friendly_name'));
    }

    /**
     * Calculates the total cost of the shipping rate using the price of the given shipping rate items.
     *
     * @param ShippingRateItemContract[] $items
     * @return CurrencyAmount
     */
    protected function calculateTotal(array $items) : CurrencyAmount
    {
        $currencyCode = null;
        $total = 0;

        foreach ($items as $item) {
            if (! $currencyCode) {
                $currencyCode = $item->getPrice()->getCurrencyCode();
            }

            $total += $item->getPrice()->getAmount();
        }

        return (new CurrencyAmount())
            ->setAmount($total)
            ->setCurrencyCode($currencyCode ?? WooCommerceRepository::getCurrency());
    }

    /**
     * Converts a {@see ShippingRateContract} object into an array of data.
     *
     * Not implemented.
     *
     * @param ShippingRateContract|null $shippingRate
     * @return array<string, mixed>
     * @throws BadMethodCallException
     */
    public function convertToSource(?ShippingRateContract $shippingRate = null) : array
    {
        throw new BadMethodCallException('Not implemented.');
    }
}
