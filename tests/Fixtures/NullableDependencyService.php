<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

final class NullableDependencyService
{
    public function __construct(public readonly ?UnboundInterface $thing)
    {
    }
}
