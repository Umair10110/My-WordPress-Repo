<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\ExternalId;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter for converting a {@see Product} external IDs to a corresponding array of {@see ExternalId} DTO objects.
 */
class ExternalIdsAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts a {@see Product} external IDs into an array of {@see ExternalId} DTOs.
     *
     * @param Product|null $product
     * @return ExternalId[]
     */
    public function convertToSource(Product $product = null) : array
    {
        $externalIds = [];

        if (! $product) {
            return $externalIds;
        }

        if ($gtin = $product->getMarketplacesGtin()) {
            $externalIds[] = new ExternalId([
                'type'  => ExternalId::TYPE_GTIN,
                'value' => $gtin,
            ]);
        }

        if ($mpn = $product->getMarketplacesMpn()) {
            $externalIds[] = new ExternalId([
                'type'  => ExternalId::TYPE_MPN,
                'value' => $mpn,
            ]);
        }

        return $externalIds;
    }

    /**
     * @inerhitDoc
     */
    public function convertFromSource()
    {
        // no-op for now
    }
}
