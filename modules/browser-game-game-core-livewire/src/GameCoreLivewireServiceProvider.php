<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\GameCoreLivewire\Livewire\WorldOverview;
use Livewire\Livewire;

final class GameCoreLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-game-core-livewire');
        Livewire::component('browser-game.game-core.world-overview', WorldOverview::class);
        Livewire::addNamespace('module-browser-game-game-core', classNamespace: 'Liberu\\BrowserGame\\GameCoreLivewire\\Livewire');
    }
}
