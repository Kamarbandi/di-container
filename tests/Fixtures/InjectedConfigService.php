<?php

declare(strict_types=1);

namespace Azad\Container\Tests\Fixtures;

use Azad\Container\Attribute\Inject;

final class InjectedConfigService
{
    public function __construct(
        #[Inject('config.api_key')]
        public readonly string $apiKey,
    ) {
    }
}
