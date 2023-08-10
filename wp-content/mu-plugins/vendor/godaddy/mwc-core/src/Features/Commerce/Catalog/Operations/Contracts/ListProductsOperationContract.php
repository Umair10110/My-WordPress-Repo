<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;

interface ListProductsOperationContract extends ListRemoteResourcesOperationContract, CanConvertToArrayContract
{
    /**
     * Set the include deleted flag.
     *
     * @param ?bool $includeDeleted
     * @return ListProductsOperationContract
     */
    public function setIncludeDeleted(?bool $includeDeleted) : ListProductsOperationContract;

    /**
     * Get the include deleted flag.
     *
     * @return ?bool
     */
    public function getIncludeDeleted() : ?bool;

    /**
     * Sets the include child products flag.
     *
     * @param bool|null $includeChildProducts
     * @return ListProductsOperationContract
     */
    public function setIncludeChildProducts(?bool $includeChildProducts) : ListProductsOperationContract;

    /**
     * Gets the include child products flag.
     *
     * @return bool|null
     */
    public function getIncludeChildProducts() : ?bool;

    /**
     * Set the sort by.
     *
     * @param ?string $sortBy
     * @return ListProductsOperationContract
     */
    public function setSortBy(?string $sortBy) : ListProductsOperationContract;

    /**
     * Get the sort by.
     *
     * @return ?string
     */
    public function getSortBy() : ?string;

    /**
     * Set the sort order.
     *
     * @param ?string $sortOrder
     * @return ListProductsOperationContract
     */
    public function setSortOrder(?string $sortOrder) : ListProductsOperationContract;

    /**
     * Get the sort order.
     *
     * @return ?string
     */
    public function getSortOrder() : ?string;

    /**
     * Set the category ID.
     *
     * @param ?int $localCategoryId
     * @return ListProductsOperationContract
     */
    public function setLocalCategoryId(?int $localCategoryId) : ListProductsOperationContract;

    /**
     * Get the category ID.
     *
     * @return ?int
     */
    public function getLocalCategoryId() : ?int;

    /**
     * Set the channel ID.
     *
     * @param ?string $channelId
     * @return ListProductsOperationContract
     */
    public function setChannelId(?string $channelId) : ListProductsOperationContract;

    /**
     * Get the channel ID.
     *
     * @return ?string
     */
    public function getChannelId() : ?string;

    /**
     * Set the SKU.
     *
     * @param ?string $sku
     * @return ListProductsOperationContract
     */
    public function setSku(?string $sku) : ListProductsOperationContract;

    /**
     * Get the SKU.
     *
     * @return ?string
     */
    public function getSku() : ?string;

    /**
     * Set the alt ID.
     *
     * @param ?string $altId
     * @return ListProductsOperationContract
     */
    public function setAltId(?string $altId) : ListProductsOperationContract;

    /**
     * Get the alt ID.
     *
     * @return ?string
     */
    public function getAltId() : ?string;

    /**
     * Set the name.
     *
     * @param ?string $name
     * @return ListProductsOperationContract
     */
    public function setName(?string $name) : ListProductsOperationContract;

    /**
     * Get the name.
     *
     * @return ?string
     */
    public function getName() : ?string;

    /**
     * Set the type.
     *
     * @param ?string $type
     * @return ListProductsOperationContract
     */
    public function setType(?string $type) : ListProductsOperationContract;

    /**
     * Get the type.
     *
     * @return ?string
     */
    public function getType() : ?string;

    /**
     * Sets the number of results per page.
     *
     * @param int|null $value
     * @return ListProductsOperationContract
     */
    public function setPageSize(?int $value) : ListProductsOperationContract;

    /**
     * Gets the number of results per page.
     *
     * @return int|null
     */
    public function getPageSize() : ?int;

    /**
     * Set the page token.
     *
     * @param ?string $pageToken
     * @return ListProductsOperationContract
     */
    public function setPageToken(?string $pageToken) : ListProductsOperationContract;

    /**
     * Get the page token.
     *
     * @return ?string
     */
    public function getPageToken() : ?string;

    /**
     * Set the token direction.
     *
     * @param ?string $tokenDirection
     * @return ListProductsOperationContract
     */
    public function setTokenDirection(?string $tokenDirection) : ListProductsOperationContract;

    /**
     * Get the token direction.
     *
     * @return ?string
     */
    public function getTokenDirection() : ?string;
}
