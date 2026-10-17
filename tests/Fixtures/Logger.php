<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

final class Logger
{
    public function __construct(public readonly SimpleService $service)
    {
    }
}
