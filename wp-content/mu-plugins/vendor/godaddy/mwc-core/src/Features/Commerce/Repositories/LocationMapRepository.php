<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories;

use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

class LocationMapRepository extends AbstractResourceMapRepository
{
    const RESOURCE_TYPE = 'inventory_location';

    /** @var string type of resources managed by this repository */
    protected string $resourceType = self::RESOURCE_TYPE;
}
