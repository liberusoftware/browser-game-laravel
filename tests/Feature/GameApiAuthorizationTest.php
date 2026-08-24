<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\BrowserGame\Accounts\AccountsServiceProvider;
use Liberu\BrowserGame\Accounts\Support\AccountsManager;
use Liberu\BrowserGame\AccountsApi\AccountsApiServiceProvider;
use Tests\TestCase;

class GameApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_mutations_require_authentication(): void
    {
        $this->postJson('/api/combat/heal', ['player_id' => 1])
            ->assertUnauthorized();

        $this->postJson('/api/marketplace', [
            'seller_id' => 1,
            'item_id' => 1,
            'quantity' => 1,
            'price_per_unit' => 1,
        ])->assertUnauthorized();

        $this->postJson('/api/quests/1/accept', ['player_id' => 1])
            ->assertUnauthorized();
    }

    public function test_authenticated_player_cannot_read_another_players_private_data(): void
    {
        $player = Player::factory()->create();
        $otherPlayer = Player::factory()->create();
        Sanctum::actingAs($player);

        $this->getJson("/api/players/{$otherPlayer->id}/statistics")
            ->assertForbidden();

        $this->getJson("/api/players/{$otherPlayer->id}/recipes")
            ->assertForbidden();

        $this->postJson("/api/players/{$otherPlayer->id}/daily-reward/claim")
            ->assertForbidden();
    }

    public function test_client_supplied_seller_id_cannot_select_the_actor(): void
    {
        $player = Player::factory()->create();
        $victim = Player::factory()->create();
        $item = Item::factory()->create();
        $victim->playerItems()->create(['item_id' => $item->id, 'quantity' => 5]);
        Sanctum::actingAs($player);

        $this->postJson('/api/marketplace', [
            'seller_id' => $victim->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'price_per_unit' => 10,
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('marketplace_listings', [
            'seller_id' => $victim->id,
        ]);
    }

    public function test_browser_game_account_reads_are_scoped_to_the_current_team(): void
    {
        $this->app->register(AccountsServiceProvider::class);
        $this->app->register(AccountsApiServiceProvider::class);
        $this->artisan('migrate');

        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $otherTeam = Team::factory()->create();
        $user->forceFill(['current_team_id' => $team->getKey()])->save();

        $manager = app(AccountsManager::class);
        $visible = $manager->define('Visible account', teamId: (string) $team->getKey());
        $hidden = $manager->define('Hidden account', teamId: (string) $otherTeam->getKey());
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/browser-game/accounts/'.$visible->getKey())
            ->assertOk()
            ->assertJsonPath('data.id', (string) $visible->getKey());

        $this->getJson('/api/v1/browser-game/accounts/'.$hidden->getKey())
            ->assertNotFound();
    }
}
