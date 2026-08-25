<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Characters\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Characters\Events\CharacterCreated;
use Liberu\BrowserGame\Characters\Events\CharacterProgressed;
use Liberu\BrowserGame\Characters\Events\CharacterRespecced;
use Liberu\BrowserGame\Characters\Events\CharacterSkillsAllocated;
use Liberu\BrowserGame\Characters\Models\GameCharacter;
use Liberu\BrowserGame\Characters\Queries\CharacterQuery;

final class CharactersManager
{
    public function __construct(private readonly CharacterQuery $query) {}

    public function create(string $playerId, string $name, string $race, string $class, ?string $background = null, array $statistics = [], array $skills = [], ?string $worldId = null, ?string $teamId = null, ?string $tenantId = null): GameCharacter
    {
        foreach (['player_id' => $playerId, 'name' => $name, 'race' => $race, 'class' => $class] as $field => $value) {
            if (trim($value) === '') {
                throw ValidationException::withMessages([$field => 'This value is required.']);
            }
        }
        if (GameCharacter::query()->where('player_id', $playerId)->where('name', $name)->where('tenant_id', $tenantId)->where('team_id', $teamId)->exists()) {
            throw ValidationException::withMessages(['name' => 'A character with this name already exists.']);
        }

        $health = (int) config('browser-game.characters.starting_health', 100);
        $mana = (int) config('browser-game.characters.starting_mana', 50);
        $character = DB::transaction(fn (): GameCharacter => GameCharacter::query()->create([
            'id' => (string) Str::uuid(), 'player_id' => $playerId, 'world_id' => $worldId, 'tenant_id' => $tenantId, 'team_id' => $teamId,
            'name' => $name, 'race' => $race, 'class' => $class, 'background' => $background,
            'statistics' => $statistics, 'skills' => $skills, 'experience' => 0, 'level' => 1,
            'health' => $health, 'max_health' => $health, 'mana' => $mana, 'max_mana' => $mana,
            'strength' => (int) config('browser-game.characters.starting_strength', 10),
            'defense' => (int) config('browser-game.characters.starting_defense', 10),
            'agility' => (int) config('browser-game.characters.starting_agility', 10),
            'intelligence' => (int) config('browser-game.characters.starting_intelligence', 10),
            'stat_points' => 0,
            'available_skill_points' => (int) config('browser-game.characters.starting_skill_points', 0), 'respec_count' => 0,
        ]));
        CharacterCreated::dispatch((string) $character->getKey(), $playerId);

        return $character;
    }

    public function awardExperience(GameCharacter $character, int $amount, ?string $operationKey = null): GameCharacter
    {
        if ($amount < 0) {
            throw ValidationException::withMessages(['experience' => 'Experience cannot be negative.']);
        }
        $alreadyApplied = false;
        $updated = DB::transaction(function () use ($character, $amount, $operationKey, &$alreadyApplied): GameCharacter {
            $character = GameCharacter::query()->whereKey($character->getKey())->lockForUpdate()->firstOrFail();
            if ($operationKey !== null && $character->last_operation_key === $operationKey) {
                $alreadyApplied = true;

                return $character;
            }
            $experience = (int) $character->getAttribute('experience') + $amount;
            $level = $this->query->levelForExperience($experience);
            $oldLevel = (int) $character->getAttribute('level');
            $levelsGained = max(0, $level - $oldLevel);
            $maxHealth = max((int) $character->getAttribute('max_health'), 100 + (($level - 1) * 10));
            $maxMana = max((int) $character->getAttribute('max_mana'), 50 + (($level - 1) * 5));
            $character->update([
                'experience' => $experience,
                'level' => $level,
                'max_health' => $maxHealth,
                'health' => $levelsGained > 0 ? $maxHealth : min((int) $character->getAttribute('health'), $maxHealth),
                'max_mana' => $maxMana,
                'mana' => $levelsGained > 0 ? $maxMana : min((int) $character->getAttribute('mana'), $maxMana),
                'available_skill_points' => (int) $character->getAttribute('available_skill_points') + ($levelsGained * (int) config('browser-game.characters.skill_points_per_level', 5)),
                'stat_points' => (int) $character->getAttribute('stat_points') + ($levelsGained * (int) config('browser-game.characters.stat_points_per_level', 5)),
                'last_operation_key' => $operationKey,
            ]);

            return $character->refresh();
        });
        if (! $alreadyApplied) {
            CharacterProgressed::dispatch((string) $updated->getKey(), (int) $updated->experience, (int) $updated->level);
        }

        return $updated;
    }

    public function respec(GameCharacter $character, array $skills, ?string $operationKey = null): GameCharacter
    {
        if (array_sum(array_map('intval', $skills)) < 0 || count(array_filter($skills, fn ($points): bool => (int) $points < 0)) > 0) {
            throw ValidationException::withMessages(['skills' => 'Skill points cannot be negative.']);
        }
        $alreadyApplied = false;
        $updated = DB::transaction(function () use ($character, $skills, $operationKey, &$alreadyApplied): GameCharacter {
            $character = GameCharacter::query()->whereKey($character->getKey())->lockForUpdate()->firstOrFail();
            if ($operationKey !== null && $character->last_operation_key === $operationKey) {
                $alreadyApplied = true;

                return $character;
            }
            $skills = array_map('intval', $skills);
            $maximum = (int) config('browser-game.characters.starting_skill_points', 0)
                + max(0, ((int) $character->level - 1) * (int) config('browser-game.characters.skill_points_per_level', 5));
            if (array_sum($skills) > $maximum) {
                throw ValidationException::withMessages(['skills' => 'The character does not have enough skill points.']);
            }
            $character->update([
                'skills' => $skills,
                'available_skill_points' => $maximum - array_sum($skills),
                'respec_count' => (int) $character->getAttribute('respec_count') + 1,
                'last_operation_key' => $operationKey,
            ]);

            return $character->refresh();
        });
        if (! $alreadyApplied) {
            CharacterRespecced::dispatch((string) $updated->getKey(), (int) $updated->respec_count);
        }

        return $updated;
    }

    public function spendStatPoints(GameCharacter $character, array $points): GameCharacter
    {
        $allowed = ['strength', 'defense', 'agility', 'intelligence'];
        $points = array_intersect_key($points, array_flip($allowed));
        $spent = array_sum(array_map('intval', $points));
        if ($spent < 0 || count(array_filter($points, fn ($value): bool => (int) $value < 0)) > 0) {
            throw ValidationException::withMessages(['statistics' => 'Stat points cannot be negative.']);
        }
        if ($spent > (int) $character->getAttribute('stat_points')) {
            throw ValidationException::withMessages(['statistics' => 'The character does not have enough stat points.']);
        }

        $updated = DB::transaction(function () use ($character, $points, $allowed, $spent): GameCharacter {
            $character = GameCharacter::query()->whereKey($character->getKey())->lockForUpdate()->firstOrFail();
            if ($spent > (int) $character->getAttribute('stat_points')) {
                throw ValidationException::withMessages(['statistics' => 'The character does not have enough stat points.']);
            }
            $updates = [];
            foreach ($allowed as $stat) {
                if (array_key_exists($stat, $points)) {
                    $updates[$stat] = (int) $character->getAttribute($stat) + (int) $points[$stat];
                }
            }
            $updates['stat_points'] = (int) $character->getAttribute('stat_points') - $spent;
            $character->update($updates);

            return $character->refresh();
        });

        return $updated;
    }

    public function allocateSkills(GameCharacter $character, array $skills, ?string $operationKey = null): GameCharacter
    {
        $skills = array_map('intval', $skills);
        if (count(array_filter($skills, fn (int $points): bool => $points < 0)) > 0) {
            throw ValidationException::withMessages(['skills' => 'Skill points cannot be negative.']);
        }
        $spent = array_sum($skills);
        $alreadyApplied = false;
        $updated = DB::transaction(function () use ($character, $skills, $spent, $operationKey, &$alreadyApplied): GameCharacter {
            $character = GameCharacter::query()->whereKey($character->getKey())->lockForUpdate()->firstOrFail();
            if ($operationKey !== null && $character->last_operation_key === $operationKey) {
                $alreadyApplied = true;

                return $character;
            }
            if ($spent > (int) $character->available_skill_points) {
                throw ValidationException::withMessages(['skills' => 'The character does not have enough skill points.']);
            }
            $current = $character->skills ?? [];
            foreach ($skills as $skill => $points) {
                $current[$skill] = (int) ($current[$skill] ?? 0) + $points;
            }
            $character->update([
                'skills' => $current,
                'available_skill_points' => (int) $character->available_skill_points - $spent,
                'last_operation_key' => $operationKey,
            ]);

            return $character->refresh();
        });
        if (! $alreadyApplied) {
            CharacterSkillsAllocated::dispatch((string) $updated->getKey(), $spent);
        }

        return $updated;
    }

    public function updateProfile(GameCharacter $character, string $name, string $race, string $class, ?string $background = null): GameCharacter
    {
        foreach (['name' => $name, 'race' => $race, 'class' => $class] as $field => $value) {
            if (trim($value) === '') {
                throw ValidationException::withMessages([$field => 'This value is required.']);
            }
        }
        $duplicate = GameCharacter::query()->where('player_id', $character->player_id)->where('name', $name)->where('tenant_id', $character->tenant_id)->where('team_id', $character->team_id)->where('id', '!=', $character->getKey())->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'A character with this name already exists.']);
        }
        $character->update(compact('name', 'race', 'class', 'background'));

        return $character->refresh();
    }

    public function updateVitals(GameCharacter $character, int $health, int $mana): GameCharacter
    {
        if ($health < 0 || $health > (int) $character->max_health || $mana < 0 || $mana > (int) $character->max_mana) {
            throw ValidationException::withMessages(['vitals' => 'Health and mana must remain within their maximum values.']);
        }
        $character = GameCharacter::query()->whereKey($character->getKey())->lockForUpdate()->firstOrFail();
        if ($health > (int) $character->max_health || $mana > (int) $character->max_mana) {
            throw ValidationException::withMessages(['vitals' => 'Health and mana must remain within their maximum values.']);
        }
        $character->update(['health' => $health, 'mana' => $mana, 'last_action_at' => now()]);

        return $character->refresh();
    }
}
