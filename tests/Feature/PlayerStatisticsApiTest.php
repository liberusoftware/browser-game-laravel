<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Player_Quest;
use App\Models\Quest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlayerStatisticsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_player_statistics(): void
    {
        $player = Player::factory()->create([
            'level' => 10,
            'experience' => 1000,
        ]);
        Sanctum::actingAs($player);

        $player->statistics()->create([
            'total_quests_completed' => 5,
            'total_items_collected' => 10,
            'highest_level_achieved' => 10,
        ]);

        $response = $this->getJson("/api/players/{$player->id}/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'player_id',
                    'total_quests_completed',
                    'total_items_collected',
                    'highest_level_achieved',
                ],
            ]);
    }

    public function test_creates_statistics_if_not_exists(): void
    {
        $player = Player::factory()->create([
            'level' => 5,
            'experience' => 500,
        ]);
        Sanctum::actingAs($player);

        $this->assertDatabaseMissing('player_statistics', [
            'player_id' => $player->id,
        ]);

        $response = $this->getJson("/api/players/{$player->id}/statistics");

        $response->assertStatus(200);

        $this->assertDatabaseHas('player_statistics', [
            'player_id' => $player->id,
            'highest_level_achieved' => 5,
            'total_experience_earned' => 500,
        ]);
    }

    public function test_can_get_player_progression_summary(): void
    {
        $player = Player::factory()->create([
            'level' => 15,
            'experience' => 2000,
        ]);
        Sanctum::actingAs($player);

        $quest = Quest::factory()->create();

        Player_Quest::create([
            'player_id' => $player->id,
            'quest_id' => $quest->id,
            'status' => 'completed',
            'progress_percentage' => 100,
        ]);

        $response = $this->getJson("/api/players/{$player->id}/progression");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'level',
                    'experience',
                    'quests_completed',
                    'quests_in_progress',
                    'total_items',
                    'achievements_unlocked',
                ],
            ])
            ->assertJson([
                'data' => [
                    'level' => 15,
                    'experience' => 2000,
                    'quests_completed' => 1,
                    'quests_in_progress' => 0,
                ],
            ]);
    }
}
