<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Economy\Models\EconomyWallet;
use Liberu\BrowserGame\Economy\Support\EconomyManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-economy/database/migrations'),
        '--realpath' => true,
    ]);
});

it('normalizes currencies, rejects negative credits, and enforces vendor limits atomically', function (): void {
    $manager = app(EconomyManager::class);
    $manager->define('Gold', ['code' => 'GOLD']);
    $manager->credit('player-1', 'GOLD', 100);
    $vendor = $manager->createVendor('General Store');
    $offer = $manager->addOffer($vendor, 'potion', 'GOLD', 10, 5, 2);

    $manager->purchaseOffer('player-1', $offer, 2);
    expect(fn (): mixed => $manager->purchaseOffer('player-1', $offer, 1))->toThrow(ValidationException::class)
        ->and(EconomyWallet::query()->where('actor_id', 'player-1')->value('currency_code'))->toBe('gold')
        ->and(EconomyWallet::query()->where('actor_id', 'player-1')->value('balance'))->toBe(80);
    expect(fn (): mixed => $manager->credit('player-1', 'gold', -1))->toThrow(ValidationException::class);
});

it('rejects idempotency keys reused by another economy operation', function (): void {
    $manager = app(EconomyManager::class);
    $manager->define('Gold', ['code' => 'GOLD']);
    $manager->credit('player-1', 'gold', 10, idempotencyKey: 'ledger-1');

    expect(fn (): mixed => $manager->credit('player-2', 'gold', 10, idempotencyKey: 'ledger-1'))
        ->toThrow(ValidationException::class);
});

it('keeps scoped currencies and actor balances isolated', function (): void {
    $manager = app(EconomyManager::class);
    $teamOne = $manager->define('Team One Gold', ['code' => 'GOLD'], tenantId: 'tenant-1', teamId: 'team-1');
    $teamTwo = $manager->define('Team Two Gold', ['code' => 'GOLD'], tenantId: 'tenant-1', teamId: 'team-2');

    $manager->credit('player-1', 'gold', 10, tenantId: 'tenant-1', teamId: 'team-1');
    $manager->credit('player-1', 'gold', 30, tenantId: 'tenant-1', teamId: 'team-2');

    expect($teamOne->getKey())->not->toBe($teamTwo->getKey())
        ->and(EconomyWallet::query()->where('actor_id', 'player-1')->where('team_id', 'team-1')->value('balance'))->toBe(10)
        ->and(EconomyWallet::query()->where('actor_id', 'player-1')->where('team_id', 'team-2')->value('balance'))->toBe(30);
});

it('does not replay ledger idempotency keys across scopes', function (): void {
    $manager = app(EconomyManager::class);
    $manager->define('Team One Gold', ['code' => 'GOLD'], tenantId: 'tenant-1', teamId: 'team-1');
    $manager->define('Team Two Gold', ['code' => 'GOLD'], tenantId: 'tenant-2', teamId: 'team-2');
    $manager->credit('player-1', 'gold', 10, idempotencyKey: 'scoped-ledger', tenantId: 'tenant-1', teamId: 'team-1');

    expect(fn (): mixed => $manager->credit('player-1', 'gold', 10, idempotencyKey: 'scoped-ledger', tenantId: 'tenant-2', teamId: 'team-2'))
        ->toThrow(ValidationException::class);
});
