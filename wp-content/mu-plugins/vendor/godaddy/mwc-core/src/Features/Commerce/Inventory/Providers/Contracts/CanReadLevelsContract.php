<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLevelInput;

interface CanReadLevelsContract
{
    /**
     * Reads the item level.
     *
     * @param ReadLevelInput $input
     *
     * @return Level
     */
    public function read(ReadLevelInput $input) : Level;
}
