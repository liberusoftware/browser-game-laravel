<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CharactersLivewire\Livewire;

use Liberu\BrowserGame\Characters\Models\GameCharacter;
use Liberu\BrowserGame\Characters\Support\CharactersManager;
use Livewire\Component;

final class CharacterPanel extends Component
{
    public string $characterId;

    public array $skills = [];

    public array $statistics = [];

    public function mount(string $characterId): void
    {
        $this->characterId = $characterId;
    }

    public function respec(): void
    {
        $character = $this->ownedCharacter();
        $this->validate(['skills' => ['array'], 'skills.*' => ['integer', 'min:0']]);
        app(CharactersManager::class)->respec($character, $this->skills);
        $this->dispatch('character-updated');
    }

    public function spendStats(): void
    {
        $character = $this->ownedCharacter();
        $this->validate(['statistics' => ['array'], 'statistics.*' => ['integer', 'min:0']]);
        app(CharactersManager::class)->spendStatPoints($character, $this->statistics);
        $this->dispatch('character-updated');
    }

    public function render(): mixed
    {
        $character = $this->ownedCharacter();

        return resolve('view')->make('browser-game-characters-livewire::character-panel', ['character' => $character]);
    }

    private function ownedCharacter(): GameCharacter
    {
        return GameCharacter::query()->whereKey($this->characterId)->where('player_id', (string) auth()->id())->firstOrFail();
    }
}
