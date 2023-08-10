<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ReadProductOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;

/**
 * Checks if a product has been deleted upstream, and if so, handles the appropriate user experience by displaying an error message.
 */
class CheckForDeletedProductHelper
{
    protected ProductsServiceContract $productsService;
    protected RemoteProductNotFoundHelper $remoteProductNotFoundHelper;

    /**
     * @param ProductsServiceContract $productsService
     * @param RemoteProductNotFoundHelper $remoteProductNotFoundHelper
     */
    public function __construct(ProductsServiceContract $productsService, RemoteProductNotFoundHelper $remoteProductNotFoundHelper)
    {
        $this->productsService = $productsService;
        $this->remoteProductNotFoundHelper = $remoteProductNotFoundHelper;
    }

    /**
     * Checks if the supplied local product ID has been deleted upstream.
     * If it has been, then we'll display an error message to the user {@see static::handleDeletedProductUserExperience()}
     * If it has not been, then we do nothing.
     *
     * @param int $localId
     * @return void
     */
    public function checkByLocalId(int $localId) : void
    {
        try {
            $this->productsService->readProduct(
                (new ReadProductOperation())->setLocalId($localId)
            );
        } catch(GatewayRequest404Exception $e) {
            // delete the product locally
            $this->remoteProductNotFoundHelper->handle($localId);

            $this->handleDeletedProductUserExperience();
        } catch(MissingProductRemoteIdException $e) {
            // this means the product hasn't been written to the platform yet; we do not need to report this error.
            // @TODO perhaps in a future story we will use this opportunity to write it immediately
        } catch(Exception $e) {
            SentryException::getNewInstance('Failed to fetch product by local ID.', $e);
        }
    }

    /**
     * Handles the user experience for attempting to view a product that has since been deleted.
     *
     * @return void
     */
    protected function handleDeletedProductUserExperience() : void
    {
        if (WordPressRepository::isAdmin()) {
            wp_die(__('You attempted to edit an item that does not exist. Perhaps it was deleted?', 'mwc-core'));
        } else {
            // @TODO determine how to handle front-end in MWC-12620 {agibson 2023-06-15}
        }
    }
}
