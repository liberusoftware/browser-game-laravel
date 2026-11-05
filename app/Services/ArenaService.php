<?php

namespace App\Services;

use App\Models\Battle;
use App\Models\Npc;
use App\Models\Player;
use App\Models\PlayerEquipment;
use App\Settings\GameSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArenaService
{
    public function expireFor(Player $player): void
    {
        Battle::where('arena_status', 'pending')
            ->where(fn ($query) => $query->where('attacker_id', $player->id)->orWhere('defender_id', $player->id))
            ->where('expires_at', '<=', now())->update(['arena_status' => 'expired', 'completed_at' => now(), 'turn_player_id' => null]);
        $expiredIds = Battle::where('arena_status', 'active')
            ->where(fn ($query) => $query->where('attacker_id', $player->id)->orWhere('defender_id', $player->id))
            ->where('expires_at', '<=', now())->pluck('id');
        foreach ($expiredIds as $battleId) {
            DB::transaction(function () use ($battleId): void {
                $battle = Battle::findOrFail($battleId);
                $players = $this->lockPlayers(array_filter([$battle->attacker_id, $battle->defender_id]));
                $battle = Battle::lockForUpdate()->findOrFail($battleId);
                if ($battle->arena_status !== 'active' || $battle->expires_at->isFuture()) {
                    return;
                }
                $state = $battle->arena_state;
                $state[$battle->turn_player_id === $battle->attacker_id ? 'attacker' : 'defender']['hp'] = 0;
                $state['timeout'] = true;
                $battle->arena_state = $state;
                $this->finish($battle, $players);
                $battle->save();
            });
        }
    }

    public function startNpc(Player $actor, int $npcId): Battle
    {
        $this->expireFor($actor);

        return DB::transaction(function () use ($actor, $npcId): Battle {
            $player = $this->lockPlayers([$actor->id])->first();
            $this->ensureAvailable($player);
            $npc = Npc::where('is_active', true)->findOrFail($npcId);
            $this->ensure($player->level >= max(1, $npc->level - 3), 'This opponent is too far above your level.');
            $this->ensure($player->health >= 10, 'Rest at the shrine before fighting.');
            $state = ['attacker' => $this->playerSnapshot($player), 'defender' => $this->npcSnapshot($npc), 'npc_behavior' => $npc->behavior];

            return Battle::create([
                'attacker_id' => $player->id, 'npc_id' => $npc->id, 'battle_type' => 'pve',
                'opponent_name' => $npc->name, 'opponent_level' => $npc->level,
                'arena_status' => 'active', 'arena_state' => $state, 'arena_turn' => 0,
                'turn_player_id' => $player->id, 'expires_at' => now()->addMinutes(config('arena.turn_minutes')), 'battle_log' => [],
            ]);
        });
    }

    public function challenge(Player $actor, int $defenderId): Battle
    {
        $this->ensure($actor->id !== $defenderId, 'Choose another player to challenge.');
        $this->expireFor($actor);
        $this->expireFor(Player::findOrFail($defenderId));

        return DB::transaction(function () use ($actor, $defenderId): Battle {
            $players = $this->lockPlayers([$actor->id, $defenderId]);
            $attacker = $players->get($actor->id);
            $defender = $players->get($defenderId);
            abort_unless($defender, 404);
            $this->ensure(app(GameSettings::class)->pvp_enabled, 'Player duels are disabled by the game administrator.');
            $this->ensure($attacker->arena_opt_in && $defender->arena_opt_in, 'Both players must opt in to arena challenges.');
            $this->ensure(abs($attacker->level - $defender->level) <= config('arena.level_gap'), 'Choose an opponent within five levels of your character.');
            $this->ensureAvailable($attacker);
            $this->ensureAvailable($defender);
            $recentChallenge = Battle::where('attacker_id', $attacker->id)->where('defender_id', $defender->id)
                ->where('battle_type', 'pvp')->whereNotNull('arena_status')->where('created_at', '>', now()->subMinute())->exists();
            $this->ensure(! $recentChallenge, 'Wait a minute before challenging the same player again.');

            return Battle::create([
                'attacker_id' => $attacker->id, 'defender_id' => $defender->id, 'battle_type' => 'pvp',
                'arena_status' => 'pending', 'arena_state' => [], 'battle_log' => [],
                'expires_at' => now()->addMinutes(config('arena.challenge_minutes')),
            ]);
        });
    }

    public function respond(Player $actor, int $battleId, bool $accept): Battle
    {
        return DB::transaction(function () use ($actor, $battleId, $accept): Battle {
            $battle = Battle::findOrFail($battleId);
            abort_unless($battle->defender_id === $actor->id, 403);
            $players = $this->lockPlayers([$battle->attacker_id, $battle->defender_id]);
            $battle = Battle::lockForUpdate()->findOrFail($battleId);
            $this->ensure($battle->arena_status === 'pending' && $battle->expires_at->isFuture(), 'This challenge is no longer available.');
            if (! $accept) {
                $battle->update(['arena_status' => 'declined', 'completed_at' => now()]);

                return $battle;
            }
            $attacker = $players->get($battle->attacker_id);
            $defender = $players->get($battle->defender_id);
            $this->ensure(app(GameSettings::class)->pvp_enabled && $attacker->arena_opt_in && $defender->arena_opt_in, 'Both players must still be opted in and PvP must be enabled.');
            $recent = Battle::where('battle_type', 'pvp')->where('arena_status', 'completed')
                ->whereIn('attacker_id', [$attacker->id, $defender->id])->whereIn('defender_id', [$attacker->id, $defender->id])
                ->where('completed_at', '>', now()->subMinutes(config('arena.reward_cooldown_minutes')))->exists();
            $state = ['attacker' => $this->playerSnapshot($attacker, true), 'defender' => $this->playerSnapshot($defender, true), 'rated' => ! $recent];
            $first = $state['attacker']['agility'] >= $state['defender']['agility'] ? $attacker->id : $defender->id;
            $battle->update(['arena_status' => 'active', 'arena_state' => $state, 'turn_player_id' => $first, 'expires_at' => now()->addMinutes(config('arena.turn_minutes'))]);

            return $battle;
        });
    }

    public function act(Player $actor, int $battleId, string $action, int $expectedTurn): Battle
    {
        $this->ensure(array_key_exists($action, config('arena.actions')), 'Choose a valid combat action.');

        return DB::transaction(function () use ($actor, $battleId, $action, $expectedTurn): Battle {
            $battle = Battle::findOrFail($battleId);
            abort_unless(in_array($actor->id, [$battle->attacker_id, $battle->defender_id], true), 403);
            $players = $this->lockPlayers(array_filter([$battle->attacker_id, $battle->defender_id]));
            $battle = Battle::lockForUpdate()->findOrFail($battleId);
            $this->ensure($battle->arena_status === 'active' && $battle->expires_at->isFuture(), 'This battle has ended or expired.');
            $this->ensure($battle->turn_player_id === $actor->id, 'Wait for your opponent’s turn.');
            $this->ensure($battle->arena_turn === $expectedTurn, 'This turn has already been resolved. Refresh the battle.');
            if ($battle->battle_type === 'pvp') {
                $this->ensure(app(GameSettings::class)->pvp_enabled, 'Player duels are currently disabled.');
            }
            $state = $battle->arena_state;
            $side = $actor->id === $battle->attacker_id ? 'attacker' : 'defender';
            $other = $side === 'attacker' ? 'defender' : 'attacker';
            $log = $battle->battle_log ?? [];
            $this->resolveAction($state, $side, $action, $log, $battle->arena_turn + 1);
            if ($battle->npc_id && $state['defender']['hp'] > 0) {
                $this->resolveAction($state, 'defender', $this->npcAction($state, $battle->arena_turn), $log, $battle->arena_turn + 1);
            }
            $turn = $battle->arena_turn + 1;
            $battle->fill(['arena_state' => $state, 'battle_log' => $log, 'arena_turn' => $turn,
                'turn_player_id' => $battle->npc_id ? $actor->id : ($other === 'attacker' ? $battle->attacker_id : $battle->defender_id),
                'expires_at' => now()->addMinutes(config('arena.turn_minutes'))]);
            if ($state['attacker']['hp'] <= 0 || $state['defender']['hp'] <= 0 || $turn >= config('arena.max_turns')) {
                $this->finish($battle, $players);
            }
            $battle->save();

            return $battle;
        });
    }

    public function forfeit(Player $actor, int $battleId): void
    {
        DB::transaction(function () use ($actor, $battleId): void {
            $battle = Battle::findOrFail($battleId);
            abort_unless(in_array($actor->id, [$battle->attacker_id, $battle->defender_id], true), 403);
            $players = $this->lockPlayers(array_filter([$battle->attacker_id, $battle->defender_id]));
            $battle = Battle::lockForUpdate()->findOrFail($battleId);
            $this->ensure(in_array($battle->arena_status, ['pending', 'active'], true) && $battle->expires_at->isFuture(), 'This battle is no longer active.');
            if ($battle->arena_status === 'pending') {
                $battle->update(['arena_status' => 'declined', 'completed_at' => now()]);

                return;
            }
            $state = $battle->arena_state;
            $state[$actor->id === $battle->attacker_id ? 'attacker' : 'defender']['hp'] = 0;
            $battle->arena_state = $state;
            $this->finish($battle, $players);
            $battle->save();
        });
    }

    public function setOptIn(Player $actor, bool $enabled): void
    {
        $this->expireFor($actor);
        DB::transaction(function () use ($actor, $enabled): void {
            $player = $this->lockPlayers([$actor->id])->first();
            $this->ensureAvailable($player, false);
            $player->update(['arena_opt_in' => $enabled]);
        });
    }

    public function rest(Player $actor): void
    {
        $this->expireFor($actor);
        DB::transaction(function () use ($actor): void {
            $player = $this->lockPlayers([$actor->id])->first();
            $this->ensureAvailable($player, false);
            $player->update(['health' => $player->max_health, 'mana' => $player->max_mana]);
        });
    }

    public function equip(Player $actor, int $itemId): void
    {
        $this->expireFor($actor);
        DB::transaction(function () use ($actor, $itemId): void {
            $player = $this->lockPlayers([$actor->id])->first();
            $this->ensureAvailable($player, false);
            $item = $player->items()->whereKey($itemId)->wherePivot('quantity', '>', 0)->firstOrFail();
            $this->ensure(in_array($item->slot, ['weapon', 'armor', 'helmet', 'boots', 'gloves', 'accessory'], true), 'This item cannot be equipped.');
            $this->ensure($player->level >= $item->min_level, 'Your level is too low for this item.');
            PlayerEquipment::updateOrCreate(['player_id' => $player->id, 'slot' => $item->slot], ['item_id' => $item->id]);
        });
    }

    private function lockPlayers(array $ids): Collection
    {
        return Player::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    private function ensureAvailable(Player $player, bool $cooldown = true): void
    {
        $busy = Battle::whereIn('arena_status', ['pending', 'active'])->where('expires_at', '>', now())
            ->where(fn ($query) => $query->where('attacker_id', $player->id)->orWhere('defender_id', $player->id))->exists();
        $this->ensure(! $busy, 'Finish, decline, or withdraw from your current battle first.');
        if ($cooldown) {
            $this->ensure(! $player->last_battle_at || $player->last_battle_at->lte(now()->subSeconds(config('arena.battle_cooldown_seconds'))), 'Take a short breather before your next battle.');
        }
    }

    private function playerSnapshot(Player $player, bool $full = false): array
    {
        $stats = $player->getTotalStats()['total'];
        $levelBonus = max(0, $player->level - 1);
        foreach (['strength', 'defense', 'agility', 'intelligence'] as $stat) {
            $stats[$stat] = max(1, (int) $stats[$stat]) + (int) floor($levelBonus / 2);
        }
        $stats['health'] = max(1, (int) $stats['health']) + $levelBonus * 2;
        $stats['mana'] = max(0, (int) $stats['mana']) + $levelBonus;

        return [...$stats, 'name' => $player->username, 'hp' => $full ? $stats['health'] : max(1, (int) round($stats['health'] * min(1, $player->health / max(1, $player->max_health)))),
            'mp' => $full ? $stats['mana'] : max(0, (int) round($stats['mana'] * min(1, $player->mana / max(1, $player->max_mana)))), 'guarding' => false];
    }

    private function npcSnapshot(Npc $npc): array
    {
        return [...$npc->stats, 'name' => $npc->name, 'hp' => $npc->stats['health'], 'mp' => $npc->stats['mana'], 'guarding' => false];
    }

    private function npcAction(array $state, int $turn): string
    {
        $npc = $state['defender'];
        if ($npc['hp'] < $npc['health'] / 3 && $npc['mp'] >= 15 && $turn % 3 === 0) {
            return 'mend';
        }

        return match ($state['npc_behavior']) {
            'mystic' => $npc['mp'] >= 12 ? 'arcane' : ($turn % 2 === 0 ? 'guard' : 'strike'),
            'aggressive' => $npc['mp'] >= 8 ? 'heavy' : 'strike',
            'defensive' => $turn % 3 === 0 ? 'guard' : 'strike',
            default => $turn % 4 === 2 && $npc['mp'] >= 8 ? 'heavy' : 'strike',
        };
    }

    private function resolveAction(array &$state, string $side, string $action, array &$log, int $turn): void
    {
        $other = $side === 'attacker' ? 'defender' : 'attacker';
        $fighter = &$state[$side];
        $target = &$state[$other];
        $cost = config('arena.actions')[$action]['mana'];
        $this->ensure($fighter['mp'] >= $cost, 'Not enough mana. Guard & focus restores mana.');
        $fighter['mp'] -= $cost;
        $damage = 0;
        $healing = 0;
        $critical = false;
        if ($action === 'guard') {
            $fighter['guarding'] = true;
            $fighter['mp'] = min($fighter['mana'], $fighter['mp'] + 8);
        } elseif ($action === 'mend') {
            $healing = min($fighter['health'] - $fighter['hp'], 12 + $fighter['intelligence']);
            $fighter['hp'] += $healing;
        } else {
            $base = $action === 'arcane' ? $fighter['intelligence'] * 1.5 + 5 : $fighter['strength'] + $fighter['agility'] * 0.35;
            if ($action === 'heavy') {
                $base *= 1.7;
            }
            $armor = $target['defense'] * ($action === 'arcane' ? 0.2 : 0.45);
            $critical = random_int(1, 100) <= min(25, 5 + (int) floor($fighter['agility'] / 3));
            $damage = max(1, (int) round(max(2, $base - $armor) * ($critical ? 1.5 : 1) * ($target['guarding'] ? 0.5 : 1)));
            $damage = min($target['hp'], $damage);
            $target['hp'] -= $damage;
            $target['guarding'] = false;
        }
        $log[] = ['round' => $turn, 'actor' => $side, 'name' => $fighter['name'], 'action' => $action, 'damage' => $damage, 'healing' => $healing, 'critical' => $critical,
            'attacker_hp' => $state['attacker']['hp'], 'defender_hp' => $state['defender']['hp'], 'remaining_hp' => $target['hp']];
    }

    private function finish(Battle $battle, Collection $players): void
    {
        $state = $battle->arena_state;
        $a = $state['attacker']['hp'] / $state['attacker']['health'];
        $d = $state['defender']['hp'] / $state['defender']['health'];
        $winner = $a === $d ? null : ($a > $d ? 'attacker' : 'defender');
        $battle->fill(['arena_status' => 'completed', 'completed_at' => now(), 'turn_player_id' => null,
            'outcome' => $winner ? $winner.'_won' : 'draw', 'winner_id' => $winner === 'attacker' ? $battle->attacker_id : ($winner === 'defender' ? $battle->defender_id : null)]);
        $attacker = $players->get($battle->attacker_id);
        foreach ($players as $player) {
            $player->last_battle_at = now();
        }
        if ($battle->npc_id) {
            $attacker->health = max(1, (int) round($attacker->max_health * $a));
            $attacker->mana = max(0, (int) round($attacker->max_mana * $state['attacker']['mp'] / max(1, $state['attacker']['mana'])));
            if ($winner === 'attacker') {
                $this->rewardNpc($battle, $attacker);
            }
        } elseif ($state['rated']) {
            $defender = $players->get($battle->defender_id);
            $expected = 1 / (1 + pow(10, ($defender->arena_rating - $attacker->arena_rating) / 400));
            $actual = $winner === null ? 0.5 : ($winner === 'attacker' ? 1 : 0);
            $change = (int) round(24 * ($actual - $expected));
            $change = max(-$attacker->arena_rating, min($defender->arena_rating, $change));
            $attacker->arena_rating += $change;
            $defender->arena_rating -= $change;
            $battle->rating_change = $change;
        }
        foreach ($players as $player) {
            $player->save();
        }
    }

    private function rewardNpc(Battle $battle, Player $player): void
    {
        $recent = Battle::where('attacker_id', $player->id)->where('npc_id', $battle->npc_id)->where('arena_status', 'completed')
            ->where('outcome', 'attacker_won')->whereNotNull('items_gained')->where('completed_at', '>', now()->subMinutes(config('arena.reward_cooldown_minutes')))->exists();
        if ($recent) {
            return;
        }
        $npc = Npc::findOrFail($battle->npc_id);
        $settings = app(GameSettings::class);
        $xp = $npc->experience_reward * $settings->experience_multiplier;
        $gold = $npc->gold_reward * $settings->gold_multiplier;
        $player->experience += $xp;
        while ($player->level < $settings->max_player_level && $player->experience >= $player->level * 100) {
            $player->level++;
            $player->stat_points += 5;
        }
        $resource = $player->resources()->firstOrCreate(['resource_type' => 'gold'], ['quantity' => 0]);
        $resource->increment('quantity', $gold);
        $loot = [];
        if ($npc->loot_item_id) {
            $item = $player->items()->whereKey($npc->loot_item_id)->first();
            if ($item || $player->items()->count() < $settings->max_inventory_size) {
                $player->items()->syncWithoutDetaching([$npc->loot_item_id => ['quantity' => ($item?->pivot->quantity ?? 0) + 1]]);
                $loot = [$npc->loot_item_id];
            }
        }
        $battle->fill(['experience_gained' => $xp, 'gold_gained' => $gold, 'items_gained' => $loot]);
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['arena' => $message]);
        }
    }
}
