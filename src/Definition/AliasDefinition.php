<?php

declare(strict_types=1);

namespace Azad\Container\Definition;

use Azad\Container\Container;

/**
 * Makes one entry id resolve through another. Caching is delegated to the
 * target's own definition, so the alias itself is never cached.
 */
final class AliasDefinition implements DefinitionInterface
{
    public function __construct(private readonly string $target)
    {
    }

    public function target(): string
    {
        return $this->target;
    }

    public function isShared(): bool
    {
        return false;
    }

    public function resolve(Container $container, array $parameterOverrides = []): mixed
    {
        return $container->get($this->target);
    }
}
