<?php

declare(strict_types=1);

namespace Azad\Container\Tests;

use PHPUnit\Framework\TestCase;
use Azad\Container\Container;
use Azad\Container\Exception\UnresolvableParameterException;
use Azad\Container\Tests\Fixtures\Logger;
use Azad\Container\Tests\Fixtures\NoTypeService;
use Azad\Container\Tests\Fixtures\ServiceWithRequiredScalar;
use Azad\Container\Tests\Fixtures\ServiceWithScalarDefault;
use Azad\Container\Tests\Fixtures\SimpleService;

final class AutowiringTest extends TestCase
{
    public function testResolvesNestedConstructorDependencies(): void
    {
        $container = new Container();

        $logger = $container->get(Logger::class);

        self::assertInstanceOf(Logger::class, $logger);
        self::assertInstanceOf(SimpleService::class, $logger->service);
        self::assertSame($container->get(SimpleService::class), $logger->service);
    }

    public function testScalarParameterFallsBackToItsDefaultValue(): void
    {
        $container = new Container();

        $service = $container->get(ServiceWithScalarDefault::class);

        self::assertSame('/tmp/default', $service->targetDir);
    }

    public function testRequiredScalarWithoutBindingThrows(): void
    {
        $container = new Container();

        $this->expectException(UnresolvableParameterException::class);

        $container->get(ServiceWithRequiredScalar::class);
    }

    public function testUntypedParameterWithoutDefaultThrows(): void
    {
        $container = new Container();

        $this->expectException(UnresolvableParameterException::class);

        $container->get(NoTypeService::class);
    }
}
