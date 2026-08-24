<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Characters\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Characters\Events\CharacterCreated;
use Liberu\BrowserGame\Characters\Events\CharacterProgressed;
use Liberu\BrowserGame\Characters\Events\CharacterRespecced;
use Liberu\BrowserGame\Characters\Models\GameCharacter;
use Liberu\BrowserGame\Characters\Queries\CharacterQuery;

final class CharactersManager
{
    public function __construct(private readonly CharacterQuery $query) {}

    public function create(string $playerId, string $name, string $race, string $class, ?string $background = null, array $statistics = [], array $skills = [], ?string $worldId = null, ?string $teamId = null): GameCharacter
    {
        foreach (['player_id' => $playerId, 'name' => $name, 'race' => $race, 'class' => $class] as $field => $value) {
            if (trim($value) === '') {
                throw ValidationException::withMessages([$field => 'This value is required.']);
            }
        }
        if (GameCharacter::query()->where('player_id', $playerId)->where('name', $name)->exists()) {
            throw ValidationException::withMessages(['name' => 'A character with this name already exists.']);
        }

        $health = (int) config('browser-game.characters.starting_health', 100);
        $character = DB::transaction(fn (): GameCharacter => GameCharacter::query()->create([
            'id' => (string) Str::uuid(), 'player_id' => $playerId, 'world_id' => $worldId, 'team_id' => $teamId,
            'name' => $name, 'race' => $race, 'class' => $class, 'background' => $background,
            'statistics' => $statistics, 'skills' => $skills, 'experience' => 0, 'level' => 1,
            'health' => $health, 'max_health' => $health, 'available_skill_points' => (int) config('browser-game.characters.starting_skill_points', 0), 'respec_count' => 0,
        ]));
        CharacterCreated::dispatch((string) $character->getKey(), $playerId);

        return $character;
    }

    public function awardExperience(GameCharacter $character, int $amount): GameCharacter
    {
        if ($amount < 0) {
            throw ValidationException::withMessages(['experience' => 'Experience cannot be negative.']);
        }
        $updated = DB::transaction(function () use ($character, $amount): GameCharacter {
            $experience = (int) $character->getAttribute('experience') + $amount;
            $level = $this->query->levelForExperience($experience);
            $maxHealth = max((int) $character->getAttribute('max_health'), 100 + (($level - 1) * 10));
            $character->update(['experience' => $experience, 'level' => $level, 'max_health' => $maxHealth, 'health' => min((int) $character->getAttribute('health'), $maxHealth)]);

            return $character->refresh();
        });
        CharacterProgressed::dispatch((string) $updated->getKey(), (int) $updated->experience, (int) $updated->level);

        return $updated;
    }

    public function respec(GameCharacter $character, array $skills): GameCharacter
    {
        if (array_sum(array_map('intval', $skills)) < 0 || count(array_filter($skills, fn ($points): bool => (int) $points < 0)) > 0) {
            throw ValidationException::withMessages(['skills' => 'Skill points cannot be negative.']);
        }
        $updated = DB::transaction(function () use ($character, $skills): GameCharacter {
            $character->update(['skills' => array_map('intval', $skills), 'respec_count' => (int) $character->getAttribute('respec_count') + 1]);

            return $character->refresh();
        });
        CharacterRespecced::dispatch((string) $updated->getKey(), (int) $updated->respec_count);

        return $updated;
    }
}
