<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Combat\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Combat\Events\CombatActionResolved;
use Liberu\BrowserGame\Combat\Events\CombatBattleCompleted;
use Liberu\BrowserGame\Combat\Events\CombatBattleStarted;
use Liberu\BrowserGame\Combat\Events\CombatLootGranted;
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
        $initialState = array_replace_recursive([
            'health' => ['actor' => 100, 'opponent' => 100],
            'cooldowns' => [],
            'loot' => [],
        ], $state);
        $battle = DB::transaction(fn (): CombatBattle => CombatBattle::query()->firstOrCreate(
            ['actor_id' => $actorId, 'idempotency_key' => $idempotencyKey],
            ['id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'team_id' => $teamId, 'opponent_id' => $opponentId, 'status' => 'active', 'seed' => Str::uuid()->toString(), 'state' => $initialState, 'created_at' => now(), 'updated_at' => now()]
        ));
        if ($battle->wasRecentlyCreated) {
            CombatBattleStarted::dispatch((string) $battle->getKey(), $actorId, $opponentId);
        }

        return $battle;
    }

    public function resolve(CombatBattle $battle, string $actorId, string $action, int $value = 0, ?string $idempotencyKey = null, array $effects = []): CombatAction
    {
        if (trim($action) === '' || $value < 0) {
            throw ValidationException::withMessages(['action' => 'A valid action and non-negative value are required.']);
        }
        $completed = false;
        $loot = [];
        $result = DB::transaction(function () use ($battle, $actorId, $action, $value, $idempotencyKey, $effects, &$completed, &$loot): CombatAction {
            $battle = CombatBattle::query()->lockForUpdate()->findOrFail($battle->getKey());
            if ($battle->status !== 'active') {
                throw ValidationException::withMessages(['battle' => 'The battle is not active.']);
            }
            if ($battle->actor_id !== $actorId) {
                throw ValidationException::withMessages(['actor' => 'The actor cannot act in this battle.']);
            }
            $existing = $idempotencyKey === null ? null : CombatAction::query()->where(['combat_id' => $battle->getKey(), 'idempotency_key' => $idempotencyKey])->first();
            if ($existing !== null) {
                return $existing;
            }
            $definition = CombatDefinition::query()->where('slug', $action)->where('kind', 'ability')->where('status', 'active')->first();
            $state = (array) $battle->state;
            $cooldowns = (array) ($state['cooldowns'] ?? []);
            $readyAt = (int) (($cooldowns[$actorId][$action] ?? 0));
            if ($readyAt > (int) $battle->turn) {
                throw ValidationException::withMessages(['action' => 'This ability is on cooldown.']);
            }
            $resolvedValue = $definition === null ? $value : max(0, (int) (($definition->data ?? [])['power'] ?? $value));
            $resolvedEffects = array_merge((array) ($definition?->effects ?? []), $effects);
            $actionRecord = CombatAction::query()->create([
                'id' => (string) Str::uuid(), 'combat_id' => $battle->getKey(), 'turn' => (int) $battle->turn,
                'actor_id' => $actorId, 'action' => $action, 'value' => $resolvedValue,
                'effects' => $resolvedEffects, 'idempotency_key' => $idempotencyKey,
            ]);
            $health = array_merge(['actor' => 100, 'opponent' => 100], (array) ($state['health'] ?? []));
            if ($action === 'heal') {
                $health['actor'] = min(100, (int) $health['actor'] + $resolvedValue);
            } elseif ($action !== 'defend') {
                $health['opponent'] = max(0, (int) $health['opponent'] - $resolvedValue);
            }
            if ($definition !== null && (int) $definition->cooldown > 0) {
                $cooldowns[$actorId][$action] = (int) $battle->turn + (int) $definition->cooldown + 1;
            }
            $state['health'] = $health;
            $state['cooldowns'] = $cooldowns;
            $state['log'] = array_slice(array_merge((array) ($state['log'] ?? []), [[
                'turn' => (int) $battle->turn, 'actor_id' => $actorId, 'action' => $action, 'value' => $resolvedValue,
            ]]), -100);
            if ((int) $health['opponent'] <= 0) {
                $completed = true;
                $loot = (array) ($state['loot'] ?? []);
                $battle->status = 'completed';
            }
            $battle->state = $state;
            $battle->turn = (int) $battle->turn + 1;
            $battle->save();

            return $actionRecord;
        });
        CombatActionResolved::dispatch((string) $battle->getKey(), (string) $result->getKey(), (int) $result->getAttribute('turn'), (int) $result->getAttribute('value'));
        if ($completed) {
            CombatBattleCompleted::dispatch((string) $battle->getKey(), $actorId, $loot);
            if ($loot !== []) {
                CombatLootGranted::dispatch((string) $battle->getKey(), $actorId, $loot);
            }
        }

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
        if (trim($actorId) === '' || trim($opponentId) === '' || $actorId === $opponentId) {
            throw ValidationException::withMessages(['combatants' => 'Distinct combatants are required.']);
        }
        $turn = 1;
        $state = array_replace_recursive(['health' => ['actor' => 100, 'opponent' => 100], 'cooldowns' => [], 'loot' => []], $state);
        $log = [];
        foreach ($actions as $action) {
            $name = (string) ($action['action'] ?? 'attack');
            $resolved = max(0, (int) ($action['value'] ?? 0));
            if ($name === 'heal') {
                $state['health']['actor'] = min(100, (int) $state['health']['actor'] + $resolved);
            } elseif ($name !== 'defend') {
                $state['health']['opponent'] = max(0, (int) $state['health']['opponent'] - $resolved);
            }
            $log[] = ['turn' => $turn++, 'actor_id' => $actorId, 'action' => $name, 'value' => $resolved, 'health' => $state['health']];
            if ((int) $state['health']['opponent'] <= 0) {
                break;
            }
        }

        return ['actor_id' => $actorId, 'opponent_id' => $opponentId, 'status' => (int) $state['health']['opponent'] <= 0 ? 'completed' : 'active', 'state' => $state, 'turns' => $log, 'seed' => hash('sha256', json_encode([$actorId, $opponentId, $actions], JSON_THROW_ON_ERROR))];
    }
}
