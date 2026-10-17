<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

final class VariadicService
{
    /** @var list<OtherService> */
    public readonly array $rest;

    public function __construct(public readonly SimpleService $first, OtherService ...$rest)
    {
        $this->rest = array_values($rest);
    }
}
