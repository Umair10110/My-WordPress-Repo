<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListProductsOperationContract;

/**
 * Operation for listing/querying products.
 */
class ListProductsOperation implements ListProductsOperationContract
{
    use CanConvertToArrayTrait {
        CanConvertToArrayTrait::toArray as traitToArray;
    }
    use CanSeedTrait;

    /** @var ?array<int> the products local IDs */
    protected ?array $localIds = null;

    /** @var string[]|null the remote (Commerce) product IDs to include */
    protected ?array $ids = null;

    /** @var ?bool include deleted */
    protected ?bool $includeDeleted = null;

    /** @var ?bool include child products */
    protected ?bool $includeChildProducts = null;

    /** @var ?string sort by */
    protected ?string $sortBy = null;

    /** @var ?string the sort order */
    protected ?string $sortOrder = null;

    /** @var ?int the local category ID */
    protected ?int $localCategoryId = null;

    /** @var ?string the channel ID */
    protected ?string $channelId = null;

    /** @var ?string the SKU */
    protected ?string $sku = null;

    /** @var ?string the altID (aka slug) */
    protected ?string $altId = null;

    /** @var ?string the name */
    protected ?string $name = null;

    /** @var ?string the type */
    protected ?string $type = null;

    /** @var int|null maximum number of results per page */
    protected ?int $pageSize = null;

    /** @var ?string the page token */
    protected ?string $pageToken = null;

    /** @var ?string the parent product id */
    protected ?string $parentId = null;

    /** @var ?string the token direction */
    protected ?string $tokenDirection = null;

    /**
     * {@inheritDoc}
     */
    public function setLocalIds(?array $ids) : ListProductsOperationContract
    {
        $this->localIds = $ids;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalIds() : ?array
    {
        return $this->localIds;
    }

    /**
     * {@inheritDoc}
     */
    public function setIds(?array $value) : ListProductsOperationContract
    {
        $this->ids = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getIds() : ?array
    {
        return $this->ids;
    }

    /**
     * {@inheritDoc}
     */
    public function setIncludeDeleted(?bool $includeDeleted) : ListProductsOperationContract
    {
        $this->includeDeleted = $includeDeleted;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getIncludeDeleted() : ?bool
    {
        return $this->includeDeleted;
    }

    /**
     * {@inheritDoc}
     */
    public function setIncludeChildProducts(?bool $includeChildProducts) : ListProductsOperationContract
    {
        $this->includeChildProducts = $includeChildProducts;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getIncludeChildProducts() : ?bool
    {
        return $this->includeChildProducts;
    }

    /**
     * {@inheritDoc}
     */
    public function setSortBy(?string $sortBy) : ListProductsOperationContract
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortBy() : ?string
    {
        return $this->sortBy;
    }

    /**
     * {@inheritDoc}
     */
    public function setSortOrder(?string $sortOrder) : ListProductsOperationContract
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder() : ?string
    {
        return $this->sortOrder;
    }

    /**
     * {@inheritDoc}
     */
    public function setLocalCategoryId(?int $localCategoryId) : ListProductsOperationContract
    {
        $this->localCategoryId = $localCategoryId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalCategoryId() : ?int
    {
        return $this->localCategoryId;
    }

    /**
     * {@inheritDoc}
     */
    public function setChannelId(?string $channelId) : ListProductsOperationContract
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getChannelId() : ?string
    {
        return $this->channelId;
    }

    /**
     * {@inheritDoc}
     */
    public function setSku(?string $sku) : ListProductsOperationContract
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSku() : ?string
    {
        return $this->sku;
    }

    /**
     * {@inheritDoc}
     */
    public function setAltId(?string $altId) : ListProductsOperationContract
    {
        $this->altId = $altId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAltId() : ?string
    {
        return $this->altId;
    }

    /**
     * {@inheritDoc}
     */
    public function setName(?string $name) : ListProductsOperationContract
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setType(?string $type) : ListProductsOperationContract
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getType() : ?string
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function setPageSize(?int $value) : ListProductsOperationContract
    {
        $this->pageSize = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPageSize() : ?int
    {
        return $this->pageSize;
    }

    /**
     * {@inheritDoc}
     */
    public function setPageToken(?string $pageToken) : ListProductsOperationContract
    {
        $this->pageToken = $pageToken;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPageToken() : ?string
    {
        return $this->pageToken;
    }

    /**
     * {@inheritDoc}
     */
    public function setTokenDirection(?string $tokenDirection) : ListProductsOperationContract
    {
        $this->tokenDirection = $tokenDirection;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenDirection() : ?string
    {
        return $this->tokenDirection;
    }

    /**
     * Overrides the {@see CanConvertToArrayTrait::toArray()} method to exclude some irrelevant properties and only return not-null values.
     *
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $data = $this->traitToArray();

        return ArrayHelper::whereNotNull(ArrayHelper::except($data, ['localIds', 'localCategoryId']));
    }

    /**
     * Get the value of parentId.
     *
     * @return ?string
     */
    public function getParentId() : ?string
    {
        return $this->parentId;
    }

    /**
     * Set the value of parentId.
     *
     * @param ?string $parentId
     * @return $this
     */
    public function setParentId(?string $parentId) : ListProductsOperation
    {
        $this->parentId = $parentId;

        return $this;
    }
}
