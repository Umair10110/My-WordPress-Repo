<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\CreateOrUpdateLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\DeleteLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\DeleteLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadLevelResponseContract;

interface LevelsServiceContract
{
    /**
     * Create or update a level.
     *
     * @param CreateOrUpdateLevelOperationContract $operation
     * @return CreateOrUpdateLevelResponseContract
     */
    public function createOrUpdateLevel(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract;

    /**
     * Read a level.
     *
     * @param ReadLevelOperationContract $operation
     * @return ReadLevelResponseContract
     */
    public function readLevel(ReadLevelOperationContract $operation) : ReadLevelResponseContract;

    /**
     * Delete a level.
     *
     * @param DeleteLevelOperationContract $operation
     * @return DeleteLevelResponseContract
     */
    public function deleteLevel(DeleteLevelOperationContract $operation) : DeleteLevelResponseContract;
}
