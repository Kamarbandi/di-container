<?php

declare(strict_types=1);

namespace Azad\Container\ServiceProvider;

use Azad\Container\Container;

/**
 * Groups the registration of a related set of services into a single class.
 *
 * When provides() returns a non-empty list, registration is deferred:
 * register() only runs the first time one of those ids is actually
 * requested from the container, keeping unused providers free of cost.
 */
interface ServiceProviderInterface
{
    /**
     * @return string[] Ids this provider can register. Return an empty
     *                   array to register eagerly, e.g. for providers that
     *                   only tag entries or have side effects.
     */
    public function provides(): array;

    public function register(Container $container): void;
}
