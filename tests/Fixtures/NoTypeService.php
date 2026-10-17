<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

final class NoTypeService
{
    /**
     * Deliberately untyped natively (no default value either), to exercise
     * the container's "no type declaration at all" resolution branch —
     * ReflectionParameter::getType() must return null for this to hold.
     *
     * @param mixed $value
     */
    public function __construct(public $value)
    {
    }
}
