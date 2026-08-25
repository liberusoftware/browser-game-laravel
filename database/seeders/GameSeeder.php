<?php

namespace Database\Seeders;

use App\Models\Guild;
use App\Models\Guild_Membership;
use App\Models\Item;
use App\Models\Player;
use App\Models\Player_Item;
use App\Models\Player_Profile;
use App\Models\Player_Quest;
use App\Models\Quest;
use App\Models\Resource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GameSeeder extends Seeder
{
    /**
     * Seed the game database with initial data.
     */
    public function run(): void
    {
        // Create Items first (needed for quest rewards)
        $this->createItems();

        // Create Quests
        $this->createQuests();

        // Create Guilds
        $guilds = $this->createGuilds();

        // Create Players with profiles and resources
        $players = $this->createPlayers();

        // Assign players to guilds
        $this->assignPlayersToGuilds($players, $guilds);

        // Give players some items
        $this->givePlayersItems($players);

        // Assign quests to players
        $this->assignQuestsToPlayers($players);
    }

    /**
     * Create initial items.
     */
    private function createItems(): void
    {
        // Weapons
        Item::create([
            'name' => 'Iron Sword',
            'description' => 'A basic iron sword suitable for beginners.',
            'type' => 'weapon',
            'rarity' => 'common',
        ]);

        Item::create([
            'name' => 'Steel Axe',
            'description' => 'A sharp steel axe that deals heavy damage.',
            'type' => 'weapon',
            'rarity' => 'uncommon',
        ]);

        Item::create([
            'name' => 'Enchanted Staff',
            'description' => 'A magical staff imbued with ancient power.',
            'type' => 'weapon',
            'rarity' => 'rare',
        ]);

        Item::create([
            'name' => 'Legendary Bow',
            'description' => 'A legendary bow crafted by master artisans.',
            'type' => 'weapon',
            'rarity' => 'legendary',
        ]);

        // Armor
        Item::create([
            'name' => 'Leather Armor',
            'description' => 'Basic leather armor for protection.',
            'type' => 'armor',
            'rarity' => 'common',
        ]);

        Item::create([
            'name' => 'Chainmail Armor',
            'description' => 'Sturdy chainmail that offers good protection.',
            'type' => 'armor',
            'rarity' => 'uncommon',
        ]);

        Item::create([
            'name' => 'Plate Armor',
            'description' => 'Heavy plate armor for maximum defense.',
            'type' => 'armor',
            'rarity' => 'rare',
        ]);

        // Potions
        Item::create([
            'name' => 'Health Potion',
            'description' => 'Restores a moderate amount of health.',
            'type' => 'potion',
            'rarity' => 'common',
        ]);

        Item::create([
            'name' => 'Mana Potion',
            'description' => 'Restores magical energy.',
            'type' => 'potion',
            'rarity' => 'common',
        ]);

        Item::create([
            'name' => 'Elixir of Strength',
            'description' => 'Temporarily increases strength.',
            'type' => 'potion',
            'rarity' => 'rare',
        ]);
    }

    /**
     * Create initial quests.
     */
    private function createQuests(): void
    {
        Quest::create([
            'name' => 'Defeat the Goblins',
            'description' => 'Clear the goblin camp near the village.',
            'experience_reward' => 100,
            'item_reward_id' => 1, // Iron Sword
        ]);

        Quest::create([
            'name' => 'Collect Herbs',
            'description' => 'Gather 10 medicinal herbs from the forest.',
            'experience_reward' => 50,
            'item_reward_id' => 8, // Health Potion
        ]);

        Quest::create([
            'name' => 'Rescue the Villagers',
            'description' => 'Rescue villagers captured by bandits.',
            'experience_reward' => 200,
            'item_reward_id' => 6, // Chainmail Armor
        ]);

        Quest::create([
            'name' => 'Explore the Ancient Ruins',
            'description' => 'Discover the secrets of the ancient ruins.',
            'experience_reward' => 300,
            'item_reward_id' => 3, // Enchanted Staff
        ]);

        Quest::create([
            'name' => 'Slay the Dragon',
            'description' => 'Defeat the fearsome dragon terrorizing the kingdom.',
            'experience_reward' => 1000,
            'item_reward_id' => 4, // Legendary Bow
        ]);
    }

    /**
     * Create initial guilds.
     */
    private function createGuilds(): array
    {
        $guilds = [];

        $guilds[] = Guild::create([
            'name' => 'Warriors of Light',
            'description' => 'A guild dedicated to protecting the innocent and fighting evil.',
        ]);

        $guilds[] = Guild::create([
            'name' => 'Shadow Assassins',
            'description' => 'Masters of stealth and precision strikes.',
        ]);

        $guilds[] = Guild::create([
            'name' => 'Arcane Society',
            'description' => 'A gathering of powerful mages seeking magical knowledge.',
        ]);

        $guilds[] = Guild::create([
            'name' => 'Merchant Guild',
            'description' => 'Traders and craftsmen working together for profit.',
        ]);

        return $guilds;
    }

    /**
     * Create players with profiles and resources.
     */
    private function createPlayers(): array
    {
        $players = [];

        // Create 10 sample players
        for ($i = 1; $i <= 10; $i++) {
            $player = Player::create([
                'username' => 'player'.$i,
                'email' => 'player'.$i.'@example.com',
                'password' => Hash::make('password'),
                'level' => rand(1, 50),
                'experience' => rand(0, 5000),
            ]);

            // Create profile for player
            Player_Profile::create([
                'player_id' => $player->id,
                'avatar_url' => 'https://i.pravatar.cc/150?img='.$i,
                'bio' => 'A brave adventurer seeking fortune and glory.',
            ]);

            // Give player some resources
            Resource::create([
                'player_id' => $player->id,
                'resource_type' => 'gold',
                'quantity' => rand(100, 10000),
            ]);

            Resource::create([
                'player_id' => $player->id,
                'resource_type' => 'wood',
                'quantity' => rand(50, 500),
            ]);

            Resource::create([
                'player_id' => $player->id,
                'resource_type' => 'stone',
                'quantity' => rand(50, 500),
            ]);

            $players[] = $player;
        }

        return $players;
    }

    /**
     * Assign players to guilds.
     */
    private function assignPlayersToGuilds(array $players, array $guilds): void
    {
        foreach ($guilds as $index => $guild) {
            // First player in each guild is the leader
            if (isset($players[$index * 2])) {
                Guild_Membership::create([
                    'player_id' => $players[$index * 2]->id,
                    'guild_id' => $guild->id,
                    'role' => 'leader',
                    'joined_at' => now()->subDays(rand(30, 365)),
                ]);
            }

            // Add a few members to each guild
            for ($i = 1; $i <= 2; $i++) {
                $playerIndex = ($index * 2) + $i;
                if (isset($players[$playerIndex])) {
                    Guild_Membership::create([
                        'player_id' => $players[$playerIndex]->id,
                        'guild_id' => $guild->id,
                        'role' => 'member',
                        'joined_at' => now()->subDays(rand(1, 90)),
                    ]);
                }
            }
        }
    }

    /**
     * Give players some items.
     */
    private function givePlayersItems(array $players): void
    {
        $itemIds = Item::pluck('id')->toArray();

        foreach ($players as $player) {
            // Give each player 3-5 random items
            $numItems = rand(3, 5);
            $randomItems = array_rand(array_flip($itemIds), $numItems);

            foreach ($randomItems as $itemId) {
                Player_Item::create([
                    'player_id' => $player->id,
                    'item_id' => $itemId,
                    'quantity' => rand(1, 10),
                ]);
            }
        }
    }

    /**
     * Assign quests to players.
     */
    private function assignQuestsToPlayers(array $players): void
    {
        $questIds = Quest::pluck('id')->toArray();

        foreach ($players as $player) {
            // Give each player 2-4 quests
            $numQuests = rand(2, 4);
            $randomQuests = array_rand(array_flip($questIds), $numQuests);

            foreach ($randomQuests as $questId) {
                Player_Quest::create([
                    'player_id' => $player->id,
                    'quest_id' => $questId,
                    'status' => rand(0, 1) ? 'in-progress' : 'completed',
                ]);
            }
        }
    }
}
