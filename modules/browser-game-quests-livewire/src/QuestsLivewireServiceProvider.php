<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\QuestsLivewire\Livewire\QuestCatalog;
use Livewire\Livewire;

final class QuestsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.quests.catalog', QuestCatalog::class);
        Livewire::addNamespace('module-browser-game-quests', classNamespace: 'Liberu\\BrowserGame\\QuestsLivewire\\Livewire');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-quests-livewire');
    }
}
