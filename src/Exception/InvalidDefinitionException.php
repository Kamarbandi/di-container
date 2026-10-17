<?php

declare(strict_types=1);

namespace Azad\Container\Exception;

use function sprintf;

/**
 * Thrown when a definition is used in a way that doesn't make sense,
 * e.g. extending an entry that was never registered.
 */
class InvalidDefinitionException extends ContainerException
{
    public static function noSuchEntry(string $id): self
    {
        return new self(sprintf('Cannot extend "%s": no definition has been registered for it.', $id));
    }
}
