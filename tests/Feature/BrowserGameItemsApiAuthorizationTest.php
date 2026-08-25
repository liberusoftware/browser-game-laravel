<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\BrowserGame\Items\ItemsServiceProvider;
use Liberu\BrowserGame\Items\Support\ItemsManager;
use Liberu\BrowserGame\ItemsApi\ItemsApiServiceProvider;
use Tests\TestCase;

class BrowserGameItemsApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_mutations_reject_items_from_another_team(): void
    {
        $this->app->register(ItemsServiceProvider::class);
        $this->app->register(ItemsApiServiceProvider::class);
        $this->artisan('migrate');

        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $otherTeam = Team::factory()->create();
        $user->forceFill(['current_team_id' => $team->getKey()])->save();

        $manager = app(ItemsManager::class);
        $visible = $manager->define('Visible item', teamId: (string) $team->getKey());
        $hidden = $manager->define('Hidden item', teamId: (string) $otherTeam->getKey());
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/browser-game/items/inventory/'.$visible->getKey().'/add', ['quantity' => 1])
            ->assertCreated();
        $this->postJson('/api/v1/browser-game/items/inventory/'.$hidden->getKey().'/add', ['quantity' => 1])
            ->assertNotFound();

        $this->getJson('/api/v1/browser-game/items/inventory/me')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
