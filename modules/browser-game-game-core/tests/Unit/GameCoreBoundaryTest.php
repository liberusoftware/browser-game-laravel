<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\BrowserGame\GameCore\Models\GameWorld;
use Liberu\BrowserGame\GameCore\Policies\GameCorePolicy;
use Liberu\BrowserGame\GameCore\Support\ArrayGameCoreContext;
use Liberu\BrowserGame\GameCore\Support\GameCoreManager;

uses(RefreshDatabase::class);

it('allows a scoped actor to view and manage its world', function (): void {
    $context = new ArrayGameCoreContext('actor-1', 'tenant-1', 'team-1');
    $world = new GameWorld(['tenant_id' => 'tenant-1', 'team_id' => 'team-1']);

    expect(app(GameCorePolicy::class)->view($context, $world))->toBeTrue()
        ->and(app(GameCorePolicy::class)->manage($context, $world))->toBeTrue();
});

it('persists a world control plane and emits lifecycle events', function (): void {
    $context = new ArrayGameCoreContext('actor-1', 'tenant-1', 'team-1');
    $manager = app(GameCoreManager::class);
    $world = $manager->createWorld($context, 'Aurora', 'aurora');

    $manager->setClock($context, $world, '2026-08-24 12:00:00', '2');
    $manager->publishRuleset($context, $world, 1, ['combat' => ['enabled' => true]]);
    $manager->publishContentVersion($context, $world, 1, 'sha256:content', ['quests' => 3]);
    $manager->setFeatureFlag($context, $world, 'seasonal_events', true, 50);
    $manager->setMaintenance($context, $world, 'resolved');

    expect($world->fresh()->slug)->toBe('aurora')
        ->and($world->fresh()->getKey())->not->toBeNull();
});

it('denies a world from another tenant or team', function (): void {
    $context = new ArrayGameCoreContext('actor-1', 'tenant-1', 'team-1');
    $world = new GameWorld(['tenant_id' => 'tenant-2', 'team_id' => 'team-2']);

    expect(app(GameCorePolicy::class)->view($context, $world))->toBeFalse()
        ->and(app(GameCorePolicy::class)->manage($context, $world))->toBeFalse();
});
