<?php

namespace Tests\Feature;

use App\Models\DailyReward;
use App\Models\Player;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DailyRewardSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Player $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->player = Player::factory()->create([
            'experience' => 0,
        ]);
        Sanctum::actingAs($this->player);

        // Ensure player has a gold resource
        $this->player->resources()->create([
            'resource_type' => 'gold',
            'quantity' => 0,
        ]);
    }

    #[Test]
    public function player_can_check_daily_reward_status(): void
    {
        $response = $this->getJson("/api/players/{$this->player->id}/daily-reward/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'can_claim',
                    'current_streak',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_claim' => true,
                    'current_streak' => 0,
                ],
            ]);
    }

    #[Test]
    public function player_can_claim_daily_reward(): void
    {
        $response = $this->postJson("/api/players/{$this->player->id}/daily-reward/claim");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daily reward claimed successfully!',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'player_id',
                    'day_streak',
                    'gold_rewarded',
                    'experience_rewarded',
                ],
            ]);

        $this->assertDatabaseHas('daily_rewards', [
            'player_id' => $this->player->id,
            'day_streak' => 1,
        ]);
    }

    #[Test]
    public function player_cannot_claim_reward_twice_on_same_day(): void
    {
        // Claim once
        $this->postJson("/api/players/{$this->player->id}/daily-reward/claim");

        // Try to claim again
        $response = $this->postJson("/api/players/{$this->player->id}/daily-reward/claim");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Daily reward already claimed today.',
            ]);
    }

    #[Test]
    public function streak_increases_for_consecutive_days(): void
    {
        // Create a reward for yesterday
        DailyReward::create([
            'player_id' => $this->player->id,
            'reward_date' => Carbon::yesterday(),
            'day_streak' => 3,
            'gold_rewarded' => 160,
            'experience_rewarded' => 80,
        ]);

        $response = $this->postJson("/api/players/{$this->player->id}/daily-reward/claim");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'day_streak' => 4,
                ],
            ]);
    }

    #[Test]
    public function streak_resets_after_missing_a_day(): void
    {
        // Create a reward from 2 days ago (streak broken)
        DailyReward::create([
            'player_id' => $this->player->id,
            'reward_date' => Carbon::today()->subDays(2),
            'day_streak' => 5,
            'gold_rewarded' => 200,
            'experience_rewarded' => 100,
        ]);

        $response = $this->postJson("/api/players/{$this->player->id}/daily-reward/claim");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'day_streak' => 1,
                ],
            ]);
    }

    #[Test]
    public function status_shows_cannot_claim_after_claiming(): void
    {
        // Claim reward
        $this->postJson("/api/players/{$this->player->id}/daily-reward/claim");

        // Check status
        $response = $this->getJson("/api/players/{$this->player->id}/daily-reward/status");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_claim' => false,
                    'current_streak' => 1,
                ],
            ]);
    }
}
