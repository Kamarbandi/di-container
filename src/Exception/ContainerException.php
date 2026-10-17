<?php

declare(strict_types=1);

namespace Azad\Container\Exception;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Base exception for every error raised by the container.
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
}
