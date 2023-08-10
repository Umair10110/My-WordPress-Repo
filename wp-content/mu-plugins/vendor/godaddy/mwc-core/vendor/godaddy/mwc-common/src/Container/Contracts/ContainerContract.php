<?php

namespace GoDaddy\WordPress\MWC\Common\Container\Contracts;

use Closure;
use Psr\Container\ContainerInterface;

interface ContainerContract extends ContainerInterface
{
    /**
     * Register a binding in the container.
     *
     * @param string $abstract
     * @param class-string|Closure $concrete
     * @param mixed[]|null $constructorArgs (DEPRECATED) arguments to be passed to $concrete's constructor
     * @return void
     */
    public function bind(string $abstract, $concrete, ?array $constructorArgs = []) : void;

    /**
     * Adds a container.
     *
     * @param ServiceProviderContract $provider
     * @return void
     */
    public function addProvider(ServiceProviderContract $provider) : void;

    /**
     * Enables auto-wiring of class constructor arguments.
     *
     * @NOTE this has a cost due to use of reflection.
     *
     * @return void
     */
    public function enableAutoWiring() : void;
}
