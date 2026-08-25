<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Crafting\Support\CraftingManager;

uses(RefreshDatabase::class);

it('consumes materials, queues work, and records quality outputs', function (): void {
    $manager = app(CraftingManager::class);
    $recipe = $manager->define('Iron Sword', [
        'materials' => ['iron' => 2],
        'outputs' => ['iron-sword' => 1],
        'salvage' => ['iron' => 1],
        'crafting_time_seconds' => 0,
        'success_rate' => 100,
    ]);
    $manager->grantResource('player-1', 'iron', 2);
    $queue = $manager->queueCraft('player-1', $recipe, 1, 85, 'craft-1');
    $completed = $manager->complete($queue);

    expect($completed->status)->toBe('completed')
        ->and($completed->outputs)->toHaveCount(1)
        ->and($completed->outputs->first()->quality)->toBe(85);
});

it('rejects insufficient materials and refunds cancelled work', function (): void {
    $manager = app(CraftingManager::class);
    $recipe = $manager->define('Potion', ['materials' => ['herb' => 3], 'outputs' => ['potion' => 1]]);

    expect(fn () => $manager->queueCraft('player-2', $recipe))->toThrow(ValidationException::class);
    $manager->grantResource('player-2', 'herb', 3);
    $queue = $manager->queueCraft('player-2', $recipe);
    $manager->cancel($queue);

    expect($manager->queueCraft('player-2', $recipe)->status)->toBe('queued');
});
