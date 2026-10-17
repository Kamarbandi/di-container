<?php

declare(strict_types=1);

namespace Azad\Container\Tests;

use PHPUnit\Framework\TestCase;
use Azad\Container\Container;
use Azad\Container\Exception\NotFoundException;
use Azad\Container\Tests\Fixtures\SimpleService;

final class DelegateTest extends TestCase
{
    public function testFallsBackToADelegateContainerWhenNotFoundLocally(): void
    {
        $delegate = new Container();
        $delegate->set('shared.value', 'from-delegate');

        $container = new Container();
        $container->delegate($delegate);

        self::assertTrue($container->has('shared.value'));
        self::assertSame('from-delegate', $container->get('shared.value'));
    }

    public function testLocalDefinitionsTakePriorityOverDelegates(): void
    {
        $delegate = new Container();
        $delegate->set('shared.value', 'from-delegate');

        $container = new Container();
        $container->delegate($delegate);
        $container->set('shared.value', 'from-local');

        self::assertSame('from-local', $container->get('shared.value'));
    }

    public function testStillThrowsWhenNoDelegateHasTheEntry(): void
    {
        $container = new Container();
        $container->delegate(new Container());

        $this->expectException(NotFoundException::class);

        $container->get('nowhere.to.be.found');
    }

    public function testMakeFallsBackToADelegateContainerWhenNotFoundLocally(): void
    {
        $delegate = new Container();
        $delegate->set('shared.value', 'from-delegate');

        $container = new Container();
        $container->delegate($delegate);

        self::assertSame('from-delegate', $container->make('shared.value'));
    }

    public function testMakePropagatesToADelegateContainerOfTheSameType(): void
    {
        $delegate = new Container();
        $delegate->factory('svc', static fn (): SimpleService => new SimpleService());

        $container = new Container();
        $container->delegate($delegate);

        self::assertNotSame($container->make('svc'), $container->make('svc'));
    }
}
