<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

final class EnglishGreeter implements GreeterInterface
{
    public function greet(): string
    {
        return 'Hello';
    }
}
