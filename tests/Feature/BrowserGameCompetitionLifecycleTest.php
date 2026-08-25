<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Competition\Events\CompetitionMatchResolved;
use Liberu\BrowserGame\Competition\Events\CompetitionRewardGranted;
use Liberu\BrowserGame\Competition\Support\CompetitionManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-competition/database/migrations'),
        '--realpath' => true,
    ]);
});

it('resolves a match once, grants configured rewards, and ranks players', function (): void {
    Event::fake();
    $manager = app(CompetitionManager::class);
    $competition = $manager->create('Arena', data: ['rewards' => ['winner' => ['gold' => 3]]]);
    $match = $manager->match($competition, 'player-a', 'player-b', 'match-1');

    $resolved = $manager->resolve($match, 'player-a');
    $retry = $manager->resolve($resolved, 'player-a');

    expect($retry->winner_id)->toBe('player-a')
        ->and($competition->entries()->where('actor_id', 'player-a')->value('wins'))->toBe(1)
        ->and($competition->entries()->where('actor_id', 'player-a')->value('points'))->toBe(3)
        ->and($competition->rewards()->where('actor_id', 'player-a')->value('quantity'))->toBe(3);

    Event::assertDispatched(CompetitionMatchResolved::class);
    Event::assertDispatched(CompetitionRewardGranted::class);
});

it('blocks rematches and rejects flags for another competition', function (): void {
    $manager = app(CompetitionManager::class);
    $competition = $manager->create('Arena');
    $otherCompetition = $manager->create('Other Arena');
    $match = $manager->match($competition, 'player-a', 'player-b');

    expect(fn (): mixed => $manager->match($competition, 'player-b', 'player-a'))
        ->toThrow(ValidationException::class);

    expect(fn (): mixed => $manager->flagCollusion($otherCompetition, 'player-a', 'suspicious', $match))
        ->toThrow(ValidationException::class);
});

it('rejects entries outside the competition window', function (): void {
    $manager = app(CompetitionManager::class);
    $competition = $manager->create('Scheduled Arena');
    $competition->update(['starts_at' => now()->addMinute()]);

    expect(fn (): mixed => $manager->queue($competition->fresh(), 'player-a'))
        ->toThrow(ValidationException::class);
});

it('provides typed competition factories for pvp, matchmaking, seasons, and leaderboards', function (): void {
    $manager = app(CompetitionManager::class);

    expect($manager->createPvp('PvP')->kind)->toBe('pvp')
        ->and($manager->createMatchmaking('Matchmaking')->kind)->toBe('matchmaking')
        ->and($manager->createSeason('Season')->kind)->toBe('season')
        ->and($manager->createLeaderboard('Leaderboard')->kind)->toBe('leaderboard');
});

it('rejects competition and match idempotency keys reused for different inputs', function (): void {
    $manager = app(CompetitionManager::class);
    $competition = $manager->createPvp('Arena', idempotencyKey: 'competition-1');

    expect(fn (): mixed => $manager->createPvp('Different Arena', idempotencyKey: 'competition-1'))
        ->toThrow(ValidationException::class);

    $manager->match($competition, 'player-a', 'player-b', 'match-1');

    expect(fn (): mixed => $manager->match($competition, 'player-a', 'player-c', 'match-1'))
        ->toThrow(ValidationException::class);
});

it('scopes match idempotency keys to their competition', function (): void {
    $manager = app(CompetitionManager::class);
    $firstCompetition = $manager->createPvp('Arena One');
    $secondCompetition = $manager->createPvp('Arena Two');

    $first = $manager->match($firstCompetition, 'player-a', 'player-b', 'shared-match-key');
    $second = $manager->match($secondCompetition, 'player-a', 'player-b', 'shared-match-key');

    expect($second->getKey())->not->toBe($first->getKey())
        ->and($second->competition_id)->toBe($secondCompetition->getKey());
});
