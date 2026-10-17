<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

final class ServiceWithScalarDefault
{
    public function __construct(public readonly string $targetDir = '/tmp/default')
    {
    }
}
