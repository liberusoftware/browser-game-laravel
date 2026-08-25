<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Economy\Support\EconomyManager;

uses(RefreshDatabase::class);

it('records atomic faucet, sink, and transfer ledger entries', function (): void {
    $manager = app(EconomyManager::class);
    $manager->define('Gold', ['code' => 'gold', 'fee_basis_points' => 250]);
    $manager->credit('seller', 'gold', 1000, 'quest', 'quest-1');
    $manager->transfer('seller', 'buyer', 'gold', 250, 'trade-1');

    expect($manager->debit('buyer', 'gold', 100, 'vendor')->balance_after)->toBe(150)
        ->and($manager->credit('buyer', 'gold', 50, 'reward')->balance_after)->toBe(200);
});

it('settles marketplace listings and rejects invalid purchases', function (): void {
    $manager = app(EconomyManager::class);
    $manager->define('Gold', ['code' => 'gold', 'fee_basis_points' => 500]);
    $manager->credit('buyer', 'gold', 1000);
    $listing = $manager->createListing('seller', 'iron-sword', 'gold', 2, 100);
    $sold = $manager->purchaseListing('buyer', $listing);

    expect($sold->status)->toBe('sold')
        ->and(fn () => $manager->purchaseListing('buyer', $listing))->toThrow(ValidationException::class);
});
