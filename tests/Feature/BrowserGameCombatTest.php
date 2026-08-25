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
    $manager->define('ability', 'power-strike', 'Power Strike', ['damage' => 30], ['power' => 30], 2);
    $battle = $manager->start('player-1', 'player-2', state: ['health' => ['actor' => 100, 'opponent' => 40], 'loot' => ['gold' => 10]]);

    $action = $manager->resolve($battle, 'player-1', 'power-strike', 999, 'combat-action-1');
    $battle = $battle->fresh();

    expect($action->value)->toBe(30)
        ->and($action->effects)->toBe(['damage' => 30])
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

it('provides typed combat modes and definition actions', function (): void {
    $manager = app(CombatManager::class);
    $ability = $manager->defineAbility('power-strike', 'Power Strike', data: ['power' => 30]);
    $enemy = $manager->defineEnemy('goblin', 'Goblin');
    $boss = $manager->defineBoss('dragon', 'Dragon');
    $battle = $manager->startPve('player-1', 'enemy-1', idempotencyKey: 'pve-1');
    $pvp = $manager->startPvp('player-1', 'player-2', idempotencyKey: 'pvp-1');

    expect($ability->kind)->toBe('ability')
        ->and($enemy->kind)->toBe('enemy')
        ->and($boss->kind)->toBe('boss')
        ->and($battle->state['mode'])->toBe('pve')
        ->and($pvp->state['mode'])->toBe('pvp');
});

it('rejects a combat idempotency key reused for another battle', function (): void {
    $manager = app(CombatManager::class);
    $manager->start('player-1', 'player-2', idempotencyKey: 'battle-1');

    expect(fn (): mixed => $manager->start('player-1', 'player-3', idempotencyKey: 'battle-1'))
        ->toThrow(ValidationException::class);
});

it('produces deterministic simulation output and rejects identical combatants', function (): void {
    $manager = app(CombatManager::class);
    $actions = [['action' => 'attack', 'value' => 20], ['action' => 'attack', 'value' => 90]];

    $first = $manager->simulate('player-1', 'player-2', $actions);
    $second = $manager->simulate('player-1', 'player-2', $actions);

    expect($first)->toEqual($second)->and($first['status'])->toBe('completed');
    expect(fn (): array => $manager->simulate('player-1', 'player-1', []))->toThrow(ValidationException::class);
});
