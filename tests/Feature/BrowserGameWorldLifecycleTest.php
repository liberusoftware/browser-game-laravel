<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\World\Support\WorldManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-world/database/migrations'),
        '--realpath' => true,
    ]);
});

it('keeps travel scoped, same-world, and idempotent', function (): void {
    $manager = app(WorldManager::class);
    $origin = $manager->define('tenant-1', 'team-1', 'location', 'Origin', 'origin', worldId: 'world-1');
    $destination = $manager->define('tenant-1', 'team-1', 'location', 'Destination', 'destination', worldId: 'world-1');
    $travel = $manager->travel('player-1', 'tenant-1', 'team-1', $origin, $destination, 'travel-1');
    $retry = $manager->travel('player-1', 'tenant-1', 'team-1', $origin, $destination, 'travel-1');

    expect($retry->getKey())->toBe($travel->getKey());
    expect(fn (): mixed => $manager->travel('player-1', 'tenant-1', 'team-1', $origin, $origin, 'travel-1'))
        ->toThrow(ValidationException::class);
});

it('defines every supported world catalog kind through typed actions', function (): void {
    $manager = app(WorldManager::class);
    $methods = ['defineRegion', 'defineLocation', 'defineMap', 'defineEncounter', 'defineNpc', 'defineResource', 'defineWeather', 'defineUnlock'];

    foreach ($methods as $index => $method) {
        $entity = $manager->{$method}('tenant-1', 'team-1', "Entity {$index}", "entity-{$index}", worldId: 'world-1');
        expect($entity->kind)->toBe(config('browser-game.world.kinds', ['region', 'location', 'map', 'encounter', 'npc', 'resource', 'weather', 'unlock'])[$index]);
    }
});

it('rejects cross-team and cross-world travel', function (): void {
    $manager = app(WorldManager::class);
    $origin = $manager->define('tenant-1', 'team-1', 'location', 'Origin', 'origin', worldId: 'world-1');
    $otherTeam = $manager->define('tenant-1', 'team-2', 'location', 'Other', 'other', worldId: 'world-1');
    $otherWorld = $manager->define('tenant-1', 'team-1', 'location', 'Other World', 'other-world', worldId: 'world-2');

    expect(fn (): mixed => $manager->travel('player-1', 'tenant-1', 'team-1', $origin, $otherTeam))
        ->toThrow(ValidationException::class);
    expect(fn (): mixed => $manager->travel('player-1', 'tenant-1', 'team-1', $origin, $otherWorld))
        ->toThrow(ValidationException::class);
});

it('updates entities only inside their current context', function (): void {
    $manager = app(WorldManager::class);
    $entity = $manager->define('tenant-1', 'team-1', 'location', 'Old Name', 'old-name');

    $updated = $manager->update($entity, 'tenant-1', 'team-1', 'New Name', 'new-name', 'archived');

    expect($updated->name)->toBe('New Name')->and($updated->status)->toBe('archived');
    expect(fn (): mixed => $manager->update($entity, 'tenant-1', 'team-2', 'Denied', 'denied', 'active'))
        ->toThrow(ValidationException::class);
});
