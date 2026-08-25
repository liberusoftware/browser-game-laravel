<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Commerce\Support\CommerceManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-commerce/database/migrations'),
        '--realpath' => true,
    ]);
});

it('checks out idempotently, completes safely, and revokes entitlements on refund', function (): void {
    $manager = app(CommerceManager::class);
    $product = $manager->createProduct('SWORD-1', 'Sword', 'GLD', 25, ['item' => 'sword'], 2, 1);
    $order = $manager->checkout('player-1', [['product_id' => $product->getKey(), 'quantity' => 1]], 'checkout-1');
    $retry = $manager->checkout('player-1', [['product_id' => $product->getKey(), 'quantity' => 1]], 'checkout-1');

    $manager->complete($order);
    $manager->complete($order->fresh());
    $refunded = $manager->refund($order->fresh());

    expect($retry->getKey())->toBe($order->getKey())
        ->and($refunded->status)->toBe('refunded')
        ->and($refunded->entitlements->first()->status)->toBe('revoked')
        ->and($product->fresh()->stock)->toBe(1);
});

it('rejects cross-currency and invalid product definitions', function (): void {
    $manager = app(CommerceManager::class);
    $first = $manager->createProduct('GLD-1', 'Gold', 'GLD', 10);
    $second = $manager->createProduct('GEM-1', 'Gem', 'GEM', 10);

    expect(fn (): mixed => $manager->checkout('player-1', [['product_id' => $first->getKey(), 'quantity' => 1], ['product_id' => $second->getKey(), 'quantity' => 1]]))
        ->toThrow(ValidationException::class);
    expect(fn (): mixed => $manager->createProduct('BAD', 'Bad', 'gold', 10))
        ->toThrow(ValidationException::class);
});

it('rejects a checkout idempotency key reused with different lines', function (): void {
    $manager = app(CommerceManager::class);
    $first = $manager->createProduct('GLD-1', 'Gold', 'GLD', 10);
    $second = $manager->createProduct('GLD-2', 'Gem', 'GLD', 20);
    $manager->checkout('player-1', [['product_id' => $first->getKey(), 'quantity' => 1]], 'checkout-conflict');

    expect(fn (): mixed => $manager->checkout('player-1', [['product_id' => $second->getKey(), 'quantity' => 1]], 'checkout-conflict'))
        ->toThrow(ValidationException::class);
});

it('keeps products and orders inside their tenant and team scope', function (): void {
    $manager = app(CommerceManager::class);
    $product = $manager->createProduct('TEAM-A', 'Team A product', 'GLD', 10, data: [], tenantId: 'tenant-a', teamId: 'team-a');

    expect(fn (): mixed => $manager->checkout('player-1', [['product_id' => $product->getKey(), 'quantity' => 1]], 'scope-a', 'tenant-b', 'team-b'))
        ->toThrow(ModelNotFoundException::class);

    $order = $manager->checkout('player-1', [['product_id' => $product->getKey(), 'quantity' => 1]], 'scope-b', 'tenant-a', 'team-a');

    expect(fn (): mixed => $manager->complete($order, 'player-1', 'tenant-b', 'team-b'))
        ->toThrow(ValidationException::class);
});
