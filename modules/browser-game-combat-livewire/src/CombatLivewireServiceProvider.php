<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\CombatLivewire\Livewire\CombatPanel;
use Livewire\Livewire;

final class CombatLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.combat.panel', CombatPanel::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-combat-livewire');
    }
}
