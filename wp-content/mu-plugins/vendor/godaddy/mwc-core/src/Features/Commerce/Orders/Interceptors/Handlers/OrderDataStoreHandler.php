<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\Handlers\AbstractDataStoreHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\WooCommerce\OrderDataStore;

class OrderDataStoreHandler extends AbstractDataStoreHandler
{
    public function __construct(OrderDataStore $dataStore)
    {
        parent::__construct($dataStore);
    }
}
