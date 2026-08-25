<?php

namespace App\Services;

use App\Models\Battle;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class CombatService
{
    /**
     * Initiate a PvE battle against a computer opponent.
     */
    public function initiatePvEBattle(Player $player, string $opponentName, int $opponentLevel): Battle
    {
        $battle = Battle::create([
            'attacker_id' => $player->id,
            'battle_type' => 'pve',
            'opponent_name' => $opponentName,
            'opponent_level' => $opponentLevel,
        ]);

        $this->processBattle($battle);

        return $battle->fresh();
    }

    /**
     * Initiate a PvP battle between two players.
     */
    public function initiatePvPBattle(Player $attacker, Player $defender): Battle
    {
        $battle = Battle::create([
            'attacker_id' => $attacker->id,
            'defender_id' => $defender->id,
            'battle_type' => 'pvp',
        ]);

        $this->processBattle($battle);

        return $battle->fresh();
    }

    /**
     * Process the battle logic.
     */
    protected function processBattle(Battle $battle): void
    {
        $attacker = $battle->attacker;
        $attackerStats = $attacker->getTotalStats()['total'];

        if ($battle->battle_type === 'pvp') {
            $defender = $battle->defender;
            $defenderStats = $defender->getTotalStats()['total'];
        } else {
            // Generate NPC stats based on level
            $defenderStats = $this->generateNPCStats($battle->opponent_level);
        }

        $battleLog = [];
        $attackerHP = $attackerStats['health'];
        $defenderHP = $defenderStats['health'];
        $round = 1;

        while ($attackerHP > 0 && $defenderHP > 0 && $round <= 20) {
            // Attacker's turn
            $attackDamage = $this->calculateDamage($attackerStats, $defenderStats);
            $defenderHP -= $attackDamage;

            $battleLog[] = [
                'round' => $round,
                'actor' => 'attacker',
                'action' => 'attack',
                'damage' => $attackDamage,
                'remaining_hp' => max(0, $defenderHP),
            ];

            if ($defenderHP <= 0) {
                break;
            }

            // Defender's turn
            $defenseDamage = $this->calculateDamage($defenderStats, $attackerStats);
            $attackerHP -= $defenseDamage;

            $battleLog[] = [
                'round' => $round,
                'actor' => 'defender',
                'action' => 'attack',
                'damage' => $defenseDamage,
                'remaining_hp' => max(0, $attackerHP),
            ];

            $round++;
        }

        $winnerId = $attackerHP > $defenderHP ? $attacker->id : ($battle->defender_id ?? null);
        $expGained = max(10, $battle->opponent_level ? $battle->opponent_level * 10 : 50);
        $goldGained = max(20, $battle->opponent_level ? $battle->opponent_level * 5 : 25);

        $battle->update([
            'winner_id' => $winnerId,
            'battle_log' => $battleLog,
            'experience_gained' => $winnerId == $attacker->id ? $expGained : 0,
            'gold_gained' => $winnerId == $attacker->id ? $goldGained : 0,
            'completed_at' => now(),
        ]);

        if ($winnerId == $attacker->id) {
            $this->awardBattleRewards($attacker, $expGained, $goldGained);
        }

        $attacker->update([
            'last_battle_at' => now(),
            'health' => max(1, $attackerHP),
        ]);
    }

    /**
     * Calculate damage based on stats.
     */
    protected function calculateDamage(array $attackerStats, array $defenderStats): int
    {
        $baseDamage = $attackerStats['strength'] + ($attackerStats['agility'] / 2);
        $defense = $defenderStats['defense'];
        $damage = max(1, $baseDamage - ($defense / 2));

        // Add some randomness (80-120% of calculated damage)
        $damage = $damage * (0.8 + (mt_rand(0, 40) / 100));

        return (int) round($damage);
    }

    /**
     * Generate NPC stats based on level.
     */
    protected function generateNPCStats(int $level): array
    {
        return [
            'health' => 80 + ($level * 20),
            'strength' => 8 + ($level * 2),
            'defense' => 8 + ($level * 2),
            'agility' => 8 + ($level * 1.5),
            'intelligence' => 8 + ($level * 1.5),
        ];
    }

    /**
     * Award rewards to the winner.
     */
    protected function awardBattleRewards(Player $player, int $exp, int $gold): void
    {
        DB::transaction(function () use ($player, $exp, $gold) {
            $player->increment('experience', $exp);

            // Update gold resource
            $goldResource = $player->resources()->where('resource_type', 'gold')->first();
            if ($goldResource) {
                $goldResource->increment('quantity', $gold);
            } else {
                $player->resources()->create([
                    'resource_type' => 'gold',
                    'quantity' => $gold,
                ]);
            }

            // Check for level up
            $this->checkLevelUp($player);
        });
    }

    /**
     * Check and process level up.
     */
    protected function checkLevelUp(Player $player): void
    {
        $experienceRequired = $player->level * 100;

        while ($player->experience >= $experienceRequired) {
            $player->increment('level');
            $player->decrement('experience', $experienceRequired);
            $player->increment('stat_points', 5);
            $player->increment('max_health', 10);
            $player->increment('max_mana', 5);
            $player->update(['health' => $player->max_health, 'mana' => $player->max_mana]);

            $experienceRequired = $player->level * 100;
        }
    }

    /**
     * Heal player to full health.
     */
    public function healPlayer(Player $player): void
    {
        $player->update([
            'health' => $player->max_health,
            'mana' => $player->max_mana,
        ]);
    }
}
