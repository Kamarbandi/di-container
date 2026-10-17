<?php

declare(strict_types=1);

namespace Azad\Container\Exception;

use ReflectionParameter;

use function sprintf;

/**
 * Thrown when a constructor parameter cannot be autowired and has no
 * explicit override, default value or #[Inject] attribute.
 */
class UnresolvableParameterException extends ContainerException
{
    public static function forParameter(string $declaringClass, ReflectionParameter $param, string $reason): self
    {
        $message = sprintf(
            'Unable to resolve parameter $%s (position %d) of %s::__construct(): %s',
            $param->getName(),
            $param->getPosition() + 1,
            $declaringClass,
            $reason
        );

        return new self($message);
    }
}
