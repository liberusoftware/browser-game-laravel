<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Combat\Support\CombatManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-combat/database/migrations'),
        '--realpath' => true,
    ]);
});

it('resolves combat actions from server definitions and persists terminal state', function (): void {
    $manager = app(CombatManager::class);
    $manager->define('ability', 'power-strike', 'Power Strike', [], ['power' => 30], 2);
    $battle = $manager->start('player-1', 'player-2', state: ['health' => ['actor' => 100, 'opponent' => 40], 'loot' => ['gold' => 10]]);

    $action = $manager->resolve($battle, 'player-1', 'power-strike', 999, 'combat-action-1');
    $battle = $battle->fresh();

    expect($action->value)->toBe(30)
        ->and($battle->turn)->toBe(2)
        ->and($battle->status)->toBe('active')
        ->and($battle->state['health']['opponent'])->toBe(10)
        ->and($battle->state['log'])->toHaveCount(1);

    $duplicate = $manager->resolve($battle, 'player-1', 'power-strike', 1, 'combat-action-1');
    expect($duplicate->getKey())->toBe($action->getKey())
        ->and($battle->fresh()->turn)->toBe(2);

    $manager->resolve($battle->fresh(), 'player-1', 'attack', 10, 'combat-action-2');
    $completed = $battle->fresh();
    expect($completed->status)->toBe('completed')
        ->and($completed->state['health']['opponent'])->toBe(0);
});

it('produces deterministic simulation output and rejects identical combatants', function (): void {
    $manager = app(CombatManager::class);
    $actions = [['action' => 'attack', 'value' => 20], ['action' => 'attack', 'value' => 90]];

    $first = $manager->simulate('player-1', 'player-2', $actions);
    $second = $manager->simulate('player-1', 'player-2', $actions);

    expect($first)->toEqual($second)->and($first['status'])->toBe('completed');
    expect(fn (): array => $manager->simulate('player-1', 'player-1', []))->toThrow(ValidationException::class);
});
