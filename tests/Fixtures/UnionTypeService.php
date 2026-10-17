<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

final class UnionTypeService
{
    public function __construct(public readonly SimpleService|OtherService $dependency)
    {
    }
}
