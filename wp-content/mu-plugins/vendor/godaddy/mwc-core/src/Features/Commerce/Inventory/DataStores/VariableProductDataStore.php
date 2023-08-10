<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\DataStores;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\VariableProductDataStore as CatalogVariableProductDataStore;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\DataStores\Traits\CanCrudPlatformInventoryDataTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\SummariesCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;

class VariableProductDataStore extends CatalogVariableProductDataStore
{
    use CanCrudPlatformInventoryDataTrait;

    /**
     * @param ProductsServiceContract $productsService
     * @param LevelsServiceContract $levelsService
     * @param SummariesCachingService $summariesCachingService
     * @param InventoryProviderContract $inventoryProvider
     * @param CommerceContextContract $commerceContext
     */
    public function __construct(
        ProductsServiceContract $productsService,
        LevelsServiceContract $levelsService,
        SummariesCachingService $summariesCachingService,
        InventoryProviderContract $inventoryProvider,
        CommerceContextContract $commerceContext
    ) {
        $this->levelsService = $levelsService;
        $this->summariesCachingService = $summariesCachingService;
        $this->inventoryProvider = $inventoryProvider;
        $this->commerceContext = $commerceContext;

        parent::__construct($productsService);
    }
}
