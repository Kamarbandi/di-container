<?php

declare(strict_types=1);

namespace Azad\Container\Tests;

use PHPUnit\Framework\TestCase;
use Azad\Container\Container;
use Azad\Container\Exception\ContainerException;
use Azad\Container\Exception\FrozenServiceException;
use Azad\Container\Exception\InvalidDefinitionException;
use Azad\Container\Tests\Fixtures\AbstractThing;
use Azad\Container\Tests\Fixtures\SimpleService;

final class ExceptionsTest extends TestCase
{
    public function testRedefiningAnAlreadyResolvedSharedServiceThrows(): void
    {
        $container = new Container();
        $container->set('value', 'first');
        $container->get('value');

        $this->expectException(FrozenServiceException::class);

        $container->set('value', 'second');
    }

    public function testANonSharedDefinitionCanBeFreelyRedefinedAfterUse(): void
    {
        $container = new Container();
        $container->factory('value', static fn (): string => 'first');
        $container->get('value');

        $container->factory('value', static fn (): string => 'second');

        self::assertSame('second', $container->get('value'));
    }

    public function testExtendingAnUnknownEntryThrows(): void
    {
        $container = new Container();

        $this->expectException(InvalidDefinitionException::class);

        $container->extend('unknown', static fn (mixed $v): mixed => $v);
    }

    public function testExtendDecoratesThePreviouslyRegisteredValue(): void
    {
        $container = new Container();
        $container->set('greeting', 'Hello');
        $container->extend('greeting', static fn (string $previous): string => $previous . ', world!');

        self::assertSame('Hello, world!', $container->get('greeting'));
    }

    public function testInstantiatingANonInstantiableExplicitBindingThrowsAContainerException(): void
    {
        $container = new Container();
        // Binding an abstract-like target explicitly is a configuration
        // mistake, and must surface as ContainerException, not silently pass.
        $container->bind('broken', AbstractThing::class);

        $this->expectException(ContainerException::class);

        $container->get('broken');
    }

    public function testGetOnAPlainScalarValueIsNotAffectedByAutowiring(): void
    {
        $container = new Container();
        $container->set(SimpleService::class, 'not-actually-a-service');

        self::assertSame('not-actually-a-service', $container->get(SimpleService::class));
    }
}
