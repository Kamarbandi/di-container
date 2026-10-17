<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

final class Greeting
{
    public function __construct(public readonly GreeterInterface $greeter)
    {
    }
}
