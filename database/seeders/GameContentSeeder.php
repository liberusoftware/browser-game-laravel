<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Recipe;
use App\Models\RecipeMaterial;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class GameContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->createSkills();
        $this->createCraftingRecipes();
    }

    private function createSkills(): void
    {
        $skills = [
            [
                'name' => 'Power Strike',
                'description' => 'A powerful melee attack that deals heavy damage.',
                'type' => 'attack',
                'mana_cost' => 10,
                'cooldown_seconds' => 5,
                'power' => 50,
                'min_level' => 1,
            ],
            [
                'name' => 'Fireball',
                'description' => 'Launch a ball of fire at your enemy.',
                'type' => 'attack',
                'mana_cost' => 20,
                'cooldown_seconds' => 8,
                'power' => 80,
                'min_level' => 5,
            ],
            [
                'name' => 'Shield Block',
                'description' => 'Temporarily increase your defense.',
                'type' => 'defense',
                'mana_cost' => 15,
                'cooldown_seconds' => 10,
                'power' => 30,
                'min_level' => 3,
            ],
            [
                'name' => 'Heal',
                'description' => 'Restore health over time.',
                'type' => 'heal',
                'mana_cost' => 25,
                'cooldown_seconds' => 15,
                'power' => 50,
                'min_level' => 2,
            ],
            [
                'name' => 'Lightning Strike',
                'description' => 'Call down lightning to strike your foe.',
                'type' => 'attack',
                'mana_cost' => 35,
                'cooldown_seconds' => 12,
                'power' => 120,
                'min_level' => 10,
            ],
            [
                'name' => 'Battle Cry',
                'description' => 'Increase strength temporarily.',
                'type' => 'buff',
                'mana_cost' => 20,
                'cooldown_seconds' => 20,
                'power' => 25,
                'min_level' => 7,
            ],
        ];

        foreach ($skills as $skill) {
            Skill::create($skill);
        }
    }

    private function createCraftingRecipes(): void
    {
        // Get items for crafting
        $healthPotion = Item::where('name', 'Health Potion')->first();
        $ironSword = Item::where('name', 'Iron Sword')->first();
        $leatherArmor = Item::where('name', 'Leather Armor')->first();

        // Create some crafting materials
        $woodItem = Item::create([
            'name' => 'Wood',
            'description' => 'Basic crafting material gathered from trees.',
            'type' => 'material',
            'rarity' => 'common',
            'sell_price' => 5,
            'buy_price' => 10,
        ]);

        $ironOre = Item::create([
            'name' => 'Iron Ore',
            'description' => 'Raw iron ore used for smithing.',
            'type' => 'material',
            'rarity' => 'common',
            'sell_price' => 10,
            'buy_price' => 20,
        ]);

        $leather = Item::create([
            'name' => 'Leather',
            'description' => 'Tanned leather for armor crafting.',
            'type' => 'material',
            'rarity' => 'common',
            'sell_price' => 8,
            'buy_price' => 15,
        ]);

        $herbs = Item::create([
            'name' => 'Healing Herbs',
            'description' => 'Medicinal herbs used in potion making.',
            'type' => 'material',
            'rarity' => 'common',
            'sell_price' => 3,
            'buy_price' => 7,
        ]);

        // Create recipes
        if ($healthPotion) {
            $recipe = Recipe::create([
                'name' => 'Brew Health Potion',
                'description' => 'Create a potion that restores health.',
                'result_item_id' => $healthPotion->id,
                'result_quantity' => 1,
                'min_level' => 1,
                'success_rate' => 90,
                'crafting_time_seconds' => 30,
            ]);

            RecipeMaterial::create([
                'recipe_id' => $recipe->id,
                'item_id' => $herbs->id,
                'quantity' => 3,
            ]);
        }

        if ($ironSword) {
            $recipe = Recipe::create([
                'name' => 'Forge Iron Sword',
                'description' => 'Craft a basic iron sword.',
                'result_item_id' => $ironSword->id,
                'result_quantity' => 1,
                'min_level' => 5,
                'success_rate' => 80,
                'crafting_time_seconds' => 120,
            ]);

            RecipeMaterial::create([
                'recipe_id' => $recipe->id,
                'item_id' => $ironOre->id,
                'quantity' => 5,
            ]);

            RecipeMaterial::create([
                'recipe_id' => $recipe->id,
                'item_id' => $woodItem->id,
                'quantity' => 2,
            ]);
        }

        if ($leatherArmor) {
            $recipe = Recipe::create([
                'name' => 'Craft Leather Armor',
                'description' => 'Create basic leather armor.',
                'result_item_id' => $leatherArmor->id,
                'result_quantity' => 1,
                'min_level' => 3,
                'success_rate' => 85,
                'crafting_time_seconds' => 90,
            ]);

            RecipeMaterial::create([
                'recipe_id' => $recipe->id,
                'item_id' => $leather->id,
                'quantity' => 6,
            ]);
        }
    }
}
