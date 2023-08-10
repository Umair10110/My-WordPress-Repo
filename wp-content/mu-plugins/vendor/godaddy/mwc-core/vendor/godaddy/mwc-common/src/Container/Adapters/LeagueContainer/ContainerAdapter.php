<?php

namespace GoDaddy\WordPress\MWC\Common\Container\Adapters\LeagueContainer;

use Closure;
use GoDaddy\WordPress\MWC\Common\Container\Contracts\ContainerContract;
use GoDaddy\WordPress\MWC\Common\Container\Contracts\ServiceProviderContract;
use League\Container\Container as LeagueContainer;
use League\Container\ReflectionContainer;

/**
 * Adapts {@see LeagueContainer} to interface with {@see ContainerContract}.
 */
class ContainerAdapter implements ContainerContract
{
    /** @var LeagueContainer */
    protected LeagueContainer $container;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->container = new LeagueContainer();
    }

    /**
     * {@inheritDoc}
     *
     * @note Due to how league/container works, binding a class-string $concrete only works when auto-wiring is enabled
     *    {@see enableAutoWiring}. The alternative is to always pass a closure that instantiates the concrete class.
     */
    public function bind(string $abstract, $concrete, ?array $constructorArgs = null) : void
    {
        if (! $concrete instanceof Closure) {
            $concrete = fn () => $this->get($concrete);
        }

        $this->container->add($abstract, $concrete);
    }

    /**
     * Add a provider.
     *
     * @NOTE We might want to move the inner parts of this method to its own class so it can do more stuff, like register bootable providers.
     *
     * @param ServiceProviderContract $provider
     * @return void
     */
    public function addProvider(ServiceProviderContract $provider) : void
    {
        $provider->setContainer($this);

        $this->container->addServiceProvider(ServiceProviderAdapter::getNewInstance($provider));
    }

    /**
     * {@inheritDoc}
     *
     * @NOTE Once enabled, it cannot be disabled on the same container instance.
     */
    public function enableAutoWiring() : void
    {
        $this->container->delegate(new ReflectionContainer());
    }

    /**
     * {@inheritDoc}
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * {@inheritDoc}
     */
    public function has($id) : bool
    {
        return $this->container->has($id);
    }
}
