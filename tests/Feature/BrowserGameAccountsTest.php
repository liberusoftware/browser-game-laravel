<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Accounts\Support\AccountsManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-accounts/database/migrations'),
        '--realpath' => true,
    ]);
});

it('verifies email, tracks session activity, and rejects sessions for banned accounts', function (): void {
    $manager = app(AccountsManager::class);
    $account = $manager->define('Player One', ['email' => 'player@example.test']);
    $verified = $manager->verifyEmail($account);
    $session = $manager->createSession($verified, '127.0.0.1', 'test-agent');

    expect($verified->email_verified_at)->not->toBeNull()
        ->and($manager->resolveSession($session['token']))->not->toBeNull();

    $manager->ban($verified, 'abuse');
    expect($manager->resolveSession($session['token']))->toBeNull();
    expect(fn (): mixed => $manager->createSession($verified->fresh()))->toThrow(ValidationException::class);
});

it('anonymizes an account and revokes its sessions on completed deletion', function (): void {
    $manager = app(AccountsManager::class);
    $account = $manager->define('Player Two', ['email' => 'two@example.test', 'username' => 'player-two']);
    $session = $manager->createSession($account);
    $manager->requestDeletion($account);
    $privacy = $manager->completeDeletion($account, 'operator-1');

    expect($privacy->deletion_completed_at)->not->toBeNull()
        ->and($account->fresh()->status)->toBe('closed')
        ->and($account->fresh()->email)->toBeNull()
        ->and($manager->resolveSession($session['token']))->toBeNull();
});
