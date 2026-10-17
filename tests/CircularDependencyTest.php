<?php

declare(strict_types=1);

namespace Azad\Container\Tests;

use PHPUnit\Framework\TestCase;
use Azad\Container\Container;
use Azad\Container\Exception\CircularDependencyException;
use Azad\Container\Tests\Fixtures\CircularA;

final class CircularDependencyTest extends TestCase
{
    public function testDetectsACircularDependencyAndReportsTheCycle(): void
    {
        $container = new Container();

        try {
            $container->get(CircularA::class);
            self::fail('Expected a CircularDependencyException to be thrown.');
        } catch (CircularDependencyException $exception) {
            self::assertStringContainsString(CircularA::class, $exception->getMessage());
            self::assertStringContainsString('CircularB', $exception->getMessage());
        }
    }

    public function testDetectsACircularDependencyThroughFactoryClosures(): void
    {
        $container = new Container();
        $container->set('a', static fn (Container $c): mixed => $c->get('b'));
        $container->set('b', static fn (Container $c): mixed => $c->get('a'));

        try {
            $container->get('a');
            self::fail('Expected a CircularDependencyException to be thrown.');
        } catch (CircularDependencyException $exception) {
            self::assertStringContainsString('"a"', $exception->getMessage());
            self::assertStringContainsString('a -> b -> a', $exception->getMessage());
        }
    }

    public function testDetectsACircularDependencyThroughAliases(): void
    {
        $container = new Container();
        $container->alias('a', 'b');
        $container->alias('b', 'a');

        try {
            $container->get('a');
            self::fail('Expected a CircularDependencyException to be thrown.');
        } catch (CircularDependencyException $exception) {
            self::assertStringContainsString('a -> b -> a', $exception->getMessage());
        }
    }
}
