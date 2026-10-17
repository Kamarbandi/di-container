<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

final class FrenchGreeter implements GreeterInterface
{
    public function greet(): string
    {
        return 'Bonjour';
    }
}
