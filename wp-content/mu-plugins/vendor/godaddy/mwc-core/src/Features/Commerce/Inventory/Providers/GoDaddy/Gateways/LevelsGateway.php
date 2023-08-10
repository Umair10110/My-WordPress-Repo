<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\LevelsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\DeleteLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\CreateLevelRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\DeleteLevelRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\ReadLevelRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\UpdateLevelRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;

class LevelsGateway extends AbstractGateway implements LevelsGatewayContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     *
     * @throws CommerceExceptionContract|Exception
     */
    public function createOrUpdate(UpsertLevelInput $input) : Level
    {
        $adapterClass = isset($input->level->inventoryLevelId) ? UpdateLevelRequestAdapter::class : CreateLevelRequestAdapter::class;

        /** @var Level $result */
        $result = $this->doAdaptedRequest($adapterClass::getNewInstance($input));

        return $result;
    }

    /**
     * {@inheritDoc}
     *
     * @throws CommerceExceptionContract|Exception
     */
    public function delete(DeleteLevelInput $input) : bool
    {
        $this->doAdaptedRequest(DeleteLevelRequestAdapter::getNewInstance($input));

        return true;
    }

    /**
     * Reads a Level.
     *
     * @param ReadLevelInput $input
     *
     * @return Level
     * @throws CommerceExceptionContract|Exception
     */
    public function read(ReadLevelInput $input) : Level
    {
        /** @var Level $result */
        $result = $this->doAdaptedRequest(ReadLevelRequestAdapter::getNewInstance($input));

        return $result;
    }
}
