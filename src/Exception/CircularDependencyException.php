<?php

declare(strict_types=1);

namespace Azad\Container\Exception;

use function implode;
use function sprintf;

/**
 * Thrown when resolving an entry would require resolving itself again.
 */
class CircularDependencyException extends ContainerException
{
    /**
     * @param list<string> $stack Classes currently being resolved, in resolution order.
     */
    public static function forStack(array $stack, string $target): self
    {
        $trace = implode(' -> ', [...$stack, $target]);

        return new self(sprintf(
            'Circular dependency detected while resolving "%s": %s',
            $target,
            $trace
        ));
    }
}
