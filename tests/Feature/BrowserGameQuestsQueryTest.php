<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\BrowserGame\Quests\Queries\QuestQuery;
use Liberu\BrowserGame\Quests\Support\QuestsManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-quests/database/migrations'),
        '--realpath' => true,
    ]);
});

it('exposes available, active, and completed quest views for an actor', function (): void {
    $manager = app(QuestsManager::class);
    $query = app(QuestQuery::class);
    $actorId = 'player-1';

    $available = $manager->define('Available', 'available', ['kill' => 1]);
    $active = $manager->define('Active', 'active', ['kill' => 1]);
    $completed = $manager->define('Completed', 'completed', ['kill' => 1]);
    $repeatable = $manager->define('Repeatable', 'repeatable', ['kill' => 1], [], true);

    $manager->accept($active, $actorId);
    $manager->accept($completed, $actorId);
    $manager->progress($completed, $actorId, ['kill' => 1], 'completed');
    $manager->accept($repeatable, $actorId);
    $manager->progress($repeatable, $actorId, ['kill' => 1], 'completed');

    expect($query->availableFor($actorId, null, null)->pluck('slug')->all())
        ->toEqualCanonicalizing([$available->slug, $repeatable->slug])
        ->and($query->activeFor($actorId, null, null)->pluck('slug')->all())
        ->toBe([$active->slug])
        ->and($query->completedFor($actorId, null, null)->pluck('slug')->all())
        ->toEqualCanonicalizing([$completed->slug, $repeatable->slug]);
});
