<?php

declare(strict_types=1);

namespace Azad\Container\Tests;

use PHPUnit\Framework\TestCase;
use Azad\Container\Container;
use Azad\Container\Exception\NotFoundException;
use Azad\Container\Tests\Fixtures\InjectedConfigService;

final class InjectAttributeTest extends TestCase
{
    public function testInjectAttributeResolvesAScalarFromAnExplicitContainerEntry(): void
    {
        $container = new Container();
        $container->set('config.api_key', 'secret-from-config');

        $service = $container->get(InjectedConfigService::class);

        self::assertSame('secret-from-config', $service->apiKey);
    }

    public function testInjectAttributePropagatesNotFoundWhenTheReferencedEntryIsMissing(): void
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);

        $container->get(InjectedConfigService::class);
    }
}
