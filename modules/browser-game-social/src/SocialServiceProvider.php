<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Social;

use Illuminate\Support\ServiceProvider;

final class SocialServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
