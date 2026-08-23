<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $achievements = [
            [
                'name' => 'First Steps',
                'description' => 'Reach level 5',
                'icon' => 'heroicon-o-academic-cap',
                'points' => 10,
                'requirement_type' => 'level',
                'requirement_value' => 5,
            ],
            [
                'name' => 'Rising Star',
                'description' => 'Reach level 10',
                'icon' => 'heroicon-o-star',
                'points' => 25,
                'requirement_type' => 'level',
                'requirement_value' => 10,
            ],
            [
                'name' => 'Veteran Adventurer',
                'description' => 'Reach level 25',
                'icon' => 'heroicon-o-shield-check',
                'points' => 50,
                'requirement_type' => 'level',
                'requirement_value' => 25,
            ],
            [
                'name' => 'Master Explorer',
                'description' => 'Reach level 50',
                'icon' => 'heroicon-o-trophy',
                'points' => 100,
                'requirement_type' => 'level',
                'requirement_value' => 50,
            ],
            [
                'name' => 'Quest Beginner',
                'description' => 'Complete 5 quests',
                'icon' => 'heroicon-o-map',
                'points' => 15,
                'requirement_type' => 'quests_completed',
                'requirement_value' => 5,
            ],
            [
                'name' => 'Quest Enthusiast',
                'description' => 'Complete 25 quests',
                'icon' => 'heroicon-o-clipboard-document-check',
                'points' => 50,
                'requirement_type' => 'quests_completed',
                'requirement_value' => 25,
            ],
            [
                'name' => 'Quest Master',
                'description' => 'Complete 100 quests',
                'icon' => 'heroicon-o-trophy',
                'points' => 150,
                'requirement_type' => 'quests_completed',
                'requirement_value' => 100,
            ],
            [
                'name' => 'Collector',
                'description' => 'Collect 50 items',
                'icon' => 'heroicon-o-cube',
                'points' => 20,
                'requirement_type' => 'items_collected',
                'requirement_value' => 50,
            ],
            [
                'name' => 'Hoarder',
                'description' => 'Collect 200 items',
                'icon' => 'heroicon-o-inbox-stack',
                'points' => 75,
                'requirement_type' => 'items_collected',
                'requirement_value' => 200,
            ],
            [
                'name' => 'Experience Seeker',
                'description' => 'Earn 10,000 experience points',
                'icon' => 'heroicon-o-bolt',
                'points' => 30,
                'requirement_type' => 'experience',
                'requirement_value' => 10000,
            ],
            [
                'name' => 'Experience Hoarder',
                'description' => 'Earn 100,000 experience points',
                'icon' => 'heroicon-o-fire',
                'points' => 100,
                'requirement_type' => 'experience',
                'requirement_value' => 100000,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::firstOrCreate(
                ['name' => $achievement['name']],
                $achievement
            );
        }
    }
}
