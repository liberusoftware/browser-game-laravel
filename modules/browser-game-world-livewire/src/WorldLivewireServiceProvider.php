<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\WorldLivewire\Livewire\WorldCatalog;
use Livewire\Livewire;

final class WorldLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-world-livewire');
        Livewire::component('browser-game.world.catalog', WorldCatalog::class);
    }
}
