<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\CommerceApi\Http\Controllers\CommerceController;

Route::prefix('api/v1/browser-game/commerce')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [CommerceController::class, 'index'])->name('browser-game.commerce.index');
    Route::get('/products', [CommerceController::class, 'products'])->name('browser-game.commerce.products');
    Route::post('/checkout', [CommerceController::class, 'checkout'])->name('browser-game.commerce.checkout');
    Route::post('/orders/{order}/complete', [CommerceController::class, 'complete'])->name('browser-game.commerce.orders.complete');
    Route::post('/orders/{order}/refund', [CommerceController::class, 'refund'])->name('browser-game.commerce.orders.refund');
    Route::get('/{commerce}', [CommerceController::class, 'show'])->name('browser-game.commerce.show');
});
