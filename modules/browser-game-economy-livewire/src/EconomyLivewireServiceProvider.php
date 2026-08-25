<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\EconomyLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\EconomyLivewire\Livewire\EconomyCatalog;
use Livewire\Livewire;

final class EconomyLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.economy.catalog', EconomyCatalog::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-economy-livewire');
    }
}
