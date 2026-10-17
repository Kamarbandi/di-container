<?php

declare(strict_types=1);

namespace Azad\Container\Tests;

use PHPUnit\Framework\TestCase;
use Azad\Container\Container;
use Azad\Container\Tests\Fixtures\NullableDependencyService;
use Azad\Container\Tests\Fixtures\OtherService;
use Azad\Container\Tests\Fixtures\SimpleService;
use Azad\Container\Tests\Fixtures\UnionTypeService;
use Azad\Container\Tests\Fixtures\VariadicService;

final class ParameterResolutionTest extends TestCase
{
    public function testNullableUnboundDependencyResolvesToNull(): void
    {
        $container = new Container();

        $service = $container->get(NullableDependencyService::class);

        self::assertNull($service->thing);
    }

    public function testUnionTypeResolvesToTheFirstBindableMember(): void
    {
        $container = new Container();

        $service = $container->get(UnionTypeService::class);

        self::assertInstanceOf(SimpleService::class, $service->dependency);
    }

    public function testVariadicParameterDefaultsToAnEmptyList(): void
    {
        $container = new Container();

        $service = $container->get(VariadicService::class);

        self::assertInstanceOf(SimpleService::class, $service->first);
        self::assertSame([], $service->rest);
    }

    public function testVariadicParameterCanBeSuppliedExplicitlyViaMake(): void
    {
        $container = new Container();
        $rest = [new OtherService(), new OtherService()];

        $service = $container->make(VariadicService::class, ['rest' => $rest]);

        self::assertSame($rest, $service->rest);
    }
}
