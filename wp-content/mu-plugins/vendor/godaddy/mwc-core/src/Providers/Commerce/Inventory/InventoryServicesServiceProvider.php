<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Inventory;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LevelsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\LocationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\ReservationsService;

class InventoryServicesServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        LevelsServiceContract::class,
        LocationsServiceContract::class,
        ReservationsServiceContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(LevelsServiceContract::class, LevelsService::class);
        $this->getContainer()->bind(LocationsServiceContract::class, LocationsService::class);
        $this->getContainer()->bind(ReservationsServiceContract::class, ReservationsService::class);
    }
}
