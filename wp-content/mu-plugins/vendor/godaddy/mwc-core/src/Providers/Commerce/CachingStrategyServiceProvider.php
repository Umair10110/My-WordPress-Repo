<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\WpCacheCachingStrategy;

/**
 * Service provider for the Caching Strategy Service.
 */
class CachingStrategyServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [CachingStrategyContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(CachingStrategyContract::class, WpCacheCachingStrategy::class);
    }
}
