<?php

declare(strict_types=1);

namespace Azad\Container\Definition;

use Closure;
use Azad\Container\Container;

/**
 * Wraps a user-supplied factory closure. The container instance is passed
 * as the sole argument, so the factory can pull its own dependencies from it.
 */
final class FactoryDefinition implements DefinitionInterface
{
    public function __construct(
        private readonly Closure $factory,
        private readonly bool $shared = true,
    ) {
    }

    public function isShared(): bool
    {
        return $this->shared;
    }

    public function resolve(Container $container, array $parameterOverrides = []): mixed
    {
        return ($this->factory)($container);
    }
}
