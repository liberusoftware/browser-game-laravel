<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\CraftingLivewire\Livewire\CraftingCatalog;
use Livewire\Livewire;

final class CraftingLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.crafting.catalog', CraftingCatalog::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-crafting-livewire');
    }
}
