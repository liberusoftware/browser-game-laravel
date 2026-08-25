<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Liberu\BrowserGame\Accounts\AccountsServiceProvider;
use Liberu\BrowserGame\Accounts\Support\AccountsManager;
use Liberu\BrowserGame\AccountsApi\AccountsApiServiceProvider;
use Liberu\BrowserGame\Characters\CharactersServiceProvider;
use Liberu\BrowserGame\Characters\Support\CharactersManager;
use Liberu\BrowserGame\CharactersApi\CharactersApiServiceProvider;
use Liberu\BrowserGame\Collections\CollectionsServiceProvider;
use Liberu\BrowserGame\CollectionsApi\CollectionsApiServiceProvider;
use Liberu\BrowserGame\Commerce\CommerceServiceProvider;
use Liberu\BrowserGame\Commerce\Support\CommerceManager;
use Liberu\BrowserGame\CommerceApi\CommerceApiServiceProvider;
use Liberu\BrowserGame\Economy\EconomyServiceProvider;
use Liberu\BrowserGame\Economy\Support\EconomyManager;
use Liberu\BrowserGame\EconomyApi\EconomyApiServiceProvider;
use Liberu\BrowserGame\Quests\Models\Quest;
use Liberu\BrowserGame\Quests\QuestsServiceProvider;
use Liberu\BrowserGame\QuestsApi\QuestsApiServiceProvider;
use Liberu\BrowserGame\World\Support\WorldManager;
use Liberu\BrowserGame\World\WorldServiceProvider;
use Liberu\BrowserGame\WorldApi\WorldApiServiceProvider;
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
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 404);
    }

    public function test_browser_game_account_lifecycle_and_ban_actions_delegate_to_the_domain_manager(): void
    {
        $this->app->register(AccountsServiceProvider::class);
        $this->app->register(AccountsApiServiceProvider::class);
        $this->artisan('migrate');

        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->forceFill(['current_team_id' => $team->getKey()])->save();
        $account = app(AccountsManager::class)->define('Lifecycle account', teamId: (string) $team->getKey());
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/browser-game/accounts/'.$account->getKey().'/suspend')->assertOk()->assertJsonPath('data.attributes.status', 'suspended');
        $this->postJson('/api/v1/browser-game/accounts/'.$account->getKey().'/reactivate')->assertOk()->assertJsonPath('data.attributes.status', 'active');
        $ban = $this->postJson('/api/v1/browser-game/accounts/'.$account->getKey().'/ban', ['reason' => 'abuse'])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('browser_game_account_bans', ['id' => $ban, 'account_id' => $account->getKey()]);
        $this->postJson('/api/v1/browser-game/accounts/'.$account->getKey().'/bans/'.$ban.'/lift')->assertOk()->assertJsonPath('data.attributes.revoked_at', fn (mixed $value): bool => $value !== null);
    }

    public function test_browser_game_economy_api_exposes_faucets_sinks_and_vendor_offers(): void
    {
        $this->app->register(EconomyServiceProvider::class);
        $this->app->register(EconomyApiServiceProvider::class);
        $this->artisan('migrate');

        $user = User::factory()->create();
        Sanctum::actingAs($user);
        app(EconomyManager::class)->define('Gold', ['code' => 'GOLD']);

        $this->postJson('/api/v1/browser-game/economy/wallet/credit', ['currency_code' => 'gold', 'amount' => 100, 'idempotency_key' => 'credit-api-1'])->assertCreated();
        $vendor = $this->postJson('/api/v1/browser-game/economy/vendors', ['name' => 'General Store'])->assertCreated()->json('data.id');
        $offer = $this->postJson('/api/v1/browser-game/economy/vendors/'.$vendor.'/offers', ['item_key' => 'potion', 'currency_code' => 'gold', 'unit_price' => 10, 'stock' => 2])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/browser-game/economy/offers/'.$offer.'/purchase', ['quantity' => 1])->assertOk();
        $this->postJson('/api/v1/browser-game/economy/wallet/debit', ['currency_code' => 'gold', 'amount' => 1, 'idempotency_key' => 'debit-api-1'])->assertCreated();
    }

    public function test_browser_game_economy_wallets_are_scoped_to_the_current_team(): void
    {
        $this->app->register(EconomyServiceProvider::class);
        $this->app->register(EconomyApiServiceProvider::class);
        $this->artisan('migrate');

        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $otherTeam = Team::factory()->create();
        $user->forceFill(['current_team_id' => $team->getKey()])->save();
        $manager = app(EconomyManager::class);
        $manager->define('Team One Gold', ['code' => 'GOLD'], teamId: (string) $team->getKey());
        $manager->define('Team Two Gold', ['code' => 'GOLD'], teamId: (string) $otherTeam->getKey());
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/browser-game/economy/wallet/credit', ['currency_code' => 'gold', 'amount' => 10])->assertCreated();
        $this->getJson('/api/v1/browser-game/economy/wallet')->assertOk()->assertJsonPath('data.0.attributes.balance', 10);

        $user->forceFill(['current_team_id' => $otherTeam->getKey()])->save();
        Sanctum::actingAs($user->fresh());
        $this->postJson('/api/v1/browser-game/economy/wallet/credit', ['currency_code' => 'gold', 'amount' => 30])->assertCreated();
        $this->getJson('/api/v1/browser-game/economy/wallet')->assertOk()->assertJsonPath('data.0.attributes.balance', 30);
    }

    public function test_browser_game_accounts_and_collections_api_can_create_team_scoped_records(): void
    {
        $this->app->register(AccountsServiceProvider::class);
        $this->app->register(AccountsApiServiceProvider::class);
        $this->app->register(CollectionsServiceProvider::class);
        $this->app->register(CollectionsApiServiceProvider::class);
        $this->artisan('migrate');

        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->forceFill(['current_team_id' => $team->getKey()])->save();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/browser-game/accounts', ['name' => 'Created account'])->assertCreated()->assertJsonPath('data.attributes.team_id', (string) $team->getKey());
        $this->postJson('/api/v1/browser-game/collections', ['name' => 'First achievement', 'kind' => 'achievement'])->assertCreated()->assertJsonPath('data.attributes.team_id', (string) $team->getKey());
    }

    public function test_browser_game_character_and_quest_reads_are_team_scoped(): void
    {
        $this->app->register(CharactersServiceProvider::class);
        $this->app->register(CharactersApiServiceProvider::class);
        $this->app->register(QuestsServiceProvider::class);
        $this->app->register(QuestsApiServiceProvider::class);
        $this->artisan('migrate');

        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $otherTeam = Team::factory()->create();
        $user->forceFill(['current_team_id' => $team->getKey()])->save();
        $manager = app(CharactersManager::class);
        $character = $manager->create((string) $user->getKey(), 'Visible', 'human', 'warrior', teamId: (string) $team->getKey());
        $hiddenCharacter = $manager->create((string) $user->getKey(), 'Hidden', 'human', 'warrior', teamId: (string) $otherTeam->getKey());
        $visibleQuest = Quest::query()->create(['id' => (string) Str::uuid(), 'name' => 'Visible quest', 'slug' => 'visible-quest', 'team_id' => $team->getKey(), 'status' => 'active', 'objectives' => [], 'rewards' => []]);
        $hiddenQuest = Quest::query()->create(['id' => (string) Str::uuid(), 'name' => 'Hidden quest', 'slug' => 'hidden-quest', 'team_id' => $otherTeam->getKey(), 'status' => 'active', 'objectives' => [], 'rewards' => []]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/browser-game/characters/'.$character->getKey())->assertOk();
        $this->getJson('/api/v1/browser-game/characters/'.$hiddenCharacter->getKey())->assertNotFound();
        $this->getJson('/api/v1/browser-game/quests/'.$visibleQuest->getKey())->assertOk();
        $this->getJson('/api/v1/browser-game/quests/'.$hiddenQuest->getKey())->assertNotFound();
    }

    public function test_browser_game_commerce_products_and_checkout_are_team_scoped(): void
    {
        $this->app->register(CommerceServiceProvider::class);
        $this->app->register(CommerceApiServiceProvider::class);
        $this->artisan('migrate');

        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $otherTeam = Team::factory()->create();
        $user->forceFill(['current_team_id' => $team->getKey()])->save();
        $manager = app(CommerceManager::class);
        $visible = $manager->createProduct('VISIBLE', 'Visible product', 'GLD', 10, data: [], teamId: (string) $team->getKey());
        $hidden = $manager->createProduct('HIDDEN', 'Hidden product', 'GLD', 10, data: [], teamId: (string) $otherTeam->getKey());
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/browser-game/commerce/products')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', (string) $visible->getKey())
            ->assertJsonMissing(['id' => (string) $hidden->getKey()]);

        $this->postJson('/api/v1/browser-game/commerce/checkout', ['lines' => [['product_id' => $hidden->getKey(), 'quantity' => 1]]])
            ->assertNotFound();
    }

    public function test_browser_game_world_unlocks_are_required_for_gated_travel(): void
    {
        $this->app->register(WorldServiceProvider::class);
        $this->app->register(WorldApiServiceProvider::class);
        $this->artisan('migrate');

        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->forceFill(['current_team_id' => $team->getKey()])->save();
        $manager = app(WorldManager::class);
        $origin = $manager->define(null, (string) $team->getKey(), 'location', 'Origin', 'origin', worldId: 'world-1');
        $destination = $manager->define(null, (string) $team->getKey(), 'location', 'Locked', 'locked', worldId: 'world-1', unlockKey: 'world.locked');
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/browser-game/world/travel', ['origin_id' => $origin->getKey(), 'destination_id' => $destination->getKey(), 'metadata' => ['unlocked' => true]])
            ->assertUnprocessable();
        $unlock = $this->postJson('/api/v1/browser-game/world/'.$destination->getKey().'/unlock', ['idempotency_key' => 'api-unlock-1'])
            ->assertCreated()
            ->json('data.id');
        $this->postJson('/api/v1/browser-game/world/travel', ['origin_id' => $origin->getKey(), 'destination_id' => $destination->getKey(), 'idempotency_key' => 'api-travel-1'])
            ->assertCreated();
        $this->deleteJson('/api/v1/browser-game/world/unlocks/'.$unlock)->assertOk()->assertJsonPath('data.attributes.status', 'revoked');
    }
}
