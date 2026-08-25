<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CompetitionLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\CompetitionLivewire\Livewire\CompetitionCatalog;
use Livewire\Livewire;

final class CompetitionLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.competition.catalog', CompetitionCatalog::class);
        Livewire::addNamespace('module-browser-game-competition', classNamespace: 'Liberu\\BrowserGame\\CompetitionLivewire\\Livewire');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-competition-livewire');
    }
}
