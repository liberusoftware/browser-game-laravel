<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Collections\Models\CollectionProgress;
use Liberu\BrowserGame\Collections\Support\CollectionsManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-collections/database/migrations'),
        '--realpath' => true,
    ]);
});

it('records collection progress once per idempotency key and grants completion metadata', function (): void {
    $manager = app(CollectionsManager::class);
    $collection = $manager->defineCollection('First Steps', 'achievement');
    $entry = $manager->addEntry($collection, 'first-win', 'Win once', 2, ['title' => 'Winner']);

    $first = $manager->record('player-1', $collection, $entry->entry_key, 1, 'collection-operation-1');
    $duplicate = $manager->record('player-1', $collection, $entry->entry_key, 1, 'collection-operation-1');
    $completed = $manager->record('player-1', $collection, $entry->entry_key, 1, 'collection-operation-2');

    expect($first->quantity)->toBe(1)
        ->and($duplicate->quantity)->toBe(1)
        ->and($completed->quantity)->toBe(2)
        ->and($completed->completion_count)->toBe(1)
        ->and($completed->completed_at)->not->toBeNull()
        ->and($completed->reward_claimed_at)->not->toBeNull()
        ->and(CollectionProgress::query()->count())->toBe(1);
});

it('resets repeatable collection progress for the next completion', function (): void {
    $manager = app(CollectionsManager::class);
    $collection = $manager->defineCollection('Daily Hunt', 'achievement', [], true);
    $entry = $manager->addEntry($collection, 'hunt', 'Complete hunt', 1);

    $first = $manager->record('player-2', $collection, $entry->entry_key, 1, 'repeat-1');
    $second = $manager->record('player-2', $collection, $entry->entry_key, 1, 'repeat-2');

    expect($first->completion_count)->toBe(1)
        ->and($second->completion_count)->toBe(2)
        ->and($second->completed_at)->not->toBeNull();
});

it('exposes typed collection definitions for every documented category', function (): void {
    $manager = app(CollectionsManager::class);

    expect($manager->defineAchievement('Achievement')->kind)->toBe('achievement')
        ->and($manager->defineTitle('Title')->kind)->toBe('title')
        ->and($manager->defineReputation('Reputation')->kind)->toBe('reputation')
        ->and($manager->definePet('Pet')->kind)->toBe('pet')
        ->and($manager->defineMount('Mount')->kind)->toBe('mount')
        ->and($manager->defineHousing('Housing')->kind)->toBe('housing')
        ->and($manager->defineCosmetic('Cosmetic')->kind)->toBe('cosmetic');
});

it('rejects scoped collection progress outside the caller scope', function (): void {
    $manager = app(CollectionsManager::class);
    $collection = $manager->defineAchievement('Team achievement', teamId: 'team-1');
    $entry = $manager->addEntry($collection, 'wins', 'Wins');

    expect(fn (): mixed => $manager->record('player-1', $collection, $entry->entry_key, tenantId: 'tenant-1', teamId: 'team-2'))
        ->toThrow(ValidationException::class);
});
