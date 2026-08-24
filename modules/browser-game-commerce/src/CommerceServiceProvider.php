<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Commerce;

use Illuminate\Support\ServiceProvider;

final class CommerceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
