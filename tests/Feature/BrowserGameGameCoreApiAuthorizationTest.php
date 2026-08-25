<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\BrowserGame\GameCore\GameCoreServiceProvider;
use Liberu\BrowserGame\GameCore\Support\ArrayGameCoreContext;
use Liberu\BrowserGame\GameCore\Support\GameCoreManager;
use Liberu\BrowserGame\GameCoreApi\GameCoreApiServiceProvider;
use Tests\TestCase;

class BrowserGameGameCoreApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_flag_evaluation_is_scoped_and_actor_aware(): void
    {
        $this->app->register(GameCoreServiceProvider::class);
        $this->app->register(GameCoreApiServiceProvider::class);
        $this->artisan('migrate');

        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $otherTeam = Team::factory()->create();
        $user->forceFill(['current_team_id' => $team->getKey()])->save();
        $manager = app(GameCoreManager::class);
        $world = $manager->createWorld(new ArrayGameCoreContext((string) $user->getKey(), null, (string) $team->getKey()), 'World', 'world');
        $manager->setFeatureFlag(new ArrayGameCoreContext((string) $user->getKey(), null, (string) $team->getKey()), $world, 'seasonal', true, 100, ['team_id' => (string) $team->getKey()]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/browser-game/game-core/'.$world->getKey().'/feature-flags/seasonal')
            ->assertOk()
            ->assertJsonPath('data.attributes.enabled', true);

        $user->forceFill(['current_team_id' => $otherTeam->getKey()])->save();
        Sanctum::actingAs($user->fresh());
        $this->getJson('/api/v1/browser-game/game-core/'.$world->getKey().'/feature-flags/seasonal')->assertNotFound();
    }
}
