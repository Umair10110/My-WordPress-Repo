<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories;

use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

class LineItemMapRepository extends AbstractResourceMapRepository
{
    public const RESOURCE_TYPE = 'line_item';

    /** @var string type of resources managed by this repository */
    protected string $resourceType = self::RESOURCE_TYPE;
}
