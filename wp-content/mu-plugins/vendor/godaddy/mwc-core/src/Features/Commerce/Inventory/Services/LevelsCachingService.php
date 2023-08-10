<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingLevelRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits\CanConvertLevelResponseTrait;

/**
 * @method Level|null get(string $remoteId)
 * @method Level[] getMany(array $remoteIds)
 * @method Level remember(string $remoteId, callable $loader)
 * @method set(Level $resource)
 * @method setMany(Level[] $resources)
 */
class LevelsCachingService extends AbstractCachingService
{
    use CanConvertLevelResponseTrait;

    protected string $resourceType = 'inventory-levels';

    /**
     * {@inheritDoc}
     *
     * @return Level
     * @throws Exception
     */
    protected function makeResourceFromArray(array $resourceArray) : Level
    {
        return $this->convertLevelResponse($resourceArray);
    }

    /**
     * {@inheritDoc}
     *
     * @param Level $resource
     *
     * @throws MissingLevelRemoteIdException
     */
    protected function getResourceRemoteId(object $resource) : string
    {
        if (! empty($resource->inventoryLevelId)) {
            return $resource->inventoryLevelId;
        }

        throw MissingLevelRemoteIdException::withDefaultMessage();
    }
}
