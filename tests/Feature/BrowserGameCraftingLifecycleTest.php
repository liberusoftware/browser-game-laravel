<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Crafting\Models\CraftingResource;
use Liberu\BrowserGame\Crafting\Support\CraftingManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-crafting/database/migrations'),
        '--realpath' => true,
    ]);
});

it('consumes materials once for an idempotent queue and does not duplicate completion outputs', function (): void {
    $manager = app(CraftingManager::class);
    $recipe = $manager->define('Reliable Sword', [
        'materials' => ['iron' => 2],
        'outputs' => ['sword' => 1],
        'crafting_time_seconds' => 0,
        'success_rate' => 100,
    ]);
    $manager->grantResource('player-1', 'iron', 2);

    $queue = $manager->queueCraft('player-1', $recipe, 1, 90, 'queue-operation-1');
    $retry = $manager->queueCraft('player-1', $recipe, 1, 90, 'queue-operation-1');
    $completed = $manager->complete($queue);
    $again = $manager->complete($completed);

    expect($retry->getKey())->toBe($queue->getKey())
        ->and(CraftingResource::query()->where('actor_id', 'player-1')->where('resource_key', 'iron')->exists())->toBeFalse()
        ->and($completed->outputs)->toHaveCount(1)
        ->and($again->outputs)->toHaveCount(1);
});

it('refunds cancellation once and salvages a terminal queue once', function (): void {
    $manager = app(CraftingManager::class);
    $recipe = $manager->define('Recoverable Potion', [
        'materials' => ['herb' => 2],
        'outputs' => ['potion' => 1],
        'salvage' => ['herb' => 1],
        'crafting_time_seconds' => 0,
        'success_rate' => 100,
    ]);
    $manager->grantResource('player-2', 'herb', 2);
    $queue = $manager->queueCraft('player-2', $recipe);
    $manager->cancel($queue);
    $manager->cancel($queue);

    expect(CraftingResource::query()->where('actor_id', 'player-2')->where('resource_key', 'herb')->value('quantity'))->toBe(2);

    $manager->grantResource('player-2', 'herb', 2);
    $failedRecipe = $manager->define('Failed Potion', ['materials' => ['herb' => 1], 'salvage' => ['herb' => 1], 'success_rate' => 0]);
    $failed = $manager->complete($manager->queueCraft('player-2', $failedRecipe));
    $manager->salvage($failed);
    $manager->salvage($failed);

    expect(CraftingResource::query()->where('actor_id', 'player-2')->where('resource_key', 'herb')->value('quantity'))->toBe(4);
    expect(fn (): mixed => $manager->queueCraft('player-2', $recipe, 99))->toThrow(ValidationException::class);
});

it('keeps player crafting state and idempotency scoped by team', function (): void {
    $manager = app(CraftingManager::class);
    $teamOneRecipe = $manager->define('Team One Recipe', ['materials' => ['ore' => 1]], teamId: 'team-1');
    $teamTwoRecipe = $manager->define('Team Two Recipe', ['materials' => ['ore' => 1]], teamId: 'team-2');
    $manager->grantResource('player-1', 'ore', 1, teamId: 'team-1');
    $manager->grantResource('player-1', 'ore', 1, teamId: 'team-2');

    $first = $manager->queueCraft('player-1', $teamOneRecipe, idempotencyKey: 'scoped-queue', teamId: 'team-1');
    $second = $manager->queueCraft('player-1', $teamTwoRecipe, idempotencyKey: 'scoped-queue', teamId: 'team-2');

    expect($second->getKey())->not->toBe($first->getKey())
        ->and(CraftingResource::query()->where('actor_id', 'player-1')->where('team_id', 'team-1')->exists())->toBeFalse()
        ->and(CraftingResource::query()->where('actor_id', 'player-1')->where('team_id', 'team-2')->exists())->toBeFalse();
});
