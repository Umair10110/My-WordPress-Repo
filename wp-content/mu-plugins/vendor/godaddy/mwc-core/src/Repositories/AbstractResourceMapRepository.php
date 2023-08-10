<?php

namespace GoDaddy\WordPress\MWC\Core\Repositories;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\CommerceContext;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMap;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CommerceContextRepository;
use GoDaddy\WordPress\MWC\Core\Repositories\Strategies\Contracts\RemoteIdStrategyContract;
use GoDaddy\WordPress\MWC\Core\Repositories\Strategies\PassThruRemoteIdMutationStrategy;
use InvalidArgumentException;

/**
 * Abstract resource map repository.
 *
 * @phpstan-type TResourceMapRow array{id: numeric-string, commerce_id: string, local_id: numeric-string}
 */
abstract class AbstractResourceMapRepository
{
    /** @var string type of resources managed by this repository */
    protected string $resourceType;

    /** @var CommerceContextContract */
    protected CommerceContextContract $commerceContext;

    /** @var RemoteIdStrategyContract */
    protected RemoteIdStrategyContract $remoteIdMutationStrategy;

    /** @var string commerce map IDs (uuids, ksuids) table name */
    public const MAP_IDS_TABLE = 'godaddy_mwc_commerce_map_ids';

    /** @var string commerce resource type table name */
    public const RESOURCE_TYPES_TABLE = 'godaddy_mwc_commerce_map_resource_types';

    /** @var string column storing the remote commerce IDs */
    public const COLUMN_COMMERCE_ID = 'commerce_id';

    /** @var string column storing the primary ID of the map's row */
    public const COLUMN_ID = 'id';

    /** @var string column storing the local IDs */
    public const COLUMN_LOCAL_ID = 'local_id';

    /** @var string column storing the resource type IDs */
    public const COLUMN_RESOURCE_TYPE_ID = 'resource_type_id';

    /** @var string column storing the commerce context IDs */
    public const COLUMN_COMMERCE_CONTEXT_ID = 'commerce_context_id';

    /**
     * Constructor.
     *
     * @param CommerceContextContract $commerceContext
     * @param ?RemoteIdStrategyContract $remoteIdMutationStrategy The method used to transform the ID when saving and
     *                                                            returning the result. When null, this will use a
     *                                                            pass-through strategy, which does not mutate the ID.
     */
    final public function __construct(CommerceContextContract $commerceContext, ?RemoteIdStrategyContract $remoteIdMutationStrategy = null)
    {
        $this->commerceContext = $commerceContext;
        $this->remoteIdMutationStrategy = $remoteIdMutationStrategy ?? new PassThruRemoteIdMutationStrategy();
    }

    /**
     * Creates a new instance using the provided store ID.
     *
     * @param string $storeId The store ID to use in the context.
     *
     * @return static
     */
    public static function fromStoreId(string $storeId)
    {
        return new static(CommerceContext::seed(['storeId' => $storeId]));
    }

    /**
     * Adds a new map to associate the local ID with the given remote UUID.
     *
     * @param int $localId
     * @param string $remoteId
     * @return void
     * @throws WordPressDatabaseException
     */
    public function add(int $localId, string $remoteId) : void
    {
        DatabaseRepository::insert(static::MAP_IDS_TABLE, [
            static::COLUMN_LOCAL_ID            => $localId,
            static::COLUMN_COMMERCE_ID         => $this->remoteIdMutationStrategy->getRemoteIdForDatabase($remoteId),
            static::COLUMN_RESOURCE_TYPE_ID    => $this->getResourceTypeId(),
            static::COLUMN_COMMERCE_CONTEXT_ID => $this->getContextId(),
        ]);
    }

    /**
     * Updates the remote ID of a row, if found by local ID, otherwise adds the map.
     *
     * Unlike {@see AbstractResourceMapRepository::add()}, this method does not attempt
     * to write to the database if an identical map already exists.
     *
     * @throws WordPressDatabaseException
     */
    public function addOrUpdateRemoteId(int $localId, string $remoteId) : void
    {
        $existingResourceMap = $this->getMappingByLocalId($localId);

        if (! $existingResourceMap) {
            $this->add($localId, $remoteId);
        } elseif ($remoteId !== $existingResourceMap->commerceId) {
            $formattedRemoteId = $this->remoteIdMutationStrategy->getRemoteIdForDatabase($remoteId);

            DatabaseRepository::update(
                AbstractResourceMapRepository::MAP_IDS_TABLE,
                [AbstractResourceMapRepository::COLUMN_COMMERCE_ID => $formattedRemoteId],
                [AbstractResourceMapRepository::COLUMN_ID          => $existingResourceMap->id],
                ['%s'],
                ['%d'],
            );
        }
    }

    /**
     * Finds the remote ID of a resource by its local ID.
     *
     * @param int $localId
     * @return string|null
     */
    public function getRemoteId(int $localId) : ?string
    {
        $uuidMapTableName = static::MAP_IDS_TABLE;

        $row = DatabaseRepository::getRow(
            implode(' ', [
                'SELECT map_ids.'.static::COLUMN_COMMERCE_ID." FROM {$uuidMapTableName} AS map_ids",
                $this->getResourceTypeJoinClause(),
                $this->getContextJoinClause(),
                'WHERE map_ids.'.static::COLUMN_LOCAL_ID.' = %d',
            ]),
            [$localId]
        );

        $result = TypeHelper::string(ArrayHelper::get($row, static::COLUMN_COMMERCE_ID), '') ?: null;

        return $this->remoteIdMutationStrategy->formatRemoteIdFromDatabase($result);
    }

    /**
     * Finds results by the supplied $columnName and $values.
     *
     * @param string $columnName
     * @param array<int|string> $values
     * @return array<array{commerce_id: string, local_id: int|string}>
     * @throws InvalidArgumentException
     */
    public function getIdsBy(string $columnName, array $values) : array
    {
        $uuidMapTableName = static::MAP_IDS_TABLE;

        if (static::COLUMN_COMMERCE_ID === $columnName) {
            $placeholder = '%s';
            $values = array_map(fn ($item) => $this->remoteIdMutationStrategy->getRemoteIdForDatabase(TypeHelper::string($item, '')), $values);
        } elseif (static::COLUMN_LOCAL_ID === $columnName) {
            $placeholder = '%d';
        } else {
            throw new InvalidArgumentException(sprintf('columnName parameter must be one of: %s, %s', static::COLUMN_COMMERCE_ID, static::COLUMN_LOCAL_ID));
        }

        $idPlaceholders = implode(',', array_fill(0, count($values), $placeholder));

        $results = DatabaseRepository::getResults(
            implode(' ', [
                'SELECT map_ids.'.static::COLUMN_COMMERCE_ID.', map_ids.'.static::COLUMN_LOCAL_ID." FROM {$uuidMapTableName} AS map_ids",
                $this->getResourceTypeJoinClause(),
                $this->getContextJoinClause(),
                "WHERE map_ids.{$columnName} IN ({$idPlaceholders})",
            ]),
            $values
        );

        /** @var array<array{commerce_id: string, local_id: string}> $results */
        $results = TypeHelper::array($results, []);

        /** @var array<array{commerce_id: string, local_id: string}> $results */
        $results = array_map([$this, 'formatGetIdItemCommerceId'], $results);

        return $results;
    }

    /**
     * Formats a single response item's commerce_id using formatRemoteIdFromDatabase.
     *
     * @param array{commerce_id: ?string, local_id: ?string} $item
     *
     * @return array{commerce_id: ?string, local_id: ?string}
     */
    protected function formatGetIdItemCommerceId(array $item) : array
    {
        $item['commerce_id'] = $this->remoteIdMutationStrategy->formatRemoteIdFromDatabase($item['commerce_id']);

        return $item;
    }

    /**
     * Finds the local ID of a resource by its remote UUID.
     *
     * @param string $remoteId
     *
     * @return int|null
     */
    public function getLocalId(string $remoteId) : ?int
    {
        $uuidMapTableName = static::MAP_IDS_TABLE;

        $row = DatabaseRepository::getRow(
            implode(' ', [
                'SELECT map_ids.'.static::COLUMN_LOCAL_ID." FROM {$uuidMapTableName} AS map_ids",
                $this->getResourceTypeJoinClause(),
                $this->getContextJoinClause(),
                'WHERE map_ids.'.static::COLUMN_COMMERCE_ID.' = %s',
            ]),
            [$this->remoteIdMutationStrategy->getRemoteIdForDatabase($remoteId)]
        );

        return TypeHelper::int(ArrayHelper::get($row, static::COLUMN_LOCAL_ID), 0) ?: null;
    }

    /**
     * Gets the ID of the resource type associated with this repository.
     *
     * @return int|null
     */
    public function getResourceTypeId() : ?int
    {
        $tableName = static::RESOURCE_TYPES_TABLE;

        $row = DatabaseRepository::getRow("SELECT id FROM {$tableName} WHERE name = %s", [$this->resourceType]);

        return TypeHelper::int(ArrayHelper::get($row, 'id'), 0) ?: null;
    }

    /**
     * Gets a SQL clause that can be used to perform an inner join on the resource type tables.
     *
     * @param string $idMapTableNameAlias
     * @param string $resourceMapTableNameAlias
     * @return string
     */
    protected function getResourceTypeJoinClause(
        string $idMapTableNameAlias = 'map_ids',
        string $resourceMapTableNameAlias = 'resource_types'
    ) : string {
        $resourceMapTableName = static::RESOURCE_TYPES_TABLE;
        $resourceType = TypeHelper::string(esc_sql($this->resourceType), '');

        return "INNER JOIN {$resourceMapTableName} AS {$resourceMapTableNameAlias}
        ON {$resourceMapTableNameAlias}.id = {$idMapTableNameAlias}.".static::COLUMN_RESOURCE_TYPE_ID."
        AND {$resourceMapTableNameAlias}.name = '{$resourceType}'";
    }

    /**
     * Gets a SQL clause that can be used to perform an inner join on the contexts table.
     *
     * @param string $idMapTableNameAlias
     * @param string $contextsTableNameAlias
     * @return string
     */
    protected function getContextJoinClause(
        string $idMapTableNameAlias = 'map_ids',
        string $contextsTableNameAlias = 'contexts'
    ) : string {
        $contextsTableName = CommerceContextRepository::CONTEXT_TABLE;
        $storeId = TypeHelper::string(esc_sql($this->commerceContext->getStoreId()), '');

        return "INNER JOIN {$contextsTableName} AS {$contextsTableNameAlias}
        ON {$contextsTableNameAlias}.id = {$idMapTableNameAlias}.".static::COLUMN_COMMERCE_CONTEXT_ID."
        AND {$contextsTableNameAlias}.gd_store_id = '{$storeId}'";
    }

    /**
     * Gets the context ID.
     *
     * @return int|null
     */
    protected function getContextId() : ?int
    {
        return $this->commerceContext->getId();
    }

    /**
     * Gets the map of the given resource local ID to a remote ID.
     */
    protected function getMappingByLocalId(int $localId) : ?ResourceMap
    {
        if (! $row = $this->getMappingRowByLocalId($localId)) {
            return null;
        }

        $row['commerce_id'] = TypeHelper::string($this->remoteIdMutationStrategy->formatRemoteIdFromDatabase($row['commerce_id']), '');

        return ResourceMap::fromRow($row);
    }

    /**
     * Gets the row representing a map of the given resource local ID to a remote ID.
     *
     * @return TResourceMapRow|array{}
     */
    protected function getMappingRowByLocalId(int $localId) : array
    {
        /** @var TResourceMapRow|array{} $row */
        $row = DatabaseRepository::getRow(
            implode(' ', [
                'SELECT map_ids.'.static::COLUMN_ID.', map_ids.'.static::COLUMN_COMMERCE_ID.', map_ids.'.static::COLUMN_LOCAL_ID.' FROM '.static::MAP_IDS_TABLE.' AS map_ids',
                $this->getResourceTypeJoinClause(),
                $this->getContextJoinClause(),
                'WHERE map_ids.'.static::COLUMN_LOCAL_ID.' = %d',
            ]),
            [$localId]
        );

        return $row;
    }

    /**
     * Deletes a mapping row by the provided local ID.
     *
     * @param int $localId
     * @return int number of records that were deleted
     * @throws WordPressDatabaseException
     */
    public function deleteByLocalId(int $localId) : int
    {
        $mapping = $this->getMappingByLocalId($localId);

        if ($mapping) {
            $numberRowsDeleted = DatabaseRepository::delete(
                static::MAP_IDS_TABLE,
                ['id' => $mapping->id],
                ['%d']
            );
        } else {
            $numberRowsDeleted = 0;
        }

        return TypeHelper::int($numberRowsDeleted, 0);
    }
}
