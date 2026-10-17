<?php

declare(strict_types=1);

namespace Azad\Container\Tests;

use PHPUnit\Framework\TestCase;
use Azad\Container\Container;
use Azad\Container\Tests\Fixtures\RecordingServiceProvider;

final class ServiceProviderTest extends TestCase
{
    public function testProviderWithProvidesIsRegisteredLazilyOnFirstUse(): void
    {
        $provider = new RecordingServiceProvider(
            ['lazy.value'],
            static function (Container $c): void {
                $c->set('lazy.value', 'built');
            }
        );

        $container = new Container(providers: [$provider]);

        self::assertSame(0, $provider->registerCalls);
        self::assertTrue($container->has('lazy.value'));

        self::assertSame('built', $container->get('lazy.value'));
        self::assertSame(1, $provider->registerCalls);

        // A second lookup must not register the provider again.
        $container->get('lazy.value');
        self::assertSame(1, $provider->registerCalls);
    }

    public function testProviderWithoutProvidesRegistersEagerly(): void
    {
        $provider = new RecordingServiceProvider(
            [],
            static function (Container $c): void {
                $c->set('eager.value', 'built');
            }
        );

        new Container(providers: [$provider]);

        self::assertSame(1, $provider->registerCalls);
    }

    public function testAddServiceProviderCanBeCalledDirectly(): void
    {
        $container = new Container();
        $provider = new RecordingServiceProvider(
            ['direct.value'],
            static function (Container $c): void {
                $c->set('direct.value', 42);
            }
        );

        $container->addServiceProvider($provider);

        self::assertSame(42, $container->get('direct.value'));
    }
}
