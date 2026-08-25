<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Quest;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some items for quest rewards
        $sword = Item::firstOrCreate(
            ['name' => 'Iron Sword'],
            ['description' => 'A sturdy iron sword for combat', 'type' => 'weapon', 'rarity' => 'common']
        );

        $shield = Item::firstOrCreate(
            ['name' => 'Wooden Shield'],
            ['description' => 'A basic wooden shield for defense', 'type' => 'armor', 'rarity' => 'common']
        );

        $potion = Item::firstOrCreate(
            ['name' => 'Health Potion'],
            ['description' => 'Restores health when consumed', 'type' => 'consumable', 'rarity' => 'common']
        );

        // Create sample quests
        Quest::firstOrCreate(
            ['name' => 'Beginner\'s Trial'],
            [
                'description' => 'Complete your first challenge in the game world. This quest will help you understand the basics of questing.',
                'experience_reward' => 50,
                'item_reward_id' => $sword->id,
            ]
        );

        Quest::firstOrCreate(
            ['name' => 'Defend the Village'],
            [
                'description' => 'The village is under attack! Help defend it from incoming enemies. Your bravery will be rewarded.',
                'experience_reward' => 100,
                'item_reward_id' => $shield->id,
            ]
        );

        Quest::firstOrCreate(
            ['name' => 'Gather Herbs'],
            [
                'description' => 'The village healer needs medicinal herbs. Gather 10 herbs from the nearby forest.',
                'experience_reward' => 75,
                'item_reward_id' => $potion->id,
            ]
        );

        Quest::firstOrCreate(
            ['name' => 'Explore the Cave'],
            [
                'description' => 'A mysterious cave has been discovered. Explore it and report back what you find.',
                'experience_reward' => 150,
                'item_reward_id' => null,
            ]
        );

        Quest::firstOrCreate(
            ['name' => 'Defeat the Boss'],
            [
                'description' => 'A powerful boss has emerged. Gather your strength and defeat it to bring peace to the land.',
                'experience_reward' => 250,
                'item_reward_id' => $sword->id,
            ]
        );

        Quest::firstOrCreate(
            ['name' => 'Collect Resources'],
            [
                'description' => 'The kingdom needs resources. Collect wood, stone, and ore from the surrounding areas.',
                'experience_reward' => 120,
                'item_reward_id' => null,
            ]
        );

        Quest::firstOrCreate(
            ['name' => 'Master the Skills'],
            [
                'description' => 'Train your skills to become a master. Complete various challenges to prove your worth.',
                'experience_reward' => 200,
                'item_reward_id' => null,
            ]
        );

        $this->command->info('Quest seeder completed successfully!');
    }
}
