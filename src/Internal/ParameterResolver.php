<?php

declare(strict_types=1);

namespace Azad\Container\Internal;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Azad\Container\Attribute\Inject;
use Azad\Container\Container;
use Azad\Container\Exception\UnresolvableParameterException;

use function array_key_exists;
use function implode;
use function sprintf;

/**
 * Resolves a single constructor parameter to a value, in this order:
 *
 *   1. an explicit override passed to instantiate()/make() by name;
 *   2. an #[Inject('id')] attribute, fetched from the container by that id;
 *   3. a variadic parameter, which resolves to an empty list;
 *   4. the parameter's type: union types try each member in turn, class/
 *      interface types are resolved recursively via the container, builtin
 *      scalar types fall back to their default value or null;
 *   5. the parameter's own default value, if none of the above applied;
 *   6. failure, with a message naming the exact parameter and reason.
 *
 * @internal
 */
final class ParameterResolver
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param class-string $declaringClass
     * @param array<string, mixed> $overrides
     */
    public function resolve(string $declaringClass, ReflectionParameter $param, array $overrides): mixed
    {
        $name = $param->getName();

        if (array_key_exists($name, $overrides)) {
            return $overrides[$name];
        }

        foreach ($param->getAttributes(Inject::class) as $attribute) {
            /** @var Inject $inject */
            $inject = $attribute->newInstance();

            return $this->container->get($inject->id);
        }

        if ($param->isVariadic()) {
            return [];
        }

        $type = $param->getType();

        if ($type instanceof ReflectionUnionType) {
            return $this->resolveUnion($declaringClass, $param, $type);
        }

        if ($type instanceof ReflectionIntersectionType) {
            return $this->fail(
                $declaringClass,
                $param,
                'intersection types cannot be autowired; provide an explicit definition or #[Inject].'
            );
        }

        if ($type instanceof ReflectionNamedType) {
            return $this->resolveNamed($declaringClass, $param, $type);
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        return $this->fail($declaringClass, $param, 'it has no type declaration and no default value.');
    }

    private function resolveNamed(string $declaringClass, ReflectionParameter $param, ReflectionNamedType $type): mixed
    {
        $typeName = $type->getName();

        if (!$type->isBuiltin()) {
            /** @var class-string $typeName */
            if ($this->container->has($typeName)) {
                return $this->container->get($typeName);
            }

            if ($type->allowsNull()) {
                return null;
            }

            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            return $this->fail(
                $declaringClass,
                $param,
                sprintf('no entry or class found for "%s".', $typeName)
            );
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($type->allowsNull()) {
            return null;
        }

        return $this->fail(
            $declaringClass,
            $param,
            sprintf(
                'it is a built-in "%s" parameter without a default value; bind it explicitly with parameter()' .
                ' or #[Inject].',
                $typeName
            )
        );
    }

    private function resolveUnion(string $declaringClass, ReflectionParameter $param, ReflectionUnionType $type): mixed
    {
        $names = [];

        foreach ($type->getTypes() as $namedType) {
            if (!$namedType instanceof ReflectionNamedType) {
                // Nested intersection member of a DNF type (PHP 8.2+) — not autowirable.
                continue;
            }

            $typeName = $namedType->getName();

            if ($typeName === 'null') {
                continue;
            }

            $names[] = $typeName;

            if (!$namedType->isBuiltin() && $this->container->has($typeName)) {
                return $this->container->get($typeName);
            }
        }

        if ($type->allowsNull()) {
            return null;
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        return $this->fail(
            $declaringClass,
            $param,
            sprintf('none of the union types (%s) could be resolved.', implode('|', $names))
        );
    }

    private function fail(string $declaringClass, ReflectionParameter $param, string $reason): never
    {
        throw UnresolvableParameterException::forParameter($declaringClass, $param, $reason);
    }
}
