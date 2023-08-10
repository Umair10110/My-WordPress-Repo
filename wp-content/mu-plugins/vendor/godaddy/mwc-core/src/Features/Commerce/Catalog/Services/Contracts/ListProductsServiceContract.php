<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListProductsOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;

/**
 * Contract for services that can list products.
 */
interface ListProductsServiceContract
{
    /**
     * Lists products.
     *
     * @param ListProductsOperationContract $operation
     * @return ProductAssociation[]
     */
    public function list(ListProductsOperationContract $operation) : array;
}
