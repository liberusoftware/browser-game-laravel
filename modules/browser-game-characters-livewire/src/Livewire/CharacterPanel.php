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

    public string $name = '';

    public string $race = '';

    public string $class = '';

    public ?string $background = null;

    public ?string $statusMessage = null;

    public function mount(string $characterId): void
    {
        $this->characterId = $characterId;
        $character = $this->ownedCharacter();
        $this->health = (int) $character->health;
        $this->mana = (int) $character->mana;
        $this->name = (string) $character->name;
        $this->race = (string) $character->race;
        $this->class = (string) $character->class;
        $this->background = $character->background;
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

    public function updateProfile(): void
    {
        $character = $this->ownedCharacter();
        $this->validate(['name' => ['required', 'string', 'max:255'], 'race' => ['required', 'string', 'max:80'], 'class' => ['required', 'string', 'max:80'], 'background' => ['nullable', 'string', 'max:255']]);
        app(CharactersManager::class)->updateProfile($character, $this->name, $this->race, $this->class, $this->background);
        $this->statusMessage = 'Character profile updated.';
        $this->dispatch('character-updated');
    }

    public function render(): mixed
    {
        $character = $this->ownedCharacter();

        return resolve('view')->make('browser-game-characters-livewire::character-panel', ['character' => $character]);
    }

    private function ownedCharacter(): GameCharacter
    {
        $teamId = auth()->user()?->currentTeam?->getKey();

        return GameCharacter::query()
            ->whereKey($this->characterId)
            ->where('player_id', (string) auth()->id())
            ->where('tenant_id', auth()->user()?->currentTeam?->getAttribute('tenant_id'))
            ->where('team_id', $teamId)
            ->firstOrFail();
    }
}
