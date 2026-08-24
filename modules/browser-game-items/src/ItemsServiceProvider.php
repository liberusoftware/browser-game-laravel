<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Items;

use Illuminate\Support\ServiceProvider;

final class ItemsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
