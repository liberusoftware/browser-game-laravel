<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\ModerationAndAnalytics\Events\ModerationAndAnalyticsRecorded;
use Liberu\BrowserGame\ModerationAndAnalytics\Events\ModerationAndAnalyticsResolved;
use Liberu\BrowserGame\ModerationAndAnalytics\Support\ModerationAndAnalyticsManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-moderation-and-analytics/database/migrations'),
        '--realpath' => true,
    ]);
});

it('records reports idempotently and resolves them transactionally', function (): void {
    Event::fake();
    $manager = app(ModerationAndAnalyticsManager::class);
    $record = $manager->record('report', 'Suspicious match', 'moderator-1', 'player-1', ['severity' => 'high'], teamId: 'team-1', idempotencyKey: 'report-1');
    $retry = $manager->record('report', 'Different report', 'moderator-1', 'player-1', [], teamId: 'team-1', idempotencyKey: 'report-1');
    $resolved = $manager->resolve($record, 'dismissed');

    expect($retry->getKey())->toBe($record->getKey())
        ->and($resolved->status)->toBe('dismissed')
        ->and($resolved->resolved_at)->not->toBeNull();
    Event::assertDispatched(ModerationAndAnalyticsResolved::class);
    Event::assertDispatched(ModerationAndAnalyticsRecorded::class);
});

it('exposes typed analytics records and protects idempotency ownership', function (): void {
    $manager = app(ModerationAndAnalyticsManager::class);
    $telemetry = $manager->recordTelemetry('player-1', 'quest.started', ['quest' => 'intro'], teamId: 'team-1', idempotencyKey: 'telemetry-1');
    expect($telemetry->kind)->toBe('telemetry');
    expect(fn (): mixed => $manager->recordTelemetry('player-2', 'other', teamId: 'team-1', idempotencyKey: 'telemetry-1'))
        ->toThrow(ValidationException::class);
    expect($manager->recordFunnel('player-1', 'onboarding', teamId: 'team-1')->kind)->toBe('funnel')
        ->and($manager->recordBalance('balance snapshot', teamId: 'team-1')->kind)->toBe('balance')
        ->and($manager->recordEconomy('economy snapshot', teamId: 'team-1')->kind)->toBe('economy')
        ->and($manager->recordFraud('moderator-1', 'player-1', 'suspicious', teamId: 'team-1')->kind)->toBe('fraud')
        ->and($manager->recordHealth('service health', teamId: 'team-1')->kind)->toBe('health');
});

it('rejects invalid record kinds and empty names', function (): void {
    $manager = app(ModerationAndAnalyticsManager::class);

    expect(fn (): mixed => $manager->record('unknown', 'Record'))
        ->toThrow(ValidationException::class);
    expect(fn (): mixed => $manager->record('report', '  '))
        ->toThrow(ValidationException::class);
});
