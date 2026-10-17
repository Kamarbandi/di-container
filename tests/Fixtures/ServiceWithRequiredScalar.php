<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

final class ServiceWithRequiredScalar
{
    public function __construct(public readonly string $apiKey)
    {
    }
}
