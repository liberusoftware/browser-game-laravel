<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Item;
use App\Models\Player;
use App\Models\Player_Item;
use App\Models\Player_Quest;
use App\Models\PlayerStatistic;
use App\Models\Quest;
use Illuminate\Database\Seeder;

class PlayerTrackingDemoSeeder extends Seeder
{
    /**
     * Seed the database with demo data for player tracking features.
     */
    public function run(): void
    {
        // Create achievements
        $this->call(AchievementSeeder::class);

        // Create some quests if they don't exist
        $quests = [];
        for ($i = 1; $i <= 5; $i++) {
            $quests[] = Quest::firstOrCreate(
                ['name' => "Demo Quest {$i}"],
                [
                    'description' => "This is demo quest {$i} for testing player tracking",
                    'experience_reward' => 100 * $i,
                ]
            );
        }

        // Create some items if they don't exist
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = Item::firstOrCreate(
                ['name' => "Demo Item {$i}"],
                [
                    'description' => "This is demo item {$i} for testing",
                    'type' => 'common',
                ]
            );
        }

        // Create demo players with varied progression
        $demoPlayers = [
            [
                'username' => 'novice_player',
                'email' => 'novice@example.com',
                'level' => 3,
                'experience' => 250,
                'completed_quests' => 1,
                'items_count' => 5,
            ],
            [
                'username' => 'intermediate_player',
                'email' => 'intermediate@example.com',
                'level' => 12,
                'experience' => 5500,
                'completed_quests' => 8,
                'items_count' => 35,
            ],
            [
                'username' => 'advanced_player',
                'email' => 'advanced@example.com',
                'level' => 28,
                'experience' => 25000,
                'completed_quests' => 30,
                'items_count' => 120,
            ],
            [
                'username' => 'master_player',
                'email' => 'master@example.com',
                'level' => 55,
                'experience' => 125000,
                'completed_quests' => 105,
                'items_count' => 250,
            ],
        ];

        foreach ($demoPlayers as $playerData) {
            $player = Player::firstOrCreate(
                ['email' => $playerData['email']],
                [
                    'username' => $playerData['username'],
                    'password' => bcrypt('password'),
                    'level' => $playerData['level'],
                    'experience' => $playerData['experience'],
                ]
            );

            // Add completed quests
            for ($i = 0; $i < $playerData['completed_quests'] && $i < count($quests); $i++) {
                Player_Quest::firstOrCreate(
                    [
                        'player_id' => $player->id,
                        'quest_id' => $quests[$i]->id,
                    ],
                    [
                        'status' => 'completed',
                        'progress_percentage' => 100,
                        'completed_at' => now()->subDays(rand(1, 30)),
                    ]
                );
            }

            // Add in-progress quest
            if (count($quests) > $playerData['completed_quests']) {
                Player_Quest::firstOrCreate(
                    [
                        'player_id' => $player->id,
                        'quest_id' => $quests[$playerData['completed_quests']]->id,
                    ],
                    [
                        'status' => 'in-progress',
                        'progress_percentage' => rand(10, 90),
                    ]
                );
            }

            // Add items
            $itemsToAdd = min($playerData['items_count'], count($items) * 50);
            foreach ($items as $item) {
                $quantity = min(rand(1, $itemsToAdd / count($items)), 50);
                if ($quantity > 0) {
                    Player_Item::firstOrCreate(
                        [
                            'player_id' => $player->id,
                            'item_id' => $item->id,
                        ],
                        ['quantity' => $quantity]
                    );
                }
            }

            // Create statistics
            PlayerStatistic::firstOrCreate(
                ['player_id' => $player->id],
                [
                    'total_quests_completed' => $playerData['completed_quests'],
                    'total_items_collected' => $playerData['items_count'],
                    'total_playtime_minutes' => rand(100, 5000),
                    'highest_level_achieved' => $playerData['level'],
                    'total_experience_earned' => $playerData['experience'],
                    'quests_in_progress' => 1,
                    'achievements_unlocked' => 0,
                ]
            );

            // Award achievements based on player progress
            $achievements = Achievement::all();
            foreach ($achievements as $achievement) {
                $progress = 0;
                $unlocked = false;

                switch ($achievement->requirement_type) {
                    case 'level':
                        $progress = min(100, ($player->level / $achievement->requirement_value) * 100);
                        $unlocked = $player->level >= $achievement->requirement_value;
                        break;
                    case 'quests_completed':
                        $progress = min(100, ($playerData['completed_quests'] / $achievement->requirement_value) * 100);
                        $unlocked = $playerData['completed_quests'] >= $achievement->requirement_value;
                        break;
                    case 'items_collected':
                        $progress = min(100, ($playerData['items_count'] / $achievement->requirement_value) * 100);
                        $unlocked = $playerData['items_count'] >= $achievement->requirement_value;
                        break;
                    case 'experience':
                        $progress = min(100, ($player->experience / $achievement->requirement_value) * 100);
                        $unlocked = $player->experience >= $achievement->requirement_value;
                        break;
                }

                $player->achievements()->syncWithoutDetaching([
                    $achievement->id => [
                        'progress' => (int) $progress,
                        'unlocked_at' => $unlocked ? now()->subDays(rand(1, 30)) : null,
                    ],
                ]);

                // Update achievements unlocked count
                if ($unlocked) {
                    $player->statistics()->increment('achievements_unlocked');
                }
            }
        }

        $this->command->info('Player tracking demo data seeded successfully!');
    }
}
