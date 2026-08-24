<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections;

use Illuminate\Support\ServiceProvider;

final class CollectionsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
