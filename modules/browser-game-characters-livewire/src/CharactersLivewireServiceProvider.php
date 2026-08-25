<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CharactersLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\CharactersLivewire\Livewire\CharacterPanel;
use Livewire\Livewire;

final class CharactersLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-characters-livewire');
        Livewire::component('browser-game.characters.character-panel', CharacterPanel::class);
        Livewire::addNamespace('module-browser-game-characters', classNamespace: 'Liberu\\BrowserGame\\CharactersLivewire\\Livewire');
    }
}
