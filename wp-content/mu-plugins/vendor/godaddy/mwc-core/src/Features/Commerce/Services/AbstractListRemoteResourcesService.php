<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\DataSources\WooCommerce\Builders\Contracts\ResourceAssociationBuilderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Helpers\Contracts\ListRemoteResourcesCachingHelperContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\ListRemoteResourcesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;
use InvalidArgumentException;

/**
 * Abstract class for listing remote resources services.
 */
abstract class AbstractListRemoteResourcesService implements ListRemoteResourcesServiceContract
{
    /** @var AbstractResourceMapRepository resource map repository to look up remote/local IDs */
    protected AbstractResourceMapRepository $resourceMapRepository;

    /** @var ListRemoteResourcesCachingHelperContract caching helper to identify caching capabilities and whether we need to execute the query */
    protected ListRemoteResourcesCachingHelperContract $listRemoteResourcesCachingHelper;

    /** @var CachingServiceContract caching service to get items from cache and set them */
    protected CachingServiceContract $cachingService;

    /** @var ResourceAssociationBuilderContract builds associations between remote resources and local IDs */
    protected ResourceAssociationBuilderContract $resourceAssociationBuilder;

    public function __construct(
        AbstractResourceMapRepository $resourceMapRepository,
        ListRemoteResourcesCachingHelperContract $listRemoteResourcesCachingHelper,
        CachingServiceContract $cachingService,
        ResourceAssociationBuilderContract $resourceAssociationBuilder
    ) {
        $this->resourceMapRepository = $resourceMapRepository;
        $this->listRemoteResourcesCachingHelper = $listRemoteResourcesCachingHelper;
        $this->cachingService = $cachingService;
        $this->resourceAssociationBuilder = $resourceAssociationBuilder;
    }

    /**
     * Executes a list query, and caches the results.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return AbstractResourceAssociation[]
     * @throws CommerceExceptionContract|CachingStrategyException|BaseException
     */
    public function list(ListRemoteResourcesOperationContract $operation) : array
    {
        $resources = [];

        $this->convertLocalEntitiesToRemote($operation);

        if ($this->listRemoteResourcesCachingHelper->canCacheOperation($operation)) {
            $resources = $this->listRemoteResourcesCachingHelper->getCachedResourcesFromOperation($operation);
        }

        if (! $this->listRemoteResourcesCachingHelper->isOperationFullyCached($operation, $resources)) {
            $resources = $this->executeListQuery($operation);

            $this->cachingService->setMany($resources);
        }

        return $this->resourceAssociationBuilder->build($resources);
    }

    /**
     * Executes the list query via the gateway.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return AbstractDataObject[]
     */
    abstract protected function executeListQuery(ListRemoteResourcesOperationContract $operation) : array;

    /**
     * Converts local entities (e.g. IDs) to their remote equivalents, as necessary to execute the query.
     *
     * By default, this just calls {@see AbstractListRemoteResourcesService::convertLocalIdsToRemoteIds().
     * Child implementations may override this if they also want to convert other entities as well.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return void
     * @throws CommerceException|BaseException
     */
    protected function convertLocalEntitiesToRemote(ListRemoteResourcesOperationContract $operation) : void
    {
        $this->convertLocalIdsToRemoteIds($operation);
    }

    /**
     * Converts local resource IDs to their remote equivalents.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return void
     * @throws CommerceException|BaseException
     */
    protected function convertLocalIdsToRemoteIds(ListRemoteResourcesOperationContract $operation) : void
    {
        if ($localIds = $operation->getLocalIds()) {
            try {
                // @TODO replace with repository `getIdsByLocal()` method once it becomes available (no story yet) {agibson 2023-05-19}
                $localAndRemoteIds = $this->resourceMapRepository->getIdsBy($this->resourceMapRepository::COLUMN_LOCAL_ID, $localIds);
            } catch(InvalidArgumentException $e) {
                $localAndRemoteIds = [];
            }

            $remoteIds = TypeHelper::arrayOfStrings(ArrayHelper::combine(
                TypeHelper::array($operation->getIds(), []),
                // @TODO replace with `ResourceMapCollection::getRemoteIds()` when it becomes available (no story yet) {agibson 2023-05-19}
                array_column($localAndRemoteIds, $this->resourceMapRepository::COLUMN_COMMERCE_ID)
            ), false);

            if (empty($remoteIds)) {
                throw MissingRemoteIdsAfterLocalIdConversionException::withDefaultMessage();
            }

            $operation->setIds(array_unique($remoteIds));
        }
    }
}
