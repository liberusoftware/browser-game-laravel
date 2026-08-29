<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\BrowserGame\GameCore\GameCoreServiceProvider;
use Liberu\BrowserGame\GameCore\Models\GameClock;
use Liberu\BrowserGame\GameCore\Models\GameContentVersion;
use Liberu\BrowserGame\GameCore\Models\GameFeatureFlag;
use Liberu\BrowserGame\GameCore\Models\GameMaintenanceState;
use Liberu\BrowserGame\GameCore\Models\GameRuleset;
use Liberu\BrowserGame\GameCore\Support\ArrayGameCoreContext;
use Liberu\BrowserGame\GameCore\Support\GameCoreManager;
use Liberu\BrowserGame\GameCoreLivewire\GameCoreLivewireServiceProvider;
use Liberu\BrowserGame\GameCoreLivewire\Livewire\WorldOverview;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('exposes every Game Core control-plane mutation through Livewire', function (): void {
    $this->app->register(GameCoreServiceProvider::class);
    $this->app->register(GameCoreLivewireServiceProvider::class);
    $this->artisan('migrate');

    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->getKey()])->save();
    $context = new ArrayGameCoreContext((string) $user->getKey(), null, (string) $team->getKey());
    $world = app(GameCoreManager::class)->createWorld($context, 'World', 'world');

    Livewire::actingAs($user)->test(WorldOverview::class, ['worldId' => $world->getKey()])
        ->set('currentAt', '2026-08-29T12:00')
        ->set('clockSpeed', '2')
        ->call('updateClock')
        ->set('rulesetVersion', 2)
        ->set('rulesJson', '{"combat":{"turns":true}}')
        ->call('publishRulesetFromForm')
        ->set('contentVersion', 3)
        ->set('contentHash', 'sha256:content')
        ->set('manifestJson', '{"quests":3}')
        ->call('publishContentFromForm')
        ->set('featureKey', 'seasonal')
        ->set('featureEnabled', true)
        ->set('featureRolloutPercentage', 75)
        ->set('featureConstraintsJson', '{"team_id":"'.$team->getKey().'"}')
        ->call('updateFeatureFlagFromForm')
        ->set('maintenanceStatus', 'active')
        ->set('maintenanceMessage', 'Maintenance enabled')
        ->call('updateMaintenanceFromForm')
        ->assertSee('Maintenance state updated.');

    expect(GameClock::query()->where('world_id', $world->getKey())->value('speed'))->toBe('2.000000')
        ->and(GameRuleset::query()->where('world_id', $world->getKey())->value('version'))->toBe(2)
        ->and(GameContentVersion::query()->where('world_id', $world->getKey())->value('version'))->toBe(3)
        ->and(GameFeatureFlag::query()->where('world_id', $world->getKey())->value('rollout_percentage'))->toBe(75)
        ->and(GameMaintenanceState::query()->where('world_id', $world->getKey())->value('status'))->toBe('active');
});

it('rejects malformed Livewire JSON payloads', function (): void {
    $this->app->register(GameCoreServiceProvider::class);
    $this->app->register(GameCoreLivewireServiceProvider::class);
    $this->artisan('migrate');

    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->getKey()])->save();
    $world = app(GameCoreManager::class)->createWorld(new ArrayGameCoreContext((string) $user->getKey(), null, (string) $team->getKey()), 'World', 'world');

    Livewire::actingAs($user)->test(WorldOverview::class, ['worldId' => $world->getKey()])
        ->set('rulesJson', 'not-json')
        ->call('publishRulesetFromForm')
        ->assertHasErrors(['rulesJson']);
});
