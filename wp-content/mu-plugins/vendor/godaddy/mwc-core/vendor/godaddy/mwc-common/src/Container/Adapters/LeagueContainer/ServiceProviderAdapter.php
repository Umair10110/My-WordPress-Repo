<?php

namespace GoDaddy\WordPress\MWC\Common\Container\Adapters\LeagueContainer;

use GoDaddy\WordPress\MWC\Common\Container\Contracts\ServiceProviderContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Adapts an MWC service provider instance to be compatible with League\Container's service provider interface.
 */
class ServiceProviderAdapter extends AbstractServiceProvider
{
    use CanGetNewInstanceTrait;

    /**
     * @var ServiceProviderContract the MWC service provider to be adapted.
     */
    protected ServiceProviderContract $serviceProvider;

    /**
     * @note Each provider must have a unique ID in League/Container. We set it to the $serviceProvider class name.
     *
     * @param ServiceProviderContract $serviceProvider an MWC service provider
     */
    public function __construct(ServiceProviderContract $serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
        $this->setIdentifier(get_class($this->serviceProvider));
    }

    /**
     * {@inheritDoc}
     */
    public function provides(string $id) : bool
    {
        return $this->serviceProvider->provides($id);
    }

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->serviceProvider->register();
    }
}
