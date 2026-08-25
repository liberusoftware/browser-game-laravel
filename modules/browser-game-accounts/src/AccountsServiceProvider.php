<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts;

use Illuminate\Support\ServiceProvider;

final class AccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/accounts.php', 'browser-game.accounts');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([__DIR__.'/../config/accounts.php' => config_path('browser-game/accounts.php')], 'browser-game-accounts-config');
    }
}
