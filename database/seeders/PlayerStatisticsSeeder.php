<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\PlayerStatistic;
use Illuminate\Database\Seeder;

class PlayerStatisticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $players = Player::all();

        foreach ($players as $player) {
            // Skip if statistics already exist
            if ($player->statistics) {
                continue;
            }

            // Count actual player data
            $questsCompleted = $player->quests()->where('status', 'completed')->count();
            $questsInProgress = $player->quests()->where('status', 'in-progress')->count();
            $itemsCollected = $player->items()->sum('quantity') ?? 0;
            $achievementsUnlocked = $player->achievements()
                ->whereNotNull('player_achievements.unlocked_at')
                ->count();

            // Create statistics record
            PlayerStatistic::create([
                'player_id' => $player->id,
                'total_quests_completed' => $questsCompleted,
                'total_items_collected' => $itemsCollected,
                'total_playtime_minutes' => rand(0, 5000), // Random for demo
                'highest_level_achieved' => $player->level,
                'total_experience_earned' => $player->experience,
                'quests_in_progress' => $questsInProgress,
                'achievements_unlocked' => $achievementsUnlocked,
            ]);
        }
    }
}
