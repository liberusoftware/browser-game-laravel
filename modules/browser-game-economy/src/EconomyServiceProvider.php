<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy;

use Illuminate\Support\ServiceProvider;

final class EconomyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
