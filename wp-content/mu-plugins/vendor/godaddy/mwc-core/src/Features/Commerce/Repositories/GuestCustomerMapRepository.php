<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories;

use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

class GuestCustomerMapRepository extends AbstractResourceMapRepository
{
    public const RESOURCE_TYPE = 'guest_order_customer';

    /** @var string type of resources managed by this repository */
    protected string $resourceType = self::RESOURCE_TYPE;
}
