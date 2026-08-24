<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\ItemsLivewire\Livewire\ItemsCatalog;
use Livewire\Livewire;

final class ItemsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.items.catalog', ItemsCatalog::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-items-livewire');
    }
}
