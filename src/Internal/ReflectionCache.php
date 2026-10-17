<?php

declare(strict_types=1);

namespace Azad\Container\Internal;

use ReflectionClass;
use ReflectionException;

/**
 * Process-wide cache of ReflectionClass instances, so repeated get()/make()
 * calls for the same class don't pay the reflection cost more than once.
 *
 * @internal
 */
final class ReflectionCache
{
    /** @var array<class-string, ReflectionClass<object>> */
    private static array $classes = [];

    /**
     * @param class-string $class
     *
     * @return ReflectionClass<object>
     * @throws ReflectionException
     */
    public static function forClass(string $class): ReflectionClass
    {
        return self::$classes[$class] ??= new ReflectionClass($class);
    }
}
