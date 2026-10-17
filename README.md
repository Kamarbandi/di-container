# azad/container

A PSR-11 dependency injection container with constructor autowiring, mutable service definitions, singleton/factory scopes, service providers, tags, and delegate lookup.

Requires PHP `8.1+`. Zero runtime dependencies beyond `psr/container: ^2.0`.

Author: Azad Kamarbandi

## Install

```bash
composer require azad/container
```

## Quick start

Plain classes are autowired without any configuration:

```php
use Azad\Container\Container;

final class Logger
{
}

final class UserService
{
    public function __construct(private readonly Logger $logger)
    {
    }
}

$container = new Container();

$service = $container->get(UserService::class); // Logger is resolved automatically
```

## Registering services

### `set()` — a raw value, a Closure factory, or a `DefinitionInterface`

```php
$container->set('project.root', '/opt/app');
$container->set(Logger::class, fn (Container $c) => new Logger($c->get('project.root')));
```

A `Closure` passed to `set()` acts as a **shared** factory: it runs once, and the result is cached.

### `singleton()` / `factory()` — explicit scope control

```php
$container->singleton(Clock::class);                     // autowired, cached for the container's lifetime
$container->singleton(Cache::class, fn () => new RedisCache(...));

$container->factory(RequestId::class, fn () => new RequestId(Uuid::v4()));
// every get(RequestId::class) call returns a new instance
```

### `bind()` — interface to implementation

```php
$container->bind(LoggerInterface::class, FileLogger::class);

$container->get(LoggerInterface::class); // an autowired FileLogger
```

### `alias()` — an alternate name for an existing entry

```php
$container->alias('logger', LoggerInterface::class);
```

### `define()` — override a single constructor parameter

```php
final class LoggerFactory
{
    public function __construct(
        private readonly Environment $env,
        private readonly Clock $clock,
        private readonly string $targetDir = '/var/log/app',
    ) {
    }
}

$container->define(LoggerFactory::class)->parameter('targetDir', '/var/log/production');
```

Everything else keeps resolving through autowiring; only the named parameter is overridden.

### `#[Inject]` — attribute-based injection for scalars and config

```php
use Azad\Container\Attribute\Inject;

final class LoggerFactory
{
    public function __construct(
        #[Inject('config.log_dir')]
        private readonly string $targetDir,
    ) {
    }
}

$container->set('config.log_dir', getenv('LOG_DIR') ?: '/var/log/app');
```

### `extend()` — decorate an already-registered entry

```php
$container->set(LoggerInterface::class, fn () => new FileLogger());
$container->extend(LoggerInterface::class, fn (LoggerInterface $logger) => new BufferedLogger($logger));
```

### `make()` — build fresh, bypassing the shared cache

```php
$container->get(Report::class);                          // shared, as usual
$container->make(Report::class, ['year' => 2025]);        // always a new instance
```

### Tags

```php
$container->tag(ProcessInventories::class, ['processing.routine']);
$container->tag(ProcessAvailabilities::class, ['processing.routine']);

foreach ($container->tagged('processing.routine') as $routine) {
    // ...
}
```

### Service providers with lazy registration

```php
use Azad\Container\ServiceProvider\ServiceProviderInterface;

final class TwigServiceProvider implements ServiceProviderInterface
{
    public function provides(): array
    {
        return [Twig::class];
    }

    public function register(Container $container): void
    {
        $container->singleton(Twig::class, fn (Container $c) => (new TwigFactory($c))->create());
    }
}

$container = new Container(providers: [new TwigServiceProvider()]);
// register() only runs on the first $container->get(Twig::class) call
```

A provider with an empty `provides()` array registers eagerly — useful for providers whose job is a side effect (e.g. only tagging entries).

### Delegate lookup

```php
$container->delegate($frameworkContainer);
// entries not found locally fall back to $frameworkContainer
```

## Autowiring behavior

* Classes and interfaces with an explicit `bind()` are resolved recursively and cached as a singleton unless overridden.
* Scalar parameters with a default value fall back to that default.
* Nullable parameters (`?Foo $x`) that can't be resolved get `null`.
* Union types (`Foo|Bar $x`) are tried in declaration order; the first resolvable type wins.
* Variadic parameters (`Foo ...$items`) default to an empty list; override them via `make($id, ['items' => [...]])` or `define()->parameter('items', [...])`.
* Untyped parameters only resolve if they have a default value.
* Intersection types (`Foo&Bar`) aren't autowired — they raise a clear `UnresolvableParameterException` instead of a fatal error.
* Circular dependencies (constructor chains, factory closures, and aliases) are detected and reported with the full resolution chain.

Setter/property injection is intentionally not supported — the library is built around constructor injection and `readonly` classes.

## Exceptions

Every exception implements `Psr\Container\ContainerExceptionInterface` (or `NotFoundExceptionInterface` for `NotFoundException`).

* `NotFoundException` — the entry doesn't exist (`has()` would return `false`).
* `CircularDependencyException` — a circular dependency was detected.
* `UnresolvableParameterException` — a constructor parameter can't be resolved.
* `FrozenServiceException` — attempted to redefine an already-resolved shared service (use `extend()` instead).
* `InvalidDefinitionException` — e.g. `extend()` called on an entry that was never registered.
* `ContainerException` — the general case (instantiation failure, an explicit binding to a non-instantiable class, etc.).

## Development

```bash
composer install
composer test   # composer validate + phpcs + phpstan (level 8) + phpunit
```

## License

MIT © Azad Kamarbandi. See [LICENSE.md](LICENSE.md).
