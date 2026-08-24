<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CommerceLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\CommerceLivewire\Livewire\CommerceCatalog;
use Livewire\Livewire;

final class CommerceLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.commerce.catalog', CommerceCatalog::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-commerce-livewire');
    }
}
