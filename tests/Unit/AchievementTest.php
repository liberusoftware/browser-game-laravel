<?php

namespace Tests\Unit;

use App\Models\Achievement;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    public function test_achievement_can_be_created(): void
    {
        $achievement = Achievement::create([
            'name' => 'First Steps',
            'description' => 'Reach level 5',
            'points' => 10,
            'requirement_type' => 'level',
            'requirement_value' => 5,
        ]);

        $this->assertDatabaseHas('achievements', [
            'name' => 'First Steps',
            'requirement_type' => 'level',
            'requirement_value' => 5,
        ]);
    }

    public function test_achievement_has_players_relationship(): void
    {
        $achievement = Achievement::create([
            'name' => 'Quest Master',
            'description' => 'Complete 10 quests',
            'points' => 50,
            'requirement_type' => 'quests_completed',
            'requirement_value' => 10,
        ]);

        $player = Player::create([
            'username' => 'testplayer',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'level' => 1,
            'experience' => 0,
        ]);

        $achievement->players()->attach($player->id, [
            'progress' => 50,
            'unlocked_at' => null,
        ]);

        $this->assertTrue($achievement->players->contains($player));
        $this->assertEquals(50, $achievement->players->first()->pivot->progress);
    }

    public function test_achievement_casts_are_correct(): void
    {
        $achievement = Achievement::factory()->create([
            'points' => 100,
            'requirement_value' => 20,
        ]);

        $this->assertIsInt($achievement->points);
        $this->assertIsInt($achievement->requirement_value);
    }
}
