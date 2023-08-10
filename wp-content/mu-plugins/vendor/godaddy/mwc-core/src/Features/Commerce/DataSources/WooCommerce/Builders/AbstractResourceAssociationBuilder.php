<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\DataSources\WooCommerce\Builders;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\DataSources\WooCommerce\Builders\Contracts\ResourceAssociationBuilderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\InsertLocalResourceServiceContract;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;
use InvalidArgumentException;

/**
 * Base class to build associations between remote and local WooCommerce resources.
 */
abstract class AbstractResourceAssociationBuilder implements ResourceAssociationBuilderContract
{
    /** @var AbstractResourceMapRepository resource map repository to look up remote/local IDs */
    protected AbstractResourceMapRepository $resourceMapRepository;

    /** @var InsertLocalResourceServiceContract service to insert resources into the local database */
    protected InsertLocalResourceServiceContract $insertLocalResourceService;

    /** @var string name of the "ID" property on the remote object DTO {@see AbstractDataObject} -- should be the value stored in the resource map table */
    protected string $remoteObjectIdProperty;

    /**
     * Constructor.
     *
     * @param AbstractResourceMapRepository $resourceMapRepository
     * @param InsertLocalResourceServiceContract $insertLocalResourceService
     */
    public function __construct(AbstractResourceMapRepository $resourceMapRepository, InsertLocalResourceServiceContract $insertLocalResourceService)
    {
        $this->resourceMapRepository = $resourceMapRepository;
        $this->insertLocalResourceService = $insertLocalResourceService;
    }

    /**
     * {@inheritDoc}
     */
    public function build(array $resources) : array
    {
        $remoteResourceIds = array_filter(array_column($resources, $this->remoteObjectIdProperty));
        $resourceAssociations = [];

        if (empty($remoteResourceIds)) {
            return $resourceAssociations;
        }

        $localAndRemoteIds = $this->getLocalAndRemoteIds($remoteResourceIds);

        foreach ($resources as $resource) {
            try {
                $localId = $this->getRemoteResourceLocalId($resource, $localAndRemoteIds);
                if (empty($localId)) {
                    // this resource will not be included in the final array
                    continue;
                }

                $resourceAssociations[] = $this->makeResourceAssociation([
                    'localId'        => $localId,
                    'remoteResource' => $resource,
                ]);
            } catch(Exception $exception) {
                // this resource will not be included in the final array
                new SentryException(sprintf('Failed to associate remote resource with local entity: %s', $exception->getMessage()), $exception);
            }
        }

        return $resourceAssociations;
    }

    /**
     * Gets the full mapping database rows (containing both local and remote ID) for the given remote resource IDs.
     *
     * @param string[] $remoteResourceIds
     * @return array<array{commerce_id: string, local_id: int|string}>
     */
    protected function getLocalAndRemoteIds(array $remoteResourceIds) : array
    {
        try {
            return $this->resourceMapRepository->getIdsBy($this->resourceMapRepository::COLUMN_COMMERCE_ID, array_map('strval', $remoteResourceIds));
        } catch(InvalidArgumentException $exception) {
            // should never be thrown since we are passing the column value from the repository constant
            return [];
        }
    }

    /**
     * Makes a new instance of an {@see AbstractResourceAssociation} object from the provided data.
     *
     * @param array<string, AbstractDataObject|int> $data
     * @return AbstractResourceAssociation
     */
    abstract protected function makeResourceAssociation(array $data) : AbstractResourceAssociation;

    /**
     * Gets the local ID of the provided remote resource. If no local ID exists, a new entry is created.
     *
     * In the event that no local ID is returned, the resource will not be included in the (@see static::build()} results.
     *
     * @param AbstractDataObject $resource
     * @param array<mixed> $localAndRemoteIds
     * @return int|null
     */
    protected function getRemoteResourceLocalId(AbstractDataObject $resource, array $localAndRemoteIds) : ?int
    {
        // find the database row the corresponds to the provided `$resource` object
        if ($localId = $this->getRemoteResourceLocalIdFromMappedIds($resource, $localAndRemoteIds)) {
            return $localId;
        }

        if (! $this->shouldInsertLocalResource($resource)) {
            return null;
        }

        // otherwise we have to create a new local resource
        return $this->insertLocalResourceService->insert($resource);
    }

    /**
     * Finds the database row that corresponds to the provided `$resource` object and returns the `local_id` value.
     *
     * `$localAndRemoteIds` is a multi-dimensional array of mapping DB records (@see AbstractResourceMapRepository::getIdsBy()}.
     * Our goal is to find the one result in that array that has a `commerce_id` matching the `$resource` UUID.
     *
     * If we cannot find a matching result then `null` is returned.
     *
     * @param AbstractDataObject $resource
     * @param array<mixed> $localAndRemoteIds
     * @return int|null
     */
    protected function getRemoteResourceLocalIdFromMappedIds(AbstractDataObject $resource, array $localAndRemoteIds) : ?int
    {
        /**
         * Find the database row that corresponds to the provided `$resource` object.
         *
         * `$localAndRemoteIds` is a multi-dimensional array of mapping DB records (@see AbstractResourceMapRepository::getIdsBy()}.
         * Our goal is to find the one result in that array that has a `commerce_id` matching the `$resource` UUID.
         *
         * If we cannot find a matching result, that means there's no local entity for this remote resource in the database,
         * and we'll have to insert a new one.
         */
        $resourceRow = ArrayHelper::where(
            $localAndRemoteIds,
            fn (array $row) => ArrayHelper::get($row, $this->resourceMapRepository::COLUMN_COMMERCE_ID) === $resource->{$this->remoteObjectIdProperty},
            false
        )[0] ?? null;

        // if we already have a matching local ID, return it
        if (is_array($resourceRow) && $localId = TypeHelper::int(ArrayHelper::get($resourceRow, $this->resourceMapRepository::COLUMN_LOCAL_ID), 0)) {
            return $localId;
        }

        return null;
    }

    /**
     * Determines whether the provided remote resource should be inserted into the local database.
     * This method is called after we've already determined that there is no local record. This method may be
     * used to perform actions such as: check if the remote resource has been "soft deleted".
     *
     * @param AbstractDataObject $resource
     * @return bool
     */
    protected function shouldInsertLocalResource(AbstractDataObject $resource) : bool
    {
        return true;
    }
}
