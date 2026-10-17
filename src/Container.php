<?php

declare(strict_types=1);

namespace Azad\Container;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionException;
use Throwable;
use Azad\Container\Definition\AliasDefinition;
use Azad\Container\Definition\AutowireDefinition;
use Azad\Container\Definition\DefinitionInterface;
use Azad\Container\Definition\FactoryDefinition;
use Azad\Container\Definition\ValueDefinition;
use Azad\Container\Exception\CircularDependencyException;
use Azad\Container\Exception\ContainerException;
use Azad\Container\Exception\FrozenServiceException;
use Azad\Container\Exception\InvalidDefinitionException;
use Azad\Container\Exception\NotFoundException;
use Azad\Container\Internal\ParameterResolver;
use Azad\Container\Internal\ReflectionCache;
use Azad\Container\ServiceProvider\ServiceProviderInterface;

use function array_key_exists;
use function array_map;
use function array_pop;
use function array_push;
use function class_exists;
use function in_array;
use function interface_exists;
use function is_string;
use function sprintf;

/**
 * A PSR-11 container with constructor autowiring, mutable definitions,
 * singleton/factory scopes, service providers, tags and delegate lookup.
 *
 * @see https://www.php-fig.org/psr/psr-11/
 */
class Container implements PsrContainerInterface
{
    /** @var array<string, DefinitionInterface> */
    private array $definitions = [];

    /** @var array<string, mixed> */
    private array $resolved = [];

    /** @var array<string, true> */
    private array $frozen = [];

    /** @var array<string, list<string>> */
    private array $tags = [];

    /** @var list<string> Entry ids and class names currently being built. */
    private array $resolutionStack = [];

    /** @var list<PsrContainerInterface> */
    private array $delegates = [];

    /** @var array<string, ServiceProviderInterface> */
    private array $deferredProviders = [];

    private readonly ParameterResolver $parameterResolver;

    /**
     * @param iterable<string, mixed> $definitions Raw values, Closures, class-strings via bind()/define(),
     *                                              or DefinitionInterface instances.
     * @param iterable<ServiceProviderInterface> $providers
     */
    public function __construct(iterable $definitions = [], iterable $providers = [])
    {
        $this->parameterResolver = new ParameterResolver($this);

        foreach ($definitions as $id => $definition) {
            $this->set($id, $definition);
        }

        foreach ($providers as $provider) {
            $this->addServiceProvider($provider);
        }
    }

    // -----------------------------------------------------------------
    // PSR-11
    // -----------------------------------------------------------------

    /**
     * @template T of object
     *
     * @param string|class-string<T> $id
     *
     * @return ($id is class-string<T> ? T : mixed)
     */
    public function get(string $id): mixed
    {
        if ($id === PsrContainerInterface::class || $id === self::class || $id === static::class) {
            return $this;
        }

        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        $this->bootDeferredProvider($id);

        if (isset($this->definitions[$id])) {
            return $this->resolveRegistered($id, $this->definitions[$id], []);
        }

        if ($this->isAutowirable($id)) {
            /** @var class-string $id */
            // instantiate() owns the stack entry for the class name.
            $instance = $this->instantiate($id, []);
            $this->resolved[$id] = $instance;
            $this->frozen[$id] = true;

            return $instance;
        }

        foreach ($this->delegates as $delegate) {
            if ($delegate->has($id)) {
                return $delegate->get($id);
            }
        }

        throw new NotFoundException(sprintf('No entry or class found for "%s".', $id));
    }

    public function has(string $id): bool
    {
        if ($id === PsrContainerInterface::class || $id === self::class || $id === static::class) {
            return true;
        }

        $known = array_key_exists($id, $this->resolved)
            || isset($this->definitions[$id])
            || isset($this->deferredProviders[$id]);

        if ($known) {
            return true;
        }

        if ($this->isAutowirable($id)) {
            return true;
        }

        foreach ($this->delegates as $delegate) {
            if ($delegate->has($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether $id names a class/interface that can be built by autowiring
     * alone, with no explicit definition. Kept consistent between has()
     * and get() so has() === false always implies get() throws NotFoundException.
     */
    private function isAutowirable(string $id): bool
    {
        if (!class_exists($id) && !interface_exists($id)) {
            return false;
        }

        try {
            return ReflectionCache::forClass($id)->isInstantiable();
        } catch (ReflectionException) {
            return false;
        }
    }

    /**
     * Builds a fresh instance, bypassing (and not populating) the shared
     * service cache. Useful for transient objects or one-off overrides.
     *
     * @template T of object
     *
     * @param string|class-string<T> $id
     * @param array<string, mixed> $parameters Named constructor-parameter overrides.
     *
     * @return ($id is class-string<T> ? T : mixed)
     */
    public function make(string $id, array $parameters = []): mixed
    {
        if ($id === PsrContainerInterface::class || $id === self::class || $id === static::class) {
            return $this;
        }

        $this->bootDeferredProvider($id);

        if (isset($this->definitions[$id])) {
            return $this->resolveRegistered($id, $this->definitions[$id], $parameters, cache: false);
        }

        if ($this->isAutowirable($id)) {
            /** @var class-string $id */
            return $this->instantiate($id, $parameters);
        }

        foreach ($this->delegates as $delegate) {
            if (!$delegate->has($id)) {
                continue;
            }

            // Prefer make() on our own type so parameter overrides and cache
            // bypass propagate; fall back to get() for arbitrary PSR-11 delegates.
            return $delegate instanceof self
                ? $delegate->make($id, $parameters)
                : $delegate->get($id);
        }

        throw new NotFoundException(sprintf('No entry or class found for "%s".', $id));
    }

    // -----------------------------------------------------------------
    // Configuration
    // -----------------------------------------------------------------

    /**
     * Registers a raw value, a Closure factory (treated as a shared
     * factory, invoked once), or a DefinitionInterface under $id.
     */
    public function set(string $id, mixed $definition): static
    {
        if (isset($this->frozen[$id])) {
            throw FrozenServiceException::forId($id);
        }

        $this->definitions[$id] = $this->normalize($definition);
        unset($this->resolved[$id]);

        return $this;
    }

    /**
     * Registers a shared (singleton) service: built once, on first use,
     * then cached for the container's lifetime.
     *
     * @param callable|class-string|null $concrete Defaults to $id itself (autowired).
     */
    public function singleton(string $id, callable|string|null $concrete = null): static
    {
        $concrete ??= $id;

        if (is_string($concrete)) {
            /** @var class-string $concrete */
            return $this->set($id, new AutowireDefinition($concrete, shared: true));
        }

        return $this->set($id, new FactoryDefinition(Closure::fromCallable($concrete), shared: true));
    }

    /**
     * Registers a factory that runs anew on every get()/make() call
     * (a "transient" or non-shared service).
     */
    public function factory(string $id, callable $factory): static
    {
        return $this->set($id, new FactoryDefinition(Closure::fromCallable($factory), shared: false));
    }

    /**
     * Binds an abstract identifier (typically an interface) to a concrete,
     * autowired class, resolved and cached as a shared service.
     *
     * @param class-string $concrete
     */
    public function bind(string $abstract, string $concrete): static
    {
        return $this->set($abstract, new AutowireDefinition($concrete, shared: true));
    }

    /**
     * Makes $alias resolve through another, already- (or later-) registered id.
     */
    public function alias(string $alias, string $id): static
    {
        return $this->set($alias, new AliasDefinition($id));
    }

    /**
     * Declares an autowired class definition and returns it for fluent
     * parameter overrides:
     *
     *     $container->define(Logger::class)->parameter('targetDir', '/var/log/app');
     *
     * @param class-string|null $class Defaults to $id itself.
     */
    public function define(string $id, ?string $class = null): AutowireDefinition
    {
        /** @var class-string $className */
        $className = $class ?? $id;
        $definition = new AutowireDefinition($className);
        $this->set($id, $definition);

        return $definition;
    }

    /**
     * Decorates an already-registered definition: the extender receives the
     * value the original definition would have produced, and returns the
     * (possibly wrapped) replacement.
     */
    public function extend(string $id, Closure $extender): static
    {
        if (!isset($this->definitions[$id])) {
            throw InvalidDefinitionException::noSuchEntry($id);
        }

        if (isset($this->frozen[$id])) {
            throw FrozenServiceException::forId($id);
        }

        $previous = $this->definitions[$id];
        $shared = $previous->isShared();

        $this->definitions[$id] = new FactoryDefinition(
            static fn (Container $c): mixed => $extender($previous->resolve($c), $c),
            $shared,
        );

        return $this;
    }

    /**
     * @param string[] $tags
     */
    public function tag(string $id, array $tags): static
    {
        foreach ($tags as $tag) {
            $this->tags[$tag][] = $id;
        }

        return $this;
    }

    /**
     * @return list<mixed> Resolved services registered under $tag, in registration order.
     */
    public function tagged(string $tag): array
    {
        $ids = $this->tags[$tag] ?? [];

        return array_map(fn (string $id): mixed => $this->get($id), $ids);
    }

    /**
     * Adds a fallback container consulted (in registration order) when an
     * entry can't be found locally.
     */
    public function delegate(PsrContainerInterface $container): static
    {
        $this->delegates[] = $container;

        return $this;
    }

    /**
     * Registers a service provider. If provides() is non-empty, registration
     * is deferred until one of those ids is first requested; otherwise it
     * runs immediately.
     */
    public function addServiceProvider(ServiceProviderInterface $provider): static
    {
        $provides = $provider->provides();

        if ($provides === []) {
            $provider->register($this);

            return $this;
        }

        foreach ($provides as $id) {
            $this->deferredProviders[$id] = $provider;
        }

        return $this;
    }

    // -----------------------------------------------------------------
    // Internals — public so Definition objects (same package) can call
    // back into the container; not part of the supported API surface.
    // -----------------------------------------------------------------

    /**
     * @internal
     *
     * @param class-string $class
     * @param array<string, mixed> $parameterOverrides
     */
    public function instantiate(string $class, array $parameterOverrides): object
    {
        if (in_array($class, $this->resolutionStack, true)) {
            throw CircularDependencyException::forStack($this->resolutionStack, $class);
        }

        try {
            $reflection = ReflectionCache::forClass($class);
        } catch (ReflectionException $exception) {
            throw new ContainerException(sprintf('Failed to reflect class "%s".', $class), 0, $exception);
        }

        if (!$reflection->isInstantiable()) {
            throw new ContainerException(sprintf('"%s" is not instantiable.', $class));
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            try {
                return new $class();
            } catch (Throwable $exception) {
                throw new ContainerException(sprintf('Failed to instantiate "%s".', $class), 0, $exception);
            }
        }

        $this->resolutionStack[] = $class;

        try {
            $arguments = [];
            foreach ($constructor->getParameters() as $parameter) {
                $value = $this->parameterResolver->resolve($class, $parameter, $parameterOverrides);

                if ($parameter->isVariadic()) {
                    // $value is always a list (see ParameterResolver) — spread it
                    // positionally instead of nesting it as a single argument.
                    array_push($arguments, ...$value);
                } else {
                    $arguments[] = $value;
                }
            }

            return new $class(...$arguments);
        } catch (ContainerExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ContainerException(sprintf('Failed to instantiate "%s".', $class), 0, $exception);
        } finally {
            array_pop($this->resolutionStack);
        }
    }

    /**
     * Resolves a registered definition. Factories and aliases are tracked on
     * the resolution stack by entry id; AutowireDefinition is not — instantiate()
     * already pushes the concrete class, and pushing the same id twice would
     * false-positive when $id === $className (e.g. singleton(Foo::class)).
     *
     * @param array<string, mixed> $overrides
     */
    private function resolveRegistered(
        string $id,
        DefinitionInterface $definition,
        array $overrides,
        bool $cache = true
    ): mixed {
        if ($definition instanceof AutowireDefinition) {
            return $this->resolveDefinition($id, $definition, $overrides, $cache);
        }

        return $this->whileResolving(
            $id,
            fn (): mixed => $this->resolveDefinition($id, $definition, $overrides, $cache)
        );
    }

    /**
     * Pushes $id onto the resolution stack for the duration of $callback so
     * circular lookups through factories, aliases and nested get()/make()
     * calls are detected before they recurse unboundedly.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function whileResolving(string $id, callable $callback): mixed
    {
        if (in_array($id, $this->resolutionStack, true)) {
            throw CircularDependencyException::forStack($this->resolutionStack, $id);
        }

        $this->resolutionStack[] = $id;

        try {
            return $callback();
        } finally {
            array_pop($this->resolutionStack);
        }
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function resolveDefinition(
        string $id,
        DefinitionInterface $definition,
        array $overrides,
        bool $cache = true
    ): mixed {
        if (!$cache || !$definition->isShared()) {
            return $definition->resolve($this, $overrides, fresh: !$cache);
        }

        $value = $definition->resolve($this, $overrides, fresh: false);
        $this->resolved[$id] = $value;
        $this->frozen[$id] = true;

        return $value;
    }

    private function bootDeferredProvider(string $id): void
    {
        $provider = $this->deferredProviders[$id] ?? null;

        if ($provider === null) {
            return;
        }

        foreach ($provider->provides() as $providedId) {
            unset($this->deferredProviders[$providedId]);
        }

        $provider->register($this);
    }

    private function normalize(mixed $definition): DefinitionInterface
    {
        if ($definition instanceof DefinitionInterface) {
            return $definition;
        }

        if ($definition instanceof Closure) {
            return new FactoryDefinition($definition, shared: true);
        }

        return new ValueDefinition($definition);
    }
}
