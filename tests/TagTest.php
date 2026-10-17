<?php

declare(strict_types=1);

namespace Azad\Container\Tests;

use PHPUnit\Framework\TestCase;
use Azad\Container\Container;
use Azad\Container\Tests\Fixtures\TaggedServiceA;
use Azad\Container\Tests\Fixtures\TaggedServiceB;

final class TagTest extends TestCase
{
    public function testTaggedReturnsResolvedServicesInRegistrationOrder(): void
    {
        $container = new Container();
        $container->tag(TaggedServiceA::class, ['handlers']);
        $container->tag(TaggedServiceB::class, ['handlers']);

        $handlers = $container->tagged('handlers');

        self::assertCount(2, $handlers);
        self::assertInstanceOf(TaggedServiceA::class, $handlers[0]);
        self::assertInstanceOf(TaggedServiceB::class, $handlers[1]);
    }

    public function testTaggedReturnsAnEmptyArrayForAnUnknownTag(): void
    {
        $container = new Container();

        self::assertSame([], $container->tagged('nothing'));
    }
}
