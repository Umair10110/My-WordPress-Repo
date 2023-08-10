<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLevelInput;

interface CanCreateOrUpdateLevelsContract
{
    /**
     * Creates or updates the item level.
     *
     * @param UpsertLevelInput $input
     *
     * @return Level
     */
    public function createOrUpdate(UpsertLevelInput $input) : Level;
}
