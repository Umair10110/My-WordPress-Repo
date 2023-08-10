<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\Traits\CanConvertSummaryResponseTrait;

/**
 * @method Summary|null get(string $remoteId)
 * @method Summary[] getMany(array $remoteIds)
 * @method Summary remember(string $remoteId, callable $loader)
 * @method set(Summary $resource)
 * @method setMany(Summary[] $resources)
 */
class SummariesCachingService extends AbstractCachingService
{
    use CanConvertSummaryResponseTrait;

    protected string $resourceType = 'inventory-summaries';

    /**
     * {@inheritDoc}
     *
     * @return Summary
     *
     * @throws Exception
     */
    protected function makeResourceFromArray(array $resourceArray) : object
    {
        return $this->convertSummaryResponse($resourceArray);
    }

    /**
     * {@inheritDoc}
     *
     * @param Summary $resource
     */
    protected function getResourceRemoteId(object $resource) : string
    {
        if (! empty($resource->inventorySummaryId)) {
            return $resource->inventorySummaryId;
        }

        throw CommerceException::getNewInstance('The summary has no remote UUID');
    }
}
