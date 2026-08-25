<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GuildPanel;
use App\Models\Guild;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuildPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_successfully()
    {
        Player::factory()->create();

        Livewire::test(GuildPanel::class)
            ->assertStatus(200);
    }

    public function test_displays_available_guilds()
    {
        $player = Player::factory()->create();
        $guild = Guild::factory()->create(['name' => 'Dragon Slayers']);

        Livewire::test(GuildPanel::class)
            ->assertSee('Dragon Slayers')
            ->assertSee('Available Guilds');
    }

    public function test_player_can_join_guild()
    {
        $player = Player::factory()->create();
        $guild = Guild::factory()->create(['name' => 'Warriors Guild']);

        Livewire::test(GuildPanel::class)
            ->call('joinGuild', $guild->id)
            ->assertDispatched('guild-joined')
            ->assertSee('joined');

        // Verify player is now in the guild
        $this->assertTrue($player->fresh()->guilds->contains($guild));
    }

    public function test_player_can_leave_guild()
    {
        $player = Player::factory()->create();
        $guild = Guild::factory()->create(['name' => 'Mages Circle']);

        // Join guild first
        $player->guilds()->attach($guild->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        Livewire::test(GuildPanel::class)
            ->call('leaveGuild', $guild->id)
            ->assertDispatched('guild-left')
            ->assertSee('left');

        // Verify player is no longer in the guild
        $this->assertFalse($player->fresh()->guilds->contains($guild));
    }

    public function test_displays_player_guilds()
    {
        $player = Player::factory()->create();
        $guild = Guild::factory()->create(['name' => 'Royal Guards']);

        $player->guilds()->attach($guild->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        Livewire::test(GuildPanel::class)
            ->assertSee('Royal Guards')
            ->assertSee('My Guilds');
    }

    public function test_can_select_guild_to_view_members()
    {
        $player = Player::factory()->create();
        $guild = Guild::factory()->create(['name' => 'Elite Squad']);
        $member = Player::factory()->create(['username' => 'GuildMate']);

        $guild->members()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        Livewire::test(GuildPanel::class)
            ->call('selectGuild', $guild->id)
            ->assertSet('selectedGuild', $guild->id)
            ->assertSee('GuildMate');
    }

    public function test_displays_guild_member_roles()
    {
        $player = Player::factory()->create();
        $guild = Guild::factory()->create();
        $leader = Player::factory()->create(['username' => 'GuildLeader']);

        $guild->members()->attach($leader->id, [
            'role' => 'leader',
            'joined_at' => now(),
        ]);

        Livewire::test(GuildPanel::class)
            ->call('selectGuild', $guild->id)
            ->assertSee('GuildLeader')
            ->assertSee('Leader');
    }

    public function test_cannot_join_guild_twice()
    {
        $player = Player::factory()->create();
        $guild = Guild::factory()->create(['name' => 'Test Guild']);

        // Join guild first
        $player->guilds()->attach($guild->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        Livewire::test(GuildPanel::class)
            ->call('joinGuild', $guild->id)
            ->assertSee('already');
    }

    public function test_guild_panel_refreshes()
    {
        $player = Player::factory()->create();

        Livewire::test(GuildPanel::class)
            ->call('refreshGuilds')
            ->assertStatus(200);
    }
}
