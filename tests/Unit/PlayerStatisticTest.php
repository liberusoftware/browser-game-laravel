<?php

namespace Tests\Unit;

use App\Models\Player;
use App\Models\PlayerStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerStatisticTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_statistic_can_be_created(): void
    {
        $player = Player::create([
            'username' => 'testplayer',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'level' => 1,
            'experience' => 0,
        ]);

        $statistic = PlayerStatistic::create([
            'player_id' => $player->id,
            'total_quests_completed' => 5,
            'total_items_collected' => 10,
            'total_playtime_minutes' => 120,
            'highest_level_achieved' => 5,
            'total_experience_earned' => 500,
        ]);

        $this->assertDatabaseHas('player_statistics', [
            'player_id' => $player->id,
            'total_quests_completed' => 5,
            'highest_level_achieved' => 5,
        ]);
    }

    public function test_player_statistic_belongs_to_player(): void
    {
        $player = Player::create([
            'username' => 'testplayer',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'level' => 1,
            'experience' => 0,
        ]);

        $statistic = PlayerStatistic::create([
            'player_id' => $player->id,
            'total_quests_completed' => 10,
        ]);

        $this->assertEquals($player->id, $statistic->player->id);
    }

    public function test_player_has_statistics_relationship(): void
    {
        $player = Player::create([
            'username' => 'testplayer',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'level' => 1,
            'experience' => 0,
        ]);

        $statistic = $player->statistics()->create([
            'total_quests_completed' => 15,
            'total_items_collected' => 25,
        ]);

        $this->assertEquals(15, $player->statistics->total_quests_completed);
    }

    public function test_player_statistic_casts_are_correct(): void
    {
        $player = Player::factory()->create();

        $statistic = PlayerStatistic::create([
            'player_id' => $player->id,
            'total_quests_completed' => 10,
            'total_items_collected' => 20,
            'total_playtime_minutes' => 300,
            'highest_level_achieved' => 10,
            'total_experience_earned' => 1000,
        ]);

        $this->assertIsInt($statistic->total_quests_completed);
        $this->assertIsInt($statistic->total_items_collected);
        $this->assertIsInt($statistic->total_playtime_minutes);
        $this->assertIsInt($statistic->highest_level_achieved);
        $this->assertIsInt($statistic->total_experience_earned);
    }
}
