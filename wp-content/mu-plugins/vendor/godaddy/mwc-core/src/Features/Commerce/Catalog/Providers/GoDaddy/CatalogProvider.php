<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts\ProductsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Gateways\ProductsGateway;

/**
 * GoDaddy catalog provider.
 */
class CatalogProvider implements CatalogProviderContract
{
    use CanGetNewInstanceTrait;

    /**
     * Returns the {@see ProductsGateway} handler.
     *
     * @return ProductsGatewayContract
     */
    public function products() : ProductsGatewayContract
    {
        return ProductsGateway::getNewInstance();
    }
}
