<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\LiveOpsLivewire\Livewire\LiveOpsCatalog;
use Livewire\Livewire;

final class LiveOpsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.live-ops.catalog', LiveOpsCatalog::class);
        Livewire::addNamespace('module-browser-game-live-ops', classNamespace: 'Liberu\\BrowserGame\\LiveOpsLivewire\\Livewire');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-live-ops-livewire');
    }
}
