<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Repositories;

use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

class OrderMapRepository extends AbstractResourceMapRepository
{
    public const RESOURCE_TYPE = 'order';

    /** @var string type of resources managed by this repository */
    public string $resourceType = self::RESOURCE_TYPE;
}
