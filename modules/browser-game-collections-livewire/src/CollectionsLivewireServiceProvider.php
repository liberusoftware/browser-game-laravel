<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CollectionsLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\CollectionsLivewire\Livewire\CollectionsCatalog;
use Livewire\Livewire;

final class CollectionsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.collections.catalog', CollectionsCatalog::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-collections-livewire');
    }
}
