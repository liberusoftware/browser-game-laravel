<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Characters;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\Characters\Policies\CharacterPolicy;
use Liberu\BrowserGame\Characters\Queries\CharacterQuery;
use Liberu\BrowserGame\Characters\Support\CharactersManager;

final class CharactersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/characters.php', 'browser-game.characters');
        $this->app->singleton(CharacterQuery::class);
        $this->app->singleton(CharactersManager::class);
        $this->app->singleton(CharacterPolicy::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([__DIR__.'/../config/characters.php' => config_path('browser-game/characters.php')], 'browser-game-characters-config');
    }
}
