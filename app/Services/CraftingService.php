<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Player;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

class CraftingService
{
    /**
     * Attempt to craft an item using a recipe.
     */
    public function craftItem(Player $player, Recipe $recipe): array
    {
        // Check if player knows the recipe
        if (! $player->recipes()->where('recipe_id', $recipe->id)->exists()) {
            return [
                'success' => false,
                'message' => 'You have not learned this recipe.',
            ];
        }

        // Check level requirement
        if ($player->level < $recipe->min_level) {
            return [
                'success' => false,
                'message' => "You need to be level {$recipe->min_level} to craft this.",
            ];
        }

        // Check materials
        foreach ($recipe->materials as $material) {
            $playerItem = $player->playerItems()
                ->where('item_id', $material->item_id)
                ->first();

            if (! $playerItem || $playerItem->quantity < $material->quantity) {
                return [
                    'success' => false,
                    'message' => 'You do not have enough materials.',
                ];
            }
        }

        // Determine success based on success rate
        $succeeded = mt_rand(1, 100) <= $recipe->success_rate;

        DB::transaction(function () use ($player, $recipe, $succeeded) {
            // Remove materials
            foreach ($recipe->materials as $material) {
                $playerItem = $player->playerItems()
                    ->where('item_id', $material->item_id)
                    ->first();

                $newQuantity = $playerItem->quantity - $material->quantity;

                if ($newQuantity <= 0) {
                    $playerItem->delete();
                } else {
                    $playerItem->update(['quantity' => $newQuantity]);
                }
            }

            // Add result item if successful
            if ($succeeded) {
                $existingItem = $player->playerItems()
                    ->where('item_id', $recipe->result_item_id)
                    ->first();

                if ($existingItem) {
                    $existingItem->increment('quantity', $recipe->result_quantity);
                } else {
                    $player->playerItems()->create([
                        'item_id' => $recipe->result_item_id,
                        'quantity' => $recipe->result_quantity,
                    ]);
                }
            }
        });

        return [
            'success' => $succeeded,
            'message' => $succeeded
                ? 'Crafting successful!'
                : 'Crafting failed. Materials were consumed.',
            'item' => $succeeded ? $recipe->resultItem : null,
        ];
    }

    /**
     * Learn a new recipe.
     */
    public function learnRecipe(Player $player, Recipe $recipe): bool
    {
        if ($player->recipes()->where('recipe_id', $recipe->id)->exists()) {
            return false;
        }

        $player->recipes()->attach($recipe->id, [
            'learned_at' => now(),
        ]);

        return true;
    }
}
