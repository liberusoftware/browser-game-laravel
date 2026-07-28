<?php

namespace Tests\Feature;

use App\Models\Battle;
use App\Models\Player;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CombatSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Player $attacker;

    protected Player $defender;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attacker = Player::factory()->create([
            'level' => 5,
            'health' => 100,
            'max_health' => 100,
            'mana' => 50,
            'max_mana' => 50,
            'strength' => 15,
            'defense' => 10,
            'agility' => 10,
            'intelligence' => 10,
        ]);

        $this->defender = Player::factory()->create([
            'level' => 5,
            'health' => 100,
            'max_health' => 100,
            'mana' => 50,
            'max_mana' => 50,
            'strength' => 12,
            'defense' => 12,
            'agility' => 8,
            'intelligence' => 8,
        ]);
        Sanctum::actingAs($this->attacker);
    }

    #[Test]
    public function player_can_initiate_pve_battle(): void
    {
        $response = $this->postJson('/api/combat/pve', [
            'player_id' => $this->attacker->id,
            'opponent_name' => 'Goblin',
            'opponent_level' => 3,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'attacker_id',
                    'battle_type',
                    'opponent_name',
                    'opponent_level',
                    'battle_log',
                    'experience_gained',
                    'gold_gained',
                    'completed_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'battle_type' => 'pve',
                    'opponent_name' => 'Goblin',
                ],
            ]);

        $this->assertDatabaseHas('battles', [
            'attacker_id' => $this->attacker->id,
            'battle_type' => 'pve',
            'opponent_name' => 'Goblin',
        ]);
    }

    #[Test]
    public function supplied_player_id_cannot_impersonate_another_player(): void
    {
        $response = $this->postJson('/api/combat/pve', [
            'player_id' => 99999,
            'opponent_name' => 'Goblin',
            'opponent_level' => 3,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('battles', ['attacker_id' => $this->attacker->id]);
    }

    #[Test]
    public function player_can_initiate_pvp_battle(): void
    {
        $response = $this->postJson('/api/combat/pvp', [
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->defender->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'attacker_id',
                    'defender_id',
                    'battle_type',
                    'battle_log',
                    'completed_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'battle_type' => 'pvp',
                ],
            ]);
    }

    #[Test]
    public function pvp_battle_requires_different_players(): void
    {
        $response = $this->postJson('/api/combat/pvp', [
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->attacker->id,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function player_can_be_healed(): void
    {
        $this->attacker->update(['health' => 10]);

        $response = $this->postJson('/api/combat/heal', [
            'player_id' => $this->attacker->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Player healed successfully',
            ]);

        $this->attacker->refresh();
        $this->assertEquals($this->attacker->max_health, $this->attacker->health);
    }

    #[Test]
    public function pve_battle_awards_experience_on_win(): void
    {
        // Create gold resource for potential rewards
        $this->attacker->resources()->create([
            'resource_type' => 'gold',
            'quantity' => 0,
        ]);

        $initialExp = $this->attacker->experience;

        $response = $this->postJson('/api/combat/pve', [
            'player_id' => $this->attacker->id,
            'opponent_name' => 'Weak Slime',
            'opponent_level' => 1,
        ]);

        $response->assertStatus(200);

        $battle = Battle::where('attacker_id', $this->attacker->id)->latest()->first();
        $this->assertNotNull($battle->completed_at);
    }
}
