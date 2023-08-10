<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingLevelRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataSources\Adapters\LevelAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\CreateOrUpdateLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\DeleteLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\DeleteLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\CreateOrUpdateLevelResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ReadLevelResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class LevelsService implements LevelsServiceContract
{
    /** @var CommerceContextContract */
    protected CommerceContextContract $commerceContext;

    /** @var InventoryProviderContract the inventory provider instance */
    protected InventoryProviderContract $provider;

    protected LevelsCachingService $levelsCachingService;

    /** @var LevelMappingServiceContract */
    protected LevelMappingServiceContract $levelMappingService;

    /** @var LocationMappingServiceContract */
    protected LocationMappingServiceContract $locationMappingService;

    /** @var ProductsMappingServiceContract */
    protected ProductsMappingServiceContract $productMappingService;

    /**
     * The Levels Service constructor.
     */
    public function __construct(
        CommerceContextContract $commerceContext,
        InventoryProviderContract $provider,
        LevelsCachingService $levelsCachingService,
        LevelMappingServiceContract $levelMappingService,
        LocationMappingServiceContract $locationMappingService,
        ProductsMappingServiceContract $productMappingService
    ) {
        $this->commerceContext = $commerceContext;
        $this->provider = $provider;
        $this->levelsCachingService = $levelsCachingService;
        $this->levelMappingService = $levelMappingService;
        $this->locationMappingService = $locationMappingService;
        $this->productMappingService = $productMappingService;
    }

    /**
     * {@inheritDoc}
     *
     * @throws CommerceExceptionContract|CachingStrategyException
     */
    public function createOrUpdateLevel(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract
    {
        $product = $operation->getProduct();

        $existingRemoteId = $this->levelMappingService->getRemoteId($product);

        // create or update in the inventory service
        $level = $this->provider->levels()->createOrUpdate(
            $this->getUpsertLevelInput($product, $existingRemoteId)
        );

        if (! $level->inventoryLevelId) {
            throw MissingLevelRemoteIdException::withDefaultMessage();
        }

        // save the remote ID if not done already
        if (! $existingRemoteId) {
            $this->levelMappingService->saveRemoteId($product, $level->inventoryLevelId);
        }

        $this->levelsCachingService->set($level);

        return new CreateOrUpdateLevelResponse($level);
    }

    /**
     * Gets the upsert level input.
     *
     * @param Product $product
     * @param string|null $existingRemoteId
     *
     * @return UpsertLevelInput
     * @throws CommerceExceptionContract
     */
    protected function getUpsertLevelInput(Product $product, ?string $existingRemoteId) : UpsertLevelInput
    {
        return new UpsertLevelInput([
            'storeId' => $this->commerceContext->getStoreId(),
            'level'   => $this->buildLevelData($product, $existingRemoteId),
        ]);
    }

    /**
     * Builds a level from the given product.
     *
     * @param Product     $product
     * @param string|null $remoteId
     *
     * @return Level
     * @throws MissingProductRemoteIdException
     */
    protected function buildLevelData(Product $product, ?string $remoteId) : Level
    {
        /** @var Level $level */
        $level = LevelAdapter::getNewInstance()->convertToSource($product);
        $locationId = $this->locationMappingService->getRemoteId() ?? null;
        $productId = $this->productMappingService->getRemoteId($product);

        if (! $productId) {
            throw new MissingProductRemoteIdException('The level product has no remote UUID saved');
        }

        $level->inventoryLevelId = $remoteId;
        $level->inventoryLocationId = $locationId;
        $level->productId = $productId;

        return $level;
    }

    /**
     * {@inheritDoc}
     *
     * @return ReadLevelResponseContract
     * @throws CommerceExceptionContract
     */
    public function readLevel(ReadLevelOperationContract $operation) : ReadLevelResponseContract
    {
        $product = $operation->getProduct();

        if (! $existingRemoteId = $this->levelMappingService->getRemoteId($product)) {
            throw new MissingProductRemoteIdException('Could not get the remote ID for given product');
        }

        $level = $this->levelsCachingService->remember(
            $existingRemoteId,
            fn () => $this->provider->levels()->read(ReadLevelInput::getNewInstance([
                'storeId' => $this->commerceContext->getStoreId(),
                'levelId' => $existingRemoteId,
            ]))
        );

        return new ReadLevelResponse($level);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteLevel(DeleteLevelOperationContract $operation) : DeleteLevelResponseContract
    {
        // @TODO: Implement this test on MWC-10823 {acastro1 2023.03.06}
        /* @phpstan-ignore-next-line */
        return null;
    }
}
