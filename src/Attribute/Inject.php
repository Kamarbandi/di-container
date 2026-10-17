<?php

declare(strict_types=1);

namespace Azad\Container\Attribute;

use Attribute;

/**
 * Explicitly binds a constructor parameter to a container entry id,
 * mainly useful for scalar/config values that autowiring can't guess:
 *
 *     public function __construct(
 *         #[Inject('config.log_dir')] private string $logDir,
 *     ) {}
 *
 *     $container->set('config.log_dir', '/var/log/app');
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class Inject
{
    public function __construct(public readonly string $id)
    {
    }
}
