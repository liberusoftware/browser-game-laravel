<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CharactersLivewire\Livewire;

use Liberu\BrowserGame\Characters\Models\GameCharacter;
use Livewire\Component;

final class CharacterPanel extends Component
{
    public string $characterId;

    public function mount(string $characterId): void
    {
        $this->characterId = $characterId;
    }

    public function render(): mixed
    {
        $character = GameCharacter::query()->whereKey($this->characterId)->where('player_id', (string) auth()->id())->firstOrFail();

        return resolve('view')->make('browser-game-characters-livewire::character-panel', ['character' => $character]);
    }
}
