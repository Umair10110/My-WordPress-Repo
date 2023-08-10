<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Inventory;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter for converting a product's inventory settings to a {@see Inventory} DTO.
 */
class InventoryAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts a product's inventory settings into a {@see Inventory} DTO.
     *
     * @param Product|null $product
     * @return Inventory|null
     */
    public function convertToSource(Product $product = null) : ?Inventory
    {
        if (! $product) {
            return null;
        }

        $stockStatus = $product->getStockStatus();
        $hasStockManagementEnabled = $product->hasStockManagementEnabled();
        $backordersAllowed = $product->getBackordersAllowed() === 'yes' || $stockStatus == 'onbackorder';

        /*
         * Commerce does not have an equivalent of "not tracking inventory" but also specifying something is out of stock.
         * In order to simulate this, we map this to:
         *
         * tracking = true
         * quantity = 0
         *
         * Also, tracking needs to be true whenever backorderable is true.
         */
        return new Inventory([
            'externalService' => $hasStockManagementEnabled,
            'backorderable'   => $backordersAllowed,
            'quantity'        => ! $hasStockManagementEnabled && in_array($stockStatus, ['onbackorder', 'outofstock'], true) ? 0 : $product->getCurrentStock(),
            'tracking'        => $hasStockManagementEnabled || in_array($stockStatus, ['onbackorder', 'outofstock'], true) || $backordersAllowed,
        ]);
    }

    /**
     * @inerhitDoc
     */
    public function convertFromSource()
    {
        // no-op for now
    }
}
