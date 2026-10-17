<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

use Closure;
use Azad\Container\Container;
use Azad\Container\ServiceProvider\ServiceProviderInterface;

final class RecordingServiceProvider implements ServiceProviderInterface
{
    public int $registerCalls = 0;

    /**
     * @param string[] $providedIds
     */
    public function __construct(
        private readonly array $providedIds,
        private readonly Closure $registerCallback,
    ) {
    }

    public function provides(): array
    {
        return $this->providedIds;
    }

    public function register(Container $container): void
    {
        $this->registerCalls++;
        ($this->registerCallback)($container);
    }
}
