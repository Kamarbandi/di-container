<?php

declare(strict_types=1);

namespace Azad\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Thrown by get() when no definition, class or delegate can provide the requested id.
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
