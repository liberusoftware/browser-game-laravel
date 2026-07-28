<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Player;
use App\Models\Player_Item;
use App\Models\Recipe;
use App\Models\RecipeMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CraftingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Player $player;

    protected Recipe $recipe;

    protected Item $material;

    protected Item $resultItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->player = Player::factory()->create(['level' => 5]);
        Sanctum::actingAs($this->player);

        $this->material = Item::factory()->create(['name' => 'Iron Ore']);
        $this->resultItem = Item::factory()->create(['name' => 'Iron Sword']);

        $this->recipe = Recipe::create([
            'name' => 'Iron Sword Recipe',
            'description' => 'Craft an iron sword',
            'result_item_id' => $this->resultItem->id,
            'result_quantity' => 1,
            'min_level' => 1,
            'success_rate' => 100,
        ]);

        RecipeMaterial::create([
            'recipe_id' => $this->recipe->id,
            'item_id' => $this->material->id,
            'quantity' => 3,
        ]);

        // Give player the recipe and materials
        $this->player->recipes()->attach($this->recipe->id, ['learned_at' => now()]);

        Player_Item::create([
            'player_id' => $this->player->id,
            'item_id' => $this->material->id,
            'quantity' => 10,
        ]);
    }

    #[Test]
    public function player_can_craft_an_item(): void
    {
        $response = $this->postJson("/api/crafting/recipes/{$this->recipe->id}/craft", [
            'player_id' => $this->player->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Crafting successful!',
            ]);

        // Verify materials were consumed
        $this->assertDatabaseHas('player__items', [
            'player_id' => $this->player->id,
            'item_id' => $this->material->id,
            'quantity' => 7,
        ]);

        // Verify result item was added
        $this->assertDatabaseHas('player__items', [
            'player_id' => $this->player->id,
            'item_id' => $this->resultItem->id,
            'quantity' => 1,
        ]);
    }

    #[Test]
    public function player_cannot_craft_unknown_recipe(): void
    {
        $otherPlayer = Player::factory()->create(['level' => 5]);
        Sanctum::actingAs($otherPlayer);

        $response = $this->postJson("/api/crafting/recipes/{$this->recipe->id}/craft", [
            'player_id' => $otherPlayer->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You have not learned this recipe.',
            ]);
    }

    #[Test]
    public function player_cannot_craft_without_enough_materials(): void
    {
        // Remove materials
        Player_Item::where('player_id', $this->player->id)
            ->where('item_id', $this->material->id)
            ->update(['quantity' => 1]);

        $response = $this->postJson("/api/crafting/recipes/{$this->recipe->id}/craft", [
            'player_id' => $this->player->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You do not have enough materials.',
            ]);
    }

    #[Test]
    public function player_can_learn_a_recipe(): void
    {
        $newRecipe = Recipe::create([
            'name' => 'Steel Shield',
            'description' => 'Craft a steel shield',
            'result_item_id' => $this->resultItem->id,
            'result_quantity' => 1,
            'min_level' => 1,
            'success_rate' => 100,
        ]);

        $response = $this->postJson("/api/crafting/recipes/{$newRecipe->id}/learn", [
            'player_id' => $this->player->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Recipe learned successfully',
            ]);

        $this->assertDatabaseHas('player_recipes', [
            'player_id' => $this->player->id,
            'recipe_id' => $newRecipe->id,
        ]);
    }

    #[Test]
    public function player_cannot_learn_same_recipe_twice(): void
    {
        $response = $this->postJson("/api/crafting/recipes/{$this->recipe->id}/learn", [
            'player_id' => $this->player->id,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Recipe already known',
            ]);
    }

    #[Test]
    public function can_get_player_recipes(): void
    {
        $response = $this->getJson("/api/players/{$this->player->id}/recipes");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'description'],
                ],
            ])
            ->assertJson(['success' => true]);

        $this->assertCount(1, $response->json('data'));
    }
}
