<?php

namespace GoDaddy\WordPress\MWC\Core\Stores\Repositories;

use GoDaddy\WordPress\MWC\Common\Stores\Exceptions\RegisterStoreException;
use GoDaddy\WordPress\MWC\Common\Stores\Repositories\AbstractStoreRepository;

/**
 * Store repository for the Managed WordPress platform.
 */
class StoreRepository extends AbstractStoreRepository
{
    /**
     * {@inheritDoc}
     */
    public function determineDefaultStoreId() : ?string
    {
        // not implemented at this time
        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @throws RegisterStoreException
     */
    public function registerStore(string $storeId) : void
    {
        throw new RegisterStoreException('registerStore is not implemented for the Managed WordPress platform.');
    }
}
