<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\World;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\World\Policies\WorldPolicy;
use Liberu\BrowserGame\World\Queries\WorldQuery;
use Liberu\BrowserGame\World\Support\WorldManager;

final class WorldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/world.php', 'browser-game.world');
        $this->app->singleton(WorldQuery::class);
        $this->app->singleton(WorldManager::class);
        $this->app->singleton(WorldPolicy::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
