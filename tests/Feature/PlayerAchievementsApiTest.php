<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlayerAchievementsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_achievements(): void
    {
        Sanctum::actingAs(Player::factory()->create());
        Achievement::factory()->count(3)->create();

        $response = $this->getJson('/api/achievements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'points',
                        'requirement_type',
                        'requirement_value',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_player_achievements(): void
    {
        $player = Player::factory()->create();
        Sanctum::actingAs($player);
        $achievement = Achievement::factory()->create();

        $player->achievements()->attach($achievement->id, [
            'progress' => 50,
            'unlocked_at' => null,
        ]);

        $response = $this->getJson("/api/players/{$player->id}/achievements");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'points',
                        'pivot' => [
                            'player_id',
                            'achievement_id',
                            'progress',
                            'unlocked_at',
                        ],
                    ],
                ],
            ]);
    }

    public function test_can_get_player_unlocked_achievements(): void
    {
        $player = Player::factory()->create();
        Sanctum::actingAs($player);
        $unlockedAchievement = Achievement::factory()->create(['name' => 'Unlocked Achievement']);
        $lockedAchievement = Achievement::factory()->create(['name' => 'Locked Achievement']);

        $player->achievements()->attach($unlockedAchievement->id, [
            'progress' => 100,
            'unlocked_at' => now(),
        ]);

        $player->achievements()->attach($lockedAchievement->id, [
            'progress' => 50,
            'unlocked_at' => null,
        ]);

        $response = $this->getJson("/api/players/{$player->id}/achievements/unlocked");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Unlocked Achievement']);
    }

    public function test_achievement_tracking_functionality(): void
    {
        $player = Player::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'Level 10',
            'requirement_type' => 'level',
            'requirement_value' => 10,
            'points' => 50,
        ]);

        // Attach achievement with initial progress
        $player->achievements()->attach($achievement->id, [
            'progress' => 0,
            'unlocked_at' => null,
        ]);

        $this->assertDatabaseHas('player_achievements', [
            'player_id' => $player->id,
            'achievement_id' => $achievement->id,
            'progress' => 0,
        ]);

        // Update progress
        $player->achievements()->updateExistingPivot($achievement->id, [
            'progress' => 100,
            'unlocked_at' => now(),
        ]);

        $this->assertDatabaseHas('player_achievements', [
            'player_id' => $player->id,
            'achievement_id' => $achievement->id,
            'progress' => 100,
        ]);

        $this->assertNotNull($player->achievements->first()->pivot->unlocked_at);
    }
}
