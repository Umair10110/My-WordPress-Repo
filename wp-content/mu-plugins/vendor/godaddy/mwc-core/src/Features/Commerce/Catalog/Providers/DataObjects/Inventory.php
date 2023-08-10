<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Provides basic inventory tracking.
 */
class Inventory extends AbstractDataObject
{
    /** @var bool if the product is backorderable. Ignored if tracking is set to false */
    public bool $backorderable = false;

    /** @var bool if the product's inventory is tracked by an external service */
    public bool $externalService = true;

    /** @var float|null the current stock quantity of the product. Ignored if tracking is set to false or externalService is set to true */
    public ?float $quantity = null;

    /** @var bool if the product is tracking inventory */
    public bool $tracking = true;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     backorderable?: bool,
     *     externalService?: bool,
     *     quantity?: ?float,
     *     tracking?: bool,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
