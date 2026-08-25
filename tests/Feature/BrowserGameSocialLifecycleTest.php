<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Social\Support\SocialManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-social/database/migrations'),
        '--realpath' => true,
    ]);
});

it('creates social records idempotently and enforces membership management', function (): void {
    $manager = app(SocialManager::class);
    $record = $manager->create('Guild', 'guild', 'owner-1', teamId: 'team-1', idempotencyKey: 'social-1');
    $retry = $manager->create('Different', 'guild', 'owner-1', teamId: 'team-1', idempotencyKey: 'social-1');
    $member = $manager->addMember($record, 'player-1', requestedBy: 'owner-1');

    expect($retry->getKey())->toBe($record->getKey())
        ->and($member->actor_id)->toBe('player-1');
    expect(fn (): mixed => $manager->addMember($record, 'player-2', requestedBy: 'player-1'))
        ->toThrow(ValidationException::class);
});

it('requires conversation membership before sending chat messages', function (): void {
    $manager = app(SocialManager::class);
    $record = $manager->create('Chat', 'chat', 'owner-1', teamId: 'team-1');

    expect(fn (): mixed => $manager->send('player-1', $record, 'hello'))
        ->toThrow(ValidationException::class);
    $manager->addMember($record, 'player-1', requestedBy: 'owner-1');
    expect($manager->send('player-1', $record->fresh(), 'hello')->body)->toBe('hello');
});

it('supports explicit friends, social groups, mail, permissions, and activity workflows', function (): void {
    $manager = app(SocialManager::class);

    $friend = $manager->createFriendRequest('owner-1', 'player-1', teamId: 'team-1', idempotencyKey: 'friend-1');
    expect($friend->target_id)->toBe('player-1');
    expect($manager->respondToFriendRequest($friend, 'player-1', 'accepted')->status)->toBe('accepted');

    $guild = $manager->createGuild('owner-1', 'Guild', teamId: 'team-1');
    $manager->addMember($guild, 'player-1', requestedBy: 'owner-1');
    expect($manager->updatePermissions($guild->fresh(), 'owner-1', 'player-1', ['manage' => true])->permissions)->toBe(['manage' => true]);

    $mail = $manager->createMail('owner-1', 'player-1', 'Welcome', teamId: 'team-1');
    expect($mail->target_id)->toBe('player-1');
    expect($manager->recordActivity('owner-1', 'guild.viewed', $guild->getKey())->kind)->toBe('guild.viewed');

    $report = $manager->report('owner-1', 'player-2', 'spam', teamId: 'team-1', idempotencyKey: 'report-1');
    expect($manager->report('owner-1', 'different', 'changed', teamId: 'team-1', idempotencyKey: 'report-1')->getKey())->toBe($report->getKey())
        ->and($report->team_id)->toBe('team-1');
});
