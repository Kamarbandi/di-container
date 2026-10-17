<?php

declare(strict_types=1);

namespace Azad\Container\Tests;

use PHPUnit\Framework\TestCase;
use Azad\Container\Container;
use Azad\Container\Tests\Fixtures\EnglishGreeter;
use Azad\Container\Tests\Fixtures\FrenchGreeter;
use Azad\Container\Tests\Fixtures\GreeterInterface;
use Azad\Container\Tests\Fixtures\Greeting;
use Azad\Container\Tests\Fixtures\ServiceWithRequiredScalar;
use Azad\Container\Tests\Fixtures\SimpleService;

final class DefinitionsTest extends TestCase
{
    public function testSetStoresARawValueVerbatim(): void
    {
        $container = new Container();
        $container->set('project.root', '/opt/app');

        self::assertSame('/opt/app', $container->get('project.root'));
    }

    public function testSetAcceptsAClosureAsASharedFactory(): void
    {
        $container = new Container();
        $container->set(SimpleService::class, static fn (Container $c): SimpleService => new SimpleService());

        self::assertSame($container->get(SimpleService::class), $container->get(SimpleService::class));
    }

    public function testFactoryBuildsANewInstanceOnEveryCall(): void
    {
        $container = new Container();
        $container->factory(SimpleService::class, static fn (): SimpleService => new SimpleService());

        self::assertNotSame($container->get(SimpleService::class), $container->get(SimpleService::class));
    }

    public function testSingletonWithoutConcreteAutowiresAndCachesItself(): void
    {
        $container = new Container();
        $container->singleton(SimpleService::class);

        self::assertSame($container->get(SimpleService::class), $container->get(SimpleService::class));
    }

    public function testBindResolvesAnInterfaceToAConcreteAutowiredClass(): void
    {
        $container = new Container();
        $container->bind(GreeterInterface::class, EnglishGreeter::class);

        $greeting = $container->get(Greeting::class);

        self::assertInstanceOf(EnglishGreeter::class, $greeting->greeter);
        self::assertSame('Hello', $greeting->greeter->greet());
    }

    public function testBindCanBeSwappedForADifferentImplementation(): void
    {
        $container = new Container();
        $container->bind(GreeterInterface::class, FrenchGreeter::class);

        self::assertSame('Bonjour', $container->get(GreeterInterface::class)->greet());
    }

    public function testAliasResolvesThroughAnotherId(): void
    {
        $container = new Container();
        $container->bind(GreeterInterface::class, EnglishGreeter::class);
        $container->alias('greeter', GreeterInterface::class);

        self::assertSame($container->get(GreeterInterface::class), $container->get('greeter'));
    }

    public function testDefineAllowsOverridingAConstructorParameterByName(): void
    {
        $container = new Container();
        $container->define(ServiceWithRequiredScalar::class)->parameter('apiKey', 'secret-key');

        self::assertSame('secret-key', $container->get(ServiceWithRequiredScalar::class)->apiKey);
    }

    public function testMakeBypassesTheSharedCache(): void
    {
        $container = new Container();

        $first = $container->get(SimpleService::class);
        $second = $container->make(SimpleService::class);

        self::assertNotSame($first, $second);
        // The cached singleton is untouched by make().
        self::assertSame($first, $container->get(SimpleService::class));
    }

    public function testMakeAcceptsParameterOverrides(): void
    {
        $container = new Container();

        $service = $container->make(ServiceWithRequiredScalar::class, ['apiKey' => 'one-off-key']);

        self::assertSame('one-off-key', $service->apiKey);
    }

    public function testMakeThroughAnAliasBypassesTheSharedCache(): void
    {
        $container = new Container();
        $container->singleton(SimpleService::class);
        $container->alias('svc', SimpleService::class);

        $shared = $container->get('svc');
        $fresh = $container->make('svc');

        self::assertInstanceOf(SimpleService::class, $fresh);
        self::assertNotSame($shared, $fresh);
        self::assertSame($shared, $container->get('svc'));
    }

    public function testConstructorAcceptsAnIterableOfDefinitions(): void
    {
        $definitions = (static function (): iterable {
            yield 'project.root' => '/opt/app';
            yield GreeterInterface::class => static fn (): GreeterInterface => new EnglishGreeter();
        })();

        $container = new Container($definitions);

        self::assertSame('/opt/app', $container->get('project.root'));
        self::assertSame('Hello', $container->get(GreeterInterface::class)->greet());
    }
}
