<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Combat\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Combat\Events\CombatActionResolved;
use Liberu\BrowserGame\Combat\Events\CombatBattleStarted;
use Liberu\BrowserGame\Combat\Models\CombatAction;
use Liberu\BrowserGame\Combat\Models\CombatBattle;
use Liberu\BrowserGame\Combat\Models\CombatDefinition;

final class CombatManager
{
    public function start(string $actorId, string $opponentId, ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null, array $state = []): CombatBattle
    {
        if (trim($actorId) === '' || trim($opponentId) === '' || $actorId === $opponentId) {
            throw ValidationException::withMessages(['combatants' => 'Distinct combatants are required.']);
        }
        $battle = DB::transaction(fn (): CombatBattle => CombatBattle::query()->firstOrCreate(
            ['actor_id' => $actorId, 'idempotency_key' => $idempotencyKey],
            ['id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'team_id' => $teamId, 'opponent_id' => $opponentId, 'status' => 'active', 'seed' => Str::uuid()->toString(), 'state' => $state, 'created_at' => now(), 'updated_at' => now()]
        ));
        CombatBattleStarted::dispatch((string) $battle->getKey(), $actorId, $opponentId);

        return $battle;
    }

    public function resolve(CombatBattle $battle, string $actorId, string $action, int $value = 0, ?string $idempotencyKey = null, array $effects = []): CombatAction
    {
        if ($battle->getAttribute('status') !== 'active') {
            throw ValidationException::withMessages(['battle' => 'The battle is not active.']);
        }
        if ($battle->getAttribute('actor_id') !== $actorId) {
            throw ValidationException::withMessages(['actor' => 'The actor cannot act in this battle.']);
        }
        if (trim($action) === '' || $value < 0) {
            throw ValidationException::withMessages(['action' => 'A valid action and non-negative value are required.']);
        }
        $result = DB::transaction(function () use ($battle, $actorId, $action, $value, $idempotencyKey, $effects): CombatAction {
            $actionRecord = CombatAction::query()->firstOrCreate(
                ['combat_id' => $battle->getKey(), 'idempotency_key' => $idempotencyKey],
                ['id' => (string) Str::uuid(), 'turn' => (int) $battle->getAttribute('turn'), 'actor_id' => $actorId, 'action' => $action, 'value' => $value, 'effects' => $effects, 'created_at' => now(), 'updated_at' => now()]
            );
            if ($actionRecord->wasRecentlyCreated) {
                $battle->increment('turn');
                $battle->refresh();
            }

            return $actionRecord;
        });
        CombatActionResolved::dispatch((string) $battle->getKey(), (string) $result->getKey(), (int) $result->getAttribute('turn'), (int) $result->getAttribute('value'));

        return $result;
    }

    public function define(string $kind, string $slug, string $name, array $effects = [], array $data = [], int $cooldown = 0): CombatDefinition
    {
        if (! in_array($kind, ['ability', 'effect', 'enemy', 'boss', 'loot'], true) || trim($slug) === '' || trim($name) === '' || $cooldown < 0) {
            throw ValidationException::withMessages(['definition' => 'A valid combat definition is required.']);
        }

        return CombatDefinition::query()->create(['id' => (string) Str::uuid(), 'kind' => $kind, 'slug' => $slug, 'name' => $name, 'effects' => $effects, 'data' => $data, 'cooldown' => $cooldown, 'status' => 'active']);
    }

    public function simulate(string $actorId, string $opponentId, array $actions, array $state = []): array
    {
        $turn = 1;
        $log = [];
        foreach ($actions as $action) {
            $log[] = ['turn' => $turn++, 'actor_id' => $actorId, 'action' => $action['action'] ?? 'attack', 'value' => max(0, (int) ($action['value'] ?? 0))];
        }

        return ['actor_id' => $actorId, 'opponent_id' => $opponentId, 'state' => $state, 'turns' => $log, 'seed' => hash('sha256', json_encode([$actorId, $opponentId, $actions], JSON_THROW_ON_ERROR))];
    }
}
