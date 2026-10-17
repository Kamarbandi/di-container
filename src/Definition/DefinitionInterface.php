<?php

declare(strict_types=1);

namespace Azad\Container\Definition;

use Azad\Container\Container;

/**
 * A recipe for building a single container entry.
 */
interface DefinitionInterface
{
    /**
     * Whether the resolved value should be cached and reused for subsequent
     * get() calls (true), or rebuilt on every call (false).
     */
    public function isShared(): bool;

    /**
     * @param array<string, mixed> $parameterOverrides Named constructor-parameter overrides, used by make().
     * @param bool $fresh When true (make()), aliases must bypass the shared cache of their target.
     */
    public function resolve(Container $container, array $parameterOverrides = [], bool $fresh = false): mixed;
}
