<?php

declare(strict_types=1);

namespace Azad\Container\Exception;

use function sprintf;

/**
 * Thrown when trying to redefine an entry that has already been resolved
 * as a shared service. Use Container::extend() to decorate it instead.
 */
class FrozenServiceException extends ContainerException
{
    public static function forId(string $id): self
    {
        return new self(sprintf(
            'Cannot redefine "%s": it has already been resolved as a shared service. ' .
            'Use extend() to decorate it instead.',
            $id
        ));
    }
}
