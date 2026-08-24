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

    public array $skillAllocation = [];

    public int $health = 0;

    public int $mana = 0;

    public ?string $statusMessage = null;

    public function mount(string $characterId): void
    {
        $this->characterId = $characterId;
        $character = $this->ownedCharacter();
        $this->health = (int) $character->health;
        $this->mana = (int) $character->mana;
    }

    public function respec(): void
    {
        $character = $this->ownedCharacter();
        $this->validate(['skills' => ['array'], 'skills.*' => ['integer', 'min:0']]);
        app(CharactersManager::class)->respec($character, $this->skills);
        $this->statusMessage = 'Skills respecced.';
        $this->dispatch('character-updated');
    }

    public function spendStats(): void
    {
        $character = $this->ownedCharacter();
        $this->validate(['statistics' => ['array'], 'statistics.*' => ['integer', 'min:0']]);
        app(CharactersManager::class)->spendStatPoints($character, $this->statistics);
        $this->statusMessage = 'Statistics updated.';
        $this->dispatch('character-updated');
    }

    public function allocateSkills(): void
    {
        $character = $this->ownedCharacter();
        $this->validate(['skillAllocation' => ['array'], 'skillAllocation.*' => ['integer', 'min:0']]);
        app(CharactersManager::class)->allocateSkills($character, $this->skillAllocation);
        $this->statusMessage = 'Skills allocated.';
        $this->dispatch('character-updated');
    }

    public function updateVitals(): void
    {
        $character = $this->ownedCharacter();
        $this->validate(['health' => ['integer', 'min:0'], 'mana' => ['integer', 'min:0']]);
        app(CharactersManager::class)->updateVitals($character, $this->health, $this->mana);
        $this->statusMessage = 'Vitals updated.';
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
