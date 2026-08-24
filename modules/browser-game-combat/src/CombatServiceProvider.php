<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Combat;

use Illuminate\Support\ServiceProvider;

final class CombatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/combat.php', 'browser-game.combat');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
