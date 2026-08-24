<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Accounts\Models\AccountsRecord;
use Liberu\BrowserGame\Accounts\Support\AccountsManager;

uses(RefreshDatabase::class);

it('owns identity, policy, session, recovery, ban, and privacy state', function (): void {
    $manager = app(AccountsManager::class);
    $account = $manager->define('Ada', [
        'email' => 'ada@example.test',
        'username' => 'ada-lovelace',
        'timezone' => 'UTC',
    ], 'tenant-a', 'team-a');

    $manager->setAgeRegionPolicy($account, 1990, 'GB', true);
    $session = $manager->createSession($account, '127.0.0.1', 'test-agent');
    $recovery = $manager->issueRecovery($account);
    $privacy = $manager->updatePrivacy($account, 'friends', true, false);

    expect($account->fresh())
        ->email->toBe('ada@example.test')
        ->username->toBe('ada-lovelace')
        ->region->toBe('GB')
        ->age_verified->toBeTrue()
        ->and($session['token'])->toHaveLength(80)
        ->and($manager->consumeRecovery($recovery['token'])?->is($account))->toBeTrue()
        ->and($privacy->profile_visibility)->toBe('friends');
});

it('prevents banned accounts from creating sessions and supports recovery', function (): void {
    $manager = app(AccountsManager::class);
    $account = $manager->define('Grace', ['email' => 'grace@example.test']);
    $manager->ban($account, 'policy violation');

    expect(fn () => $manager->createSession($account))->toThrow(ValidationException::class)
        ->and(AccountsRecord::query()->find($account->getKey())->status)->toBe('suspended');
});
