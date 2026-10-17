<?php

declare(strict_types=1);

namespace Azad\Container\Definition;

use Azad\Container\Container;

/**
 * Describes a class that should be built via constructor autowiring, with
 * optional named parameter overrides for the values autowiring can't guess
 * (scalars, arrays, anything not bound in the container).
 *
 * Mutable and fluent so it can be configured after registration:
 *
 *     $container->define(Logger::class)
 *         ->parameter('targetDir', '/var/log/app')
 *         ->shared(false);
 */
final class AutowireDefinition implements DefinitionInterface
{
    /** @var array<string, mixed> */
    private array $parameters = [];

    /**
     * @param class-string $className
     */
    public function __construct(
        private readonly string $className,
        private bool $shared = true,
    ) {
    }

    public function parameter(string $name, mixed $value): static
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    public function shared(bool $shared = true): static
    {
        $this->shared = $shared;

        return $this;
    }

    public function isShared(): bool
    {
        return $this->shared;
    }

    public function className(): string
    {
        return $this->className;
    }

    public function resolve(Container $container, array $parameterOverrides = []): mixed
    {
        return $container->instantiate($this->className, [...$this->parameters, ...$parameterOverrides]);
    }
}
