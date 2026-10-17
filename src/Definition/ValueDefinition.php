<?php

declare(strict_types=1);

namespace Azad\Container\Definition;

use Azad\Container\Container;

/**
 * Wraps a raw, already-built value (scalar, array, object) so it can be
 * returned as-is on every lookup.
 */
final class ValueDefinition implements DefinitionInterface
{
    public function __construct(private readonly mixed $value)
    {
    }

    public function isShared(): bool
    {
        return true;
    }

    public function resolve(Container $container, array $parameterOverrides = []): mixed
    {
        return $this->value;
    }
}
