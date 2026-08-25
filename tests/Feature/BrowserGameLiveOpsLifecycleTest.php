<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\LiveOps\Events\LiveOpsClaimed;
use Liberu\BrowserGame\LiveOps\Support\LiveOpsManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-live-ops/database/migrations'),
        '--realpath' => true,
    ]);
});

it('creates idempotent published activities and claims each actor once', function (): void {
    Event::fake();
    $manager = app(LiveOpsManager::class);
    $record = $manager->create('Founders Grant', 'grant', ['grant' => ['gold' => 10]], teamId: 'team-1', idempotencyKey: 'grant-1');
    $retry = $manager->create('Different Name', 'grant', [], teamId: 'team-1', idempotencyKey: 'grant-1');
    $manager->publish($record);

    $claim = $manager->claim('player-1', $record);
    $retryClaim = $manager->claim('player-1', $record->fresh());

    expect($retry->getKey())->toBe($record->getKey())
        ->and($retryClaim->getKey())->toBe($claim->getKey())
        ->and($claim->grant)->toBe(['gold' => 10]);

    Event::assertDispatched(LiveOpsClaimed::class, 1);
});

it('rejects invalid publication windows and empty rollback actors', function (): void {
    $manager = app(LiveOpsManager::class);
    $record = $manager->create('Scheduled Event', 'event', ['starts_at' => now()->addHour(), 'ends_at' => now()->addMinute()]);

    expect(fn (): mixed => $manager->publish($record))
        ->toThrow(ValidationException::class);
    $record->update(['starts_at' => now()->subMinute(), 'ends_at' => now()->addMinute()]);
    $manager->publish($record->fresh());

    expect(fn (): mixed => $manager->rollback($record, '', 'bad actor'))
        ->toThrow(ValidationException::class);
});

it('does not replay a Live Ops idempotency key across scopes', function (): void {
    $manager = app(LiveOpsManager::class);
    $manager->create('Team One Event', 'event', teamId: 'team-1', idempotencyKey: 'scoped-live-ops-1');

    expect(fn (): mixed => $manager->create('Team Two Event', 'event', teamId: 'team-2', idempotencyKey: 'scoped-live-ops-1'))
        ->toThrow(ValidationException::class);
});

it('provides typed live operations for daily activities, events, seasons, schedules, announcements, and grants', function (): void {
    $manager = app(LiveOpsManager::class);

    expect($manager->createDailyActivity('Daily')->kind)->toBe('daily_activity')
        ->and($manager->createEvent('Event')->kind)->toBe('event')
        ->and($manager->createSeason('Season')->kind)->toBe('season')
        ->and($manager->createSchedule('Schedule')->kind)->toBe('schedule')
        ->and($manager->createAnnouncement('Announcement')->kind)->toBe('announcement')
        ->and($manager->createGrant('Grant')->kind)->toBe('grant');
});
