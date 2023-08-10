<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListProductsOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadProductBySkuOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\CreateOrUpdateProductResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListProductsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ReadProductResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;

/**
 * Contract for catalog product services.
 */
interface ProductsServiceContract
{
    /**
     * Creates or updates a product.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateOrUpdateProductResponseContract
     * @throws GatewayRequestException
     */
    public function createOrUpdateProduct(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract;

    /**
     * Creates a product.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateOrUpdateProductResponseContract
     * @throws GatewayRequestException
     */
    public function createProduct(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract;

    /**
     * Reads a product by the local ID.
     *
     * @param ReadProductOperationContract $operation
     * @return ReadProductResponseContract
     * @throws MissingProductRemoteIdException|GatewayRequest404Exception|GatewayRequestException
     */
    public function readProduct(ReadProductOperationContract $operation) : ReadProductResponseContract;

    /**
     * Reads a product by SKU.
     *
     * @param ReadProductBySkuOperationContract $operation
     * @return ReadProductResponseContract
     */
    public function readProductBySku(ReadProductBySkuOperationContract $operation) : ReadProductResponseContract;

    /**
     * Lists products.
     *
     * @param ListProductsOperationContract $operation
     * @return ListProductsResponseContract
     * @throws GatewayRequestException|MissingRemoteIdsAfterLocalIdConversionException
     */
    public function listProducts(ListProductsOperationContract $operation) : ListProductsResponseContract;

    /**
     * Updates a product.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param string $remoteId
     * @return CreateOrUpdateProductResponseContract
     * @throws GatewayRequest404Exception|GatewayRequestException
     */
    public function updateProduct(CreateOrUpdateProductOperationContract $operation, string $remoteId) : CreateOrUpdateProductResponseContract;
}
