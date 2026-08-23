<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PlayerDashboard;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_successfully()
    {
        $player = Player::factory()->create([
            'username' => 'Test Player',
            'email' => 'test@example.com',
            'level' => 5,
            'experience' => 250,
        ]);

        Livewire::test(PlayerDashboard::class)
            ->assertStatus(200)
            ->assertSee('Test Player');
    }

    public function test_experience_percentage_calculated_correctly()
    {
        $player = Player::factory()->create([
            'level' => 2,
            'experience' => 150, // Level 2 needs 200 XP, so 150 is 75% progress
        ]);

        Livewire::test(PlayerDashboard::class)
            ->assertSet('player.level', 2)
            ->assertSet('player.experience', 150);
    }

    public function test_player_levels_up_on_quest_completion()
    {
        $player = Player::factory()->create([
            'level' => 1,
            'experience' => 90, // Close to level up (needs 100)
        ]);

        Livewire::test(PlayerDashboard::class)
            ->call('handleQuestCompleted', 1, 20)
            ->assertSet('player.level', 2);
    }

    public function test_component_refreshes_player_data()
    {
        $player = Player::factory()->create();

        Livewire::test(PlayerDashboard::class)
            ->call('refreshPlayer')
            ->assertDispatched('player-stats-updated');
    }

    public function test_component_displays_player_stats()
    {
        $player = Player::factory()->create([
            'username' => 'Hero123',
            'level' => 10,
            'experience' => 950,
        ]);

        Livewire::test(PlayerDashboard::class)
            ->assertSee('Hero123')
            ->assertSee('10')
            ->assertSee('950');
    }
}
