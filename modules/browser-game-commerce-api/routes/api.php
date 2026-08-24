<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\CommerceApi\Http\Controllers\CommerceController;

Route::prefix('api/v1/browser-game/commerce')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [CommerceController::class, 'index'])->name('browser-game.commerce.index');
    Route::get('/{commerce}', [CommerceController::class, 'show'])->name('browser-game.commerce.show');
});
