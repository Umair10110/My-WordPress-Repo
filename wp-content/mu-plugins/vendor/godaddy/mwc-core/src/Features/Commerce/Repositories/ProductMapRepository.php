<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories;

use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * Product map repository.
 */
class ProductMapRepository extends AbstractResourceMapRepository
{
    public const RESOURCE_TYPE = 'product';

    /** @var string type of resources managed by this repository */
    protected string $resourceType = self::RESOURCE_TYPE;
}
