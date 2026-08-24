<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalytics;

use Illuminate\Support\ServiceProvider;

final class ModerationAndAnalyticsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
