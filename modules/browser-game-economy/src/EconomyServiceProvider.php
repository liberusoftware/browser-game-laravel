<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy;

use Illuminate\Support\ServiceProvider;

final class EconomyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/economy.php', 'browser-game.economy');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([__DIR__.'/../config/economy.php' => config_path('browser-game/economy.php')], 'browser-game-economy-config');
    }
}
