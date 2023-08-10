<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Repositories;

use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

class NoteMapRepository extends AbstractResourceMapRepository
{
    public const RESOURCE_TYPE = 'order_note';

    protected string $resourceType = self::RESOURCE_TYPE;
}
