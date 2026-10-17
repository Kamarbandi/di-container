<?php

declare(strict_types=1);

namespace Azad\Container\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Azad\Container\Container;
use Azad\Container\Exception\NotFoundException;
use Azad\Container\Tests\Fixtures\AbstractThing;
use Azad\Container\Tests\Fixtures\SimpleService;
use Azad\Container\Tests\Fixtures\UnboundInterface;

final class GetHasTest extends TestCase
{
    public function testImplementsPsr11(): void
    {
        $container = new Container();

        self::assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testResolvesItself(): void
    {
        $container = new Container();

        self::assertSame($container, $container->get(ContainerInterface::class));
        self::assertSame($container, $container->get(Container::class));
        self::assertTrue($container->has(ContainerInterface::class));
        self::assertTrue($container->has(Container::class));
    }

    public function testAutowiresAPlainClassWithoutAnyConfiguration(): void
    {
        $container = new Container();

        $instance = $container->get(SimpleService::class);

        self::assertInstanceOf(SimpleService::class, $instance);
    }

    public function testGetIsASingletonByDefault(): void
    {
        $container = new Container();

        self::assertSame($container->get(SimpleService::class), $container->get(SimpleService::class));
    }

    public function testHasReturnsFalseForUnknownId(): void
    {
        $container = new Container();

        self::assertFalse($container->has('unknown.service'));
    }

    public function testGetThrowsNotFoundExceptionForUnknownId(): void
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectException(ContainerExceptionInterface::class);

        $container->get('unknown.service');
    }

    public function testHasIsFalseForAnInterfaceWithNoBinding(): void
    {
        $container = new Container();

        self::assertFalse($container->has(UnboundInterface::class));
    }

    public function testGetThrowsNotFoundExceptionForAnUnboundInterfaceConsistentlyWithHas(): void
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);

        $container->get(UnboundInterface::class);
    }

    public function testHasIsFalseForAnAbstractClass(): void
    {
        $container = new Container();

        self::assertFalse($container->has(AbstractThing::class));
    }

    public function testGetThrowsNotFoundExceptionForAnAbstractClassConsistentlyWithHas(): void
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);

        $container->get(AbstractThing::class);
    }
}
