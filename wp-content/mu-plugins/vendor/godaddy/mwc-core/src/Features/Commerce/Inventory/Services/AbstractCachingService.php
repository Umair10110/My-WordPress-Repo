<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractCachingService as CommerceAbstractCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\MemoryCachingStrategy;

abstract class AbstractCachingService extends CommerceAbstractCachingService
{
    /**
     * @param MemoryCachingStrategy $cachingStrategy
     */
    public function __construct(MemoryCachingStrategy $cachingStrategy)
    {
        parent::__construct($cachingStrategy);
    }
}
