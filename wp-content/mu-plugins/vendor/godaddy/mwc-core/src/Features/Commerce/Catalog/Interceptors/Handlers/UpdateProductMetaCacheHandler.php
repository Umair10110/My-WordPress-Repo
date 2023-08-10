<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostMetaAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListSummariesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Interceptors\Handlers\AbstractInterceptorHandler;

/**
 * Update product meta cache handler.
 *
 * This handler will update the local product post meta cache with remote product metadata.
 *
 * @TODO in the future try to decouple the inventory logic MWC-12698 {agibson 2023-06-14)
 */
class UpdateProductMetaCacheHandler extends AbstractInterceptorHandler
{
    /** @var ProductsServiceContract */
    protected ProductsServiceContract $productService;

    /** @var array<string, Summary> memoized inventory summaries keyed by productId */
    protected $inventorySummaries = [];

    protected CommerceContextContract $commerceContext;
    protected InventoryProviderContract $inventoryProvider;

    /**
     * Constructor.
     *
     * @param ProductsServiceContract $productService
     * @param InventoryProviderContract $inventoryProvider
     * @param CommerceContextContract $commerceContext
     */
    public function __construct(
        ProductsServiceContract $productService,
        InventoryProviderContract $inventoryProvider,
        CommerceContextContract $commerceContext
    ) {
        $this->productService = $productService;
        $this->inventoryProvider = $inventoryProvider;
        $this->commerceContext = $commerceContext;
    }

    /**
     * Updates local product meta cache with remote product meta.
     *
     * @param array<int, mixed> $args hook arguments
     * @return array<int, array<string, array<mixed>>> cache data
     */
    public function run(...$args) : array
    {
        /** @var array<int, array<string, array<mixed>>> $cache */
        $cache = TypeHelper::array($args[0] ?? [], []);
        $objectIds = TypeHelper::arrayOfIntegers($args[1] ?? []);
        $metaType = TypeHelper::string($args[2] ?? '', '');

        if (! $this->shouldUpdate($metaType, $objectIds)) {
            return $cache;
        }

        try {
            $listProducts = $this->productService->listProducts(ListProductsOperation::seed(['localIds' => $objectIds]));
        } catch (MissingRemoteIdsAfterLocalIdConversionException $exception) {
            // we don't need to report this exception to Sentry
            return $cache;
        } catch (Exception $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);

            return $cache;
        }

        return $this->update($cache, $listProducts->getProducts());
    }

    /**
     * Determines whether the cache should be updated for a given set.
     *
     * @param string $metaType
     * @param int[] $objectIds local product IDs
     * @return bool
     */
    protected function shouldUpdate(string $metaType, array $objectIds) : bool
    {
        return 'post' === $metaType && ! empty($objectIds);
    }

    /**
     * Updates the cache metadata related to products.
     *
     * @param array<int, array<string, array<mixed>>> $cache
     * @param ProductAssociation[] $productAssociations
     * @return array<int, array<string, array<mixed>>> the updated cache
     */
    protected function update(array $cache, array $productAssociations) : array
    {
        $this->fetchInventorySummaries($productAssociations);

        foreach ($productAssociations as $productAssociation) {
            // merges the local product cached metadata with remote metadata from catalog
            $localMeta = $cache[$productAssociation->localId] ?? [];
            $inventorySummary = $this->getInventorySummaryByProductId($productAssociation->remoteResource->productId);

            $cache[$productAssociation->localId] = array_merge(
                $localMeta,
                ProductPostMetaAdapter::getNewInstance($productAssociation->remoteResource)
                    ->setLocalMeta($localMeta)
                    ->setInventorySummary($inventorySummary)
                    ->convertFromSourceToFormattedArray()
            );
        }

        return $cache;
    }

    /**
     * Fetches the inventory summaries for each of the applicable product IDs, then keys them by product ID.
     *
     * @param ProductAssociation[] $productAssociations
     */
    protected function fetchInventorySummaries(array $productAssociations) : void
    {
        if (! $productIds = $this->getProductIdsForInventorySummary($productAssociations)) {
            return;
        }

        try {
            // This could be cached if we had a service to cache inventory summary by productId
            $summaries = $this->inventoryProvider->summaries()->list(new ListSummariesInput([
                'storeId'    => $this->commerceContext->getStoreId(),
                'productIds' => $productIds,
            ]));

            $this->inventorySummaries = [];

            foreach ($summaries as $summary) {
                $this->inventorySummaries[$summary->productId] = $summary;
            }
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance('Could not read inventory summaries', $exception);
        }
    }

    /**
     * Get productIds that need to set stock quantity from inventory summary.
     *
     * @param ProductAssociation[] $productAssociations
     *
     * @return string[]
     */
    protected function getProductIdsForInventorySummary(array $productAssociations) : array
    {
        return array_values(
            array_filter(
                array_map(
                    static function (ProductAssociation $assoc) : ?string {
                        $inventory = $assoc->remoteResource->inventory;

                        if ($inventory && $inventory->externalService) {
                            return $assoc->remoteResource->productId;
                        }

                        return null;
                    },
                    $productAssociations
                )
            )
        );
    }

    /**
     * Gets the inventory summary (if available) that corresponds to the supplied product ID.
     *
     * @param string|null $productId
     * @return Summary|null
     */
    protected function getInventorySummaryByProductId(?string $productId) : ?Summary
    {
        /** @var ?Summary $summary */
        $summary = ArrayHelper::get($this->inventorySummaries, (string) $productId);

        return $summary;
    }
}
