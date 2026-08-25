<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCore;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\GameCore\Policies\GameCorePolicy;
use Liberu\BrowserGame\GameCore\Queries\GameCoreOverview;
use Liberu\BrowserGame\GameCore\Support\GameCoreManager;

final class GameCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/game-core.php', 'browser-game.game-core');
        $this->app->singleton(GameCoreManager::class);
        $this->app->singleton(GameCoreOverview::class);
        $this->app->singleton(GameCorePolicy::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([__DIR__.'/../config/game-core.php' => config_path('browser-game/game-core.php')], 'browser-game-game-core-config');
    }
}
