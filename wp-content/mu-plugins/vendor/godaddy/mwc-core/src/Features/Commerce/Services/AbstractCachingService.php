<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

/**
 * Abstract caching service for remote entities.
 */
abstract class AbstractCachingService implements CachingServiceContract
{
    /** @var CachingStrategyContract caching strategy */
    protected CachingStrategyContract $cachingStrategy;

    /** @var string plural name of the resource type (e.g. 'products' or 'customers') -- to be set by concrete implementations */
    protected string $resourceType;

    /** @var int cache TTL (in seconds) for entries */
    protected int $cacheTtl = 30;

    /**
     * Constructor.
     *
     * @param CachingStrategyContract $cachingStrategy
     */
    public function __construct(CachingStrategyContract $cachingStrategy)
    {
        $this->cachingStrategy = $cachingStrategy;
    }

    /**
     * Gets the name of the cache group.
     *
     * @return string
     */
    protected function getCacheGroup() : string
    {
        return "godaddy-commerce-{$this->resourceType}";
    }

    /**
     * {@inheritDoc}
     */
    public function remember(string $remoteId, callable $loader) : object
    {
        $resource = $this->get($remoteId);

        if (! $resource) {
            $this->set($resource = $loader());
        }

        return $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $remoteId) : ?object
    {
        $jsonResource = $this->cachingStrategy->get($remoteId, $this->getCacheGroup());

        if (empty($jsonResource) || ! is_string($jsonResource)) {
            return null;
        }

        return $this->convertJsonResource($jsonResource);
    }

    /**
     * {@inheritDoc}
     */
    public function getMany(array $remoteIds) : array
    {
        return array_filter(
            array_map(
                [$this, 'convertJsonResource'],
                TypeHelper::arrayOfStrings($this->cachingStrategy->getMany($remoteIds, $this->getCacheGroup()))
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function set(CanConvertToArrayContract $resource) : void
    {
        $resourceRemoteId = $this->getResourceRemoteId($resource);

        $jsonEncodedResource = json_encode($resource->toArray());
        if (! is_string($jsonEncodedResource)) {
            throw new CachingStrategyException("Failed to JSON-encode resource ID {$resourceRemoteId}");
        }

        $this->cachingStrategy->set(
            $resourceRemoteId,
            $this->getCacheGroup(),
            $jsonEncodedResource,
            $this->cacheTtl
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setMany(array $resources) : void
    {
        $jsonResources = [];
        foreach ($resources as $resource) {
            $jsonEncodedResource = json_encode($resource->toArray());

            if ($jsonEncodedResource) {
                $jsonResources[$this->getResourceRemoteId($resource)] = $jsonEncodedResource;
            }
        }

        $this->cachingStrategy->setMany(
            $this->getCacheGroup(),
            $jsonResources,
            $this->cacheTtl
        );
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $remoteId) : void
    {
        $this->cachingStrategy->remove($remoteId, $this->getCacheGroup());
    }

    /**
     * Converts a JSON-encoded resource into its DTO.
     *
     * @param string $jsonResource JSON-encoded resource
     * @return object|null
     */
    protected function convertJsonResource(string $jsonResource) : ?object
    {
        $resourceArray = json_decode($jsonResource, true);

        if (! is_array($resourceArray)) {
            return null;
        }

        return $this->makeResourceFromArray($resourceArray);
    }

    /**
     * Builds a resource DTO from an array.
     *
     * @param array<string, mixed> $resourceArray
     * @return object
     */
    abstract protected function makeResourceFromArray(array $resourceArray) : object;

    /**
     * Gets the unique remote ID for a given resource.
     *
     * @param object $resource
     * @return non-empty-string
     * @throws CommerceExceptionContract
     */
    abstract protected function getResourceRemoteId(object $resource) : string;
}
