<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOps;

use Illuminate\Support\ServiceProvider;

final class LiveOpsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
