<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\EconomyApi\Http\Controllers\EconomyController;

Route::prefix('api/v1/browser-game/economy')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [EconomyController::class, 'index'])->name('browser-game.economy.index');
    Route::get('/wallet', [EconomyController::class, 'wallet'])->name('browser-game.economy.wallet');
    Route::post('/transfer', [EconomyController::class, 'transfer'])->name('browser-game.economy.transfer');
    Route::get('/listings', [EconomyController::class, 'listings'])->name('browser-game.economy.listings');
    Route::post('/listings', [EconomyController::class, 'createListing'])->name('browser-game.economy.listings.create');
    Route::post('/listings/{listing}/purchase', [EconomyController::class, 'purchaseListing'])->name('browser-game.economy.listings.purchase');
    Route::post('/listings/{listing}/cancel', [EconomyController::class, 'cancelListing'])->name('browser-game.economy.listings.cancel');
    Route::get('/{economy}', [EconomyController::class, 'show'])->name('browser-game.economy.show');
});
