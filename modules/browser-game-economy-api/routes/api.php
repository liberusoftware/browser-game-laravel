<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\EconomyApi\Http\Controllers\EconomyController;

Route::prefix('api/v1/browser-game/economy')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [EconomyController::class, 'index'])->name('browser-game.economy.index');
    Route::get('/{economy}', [EconomyController::class, 'show'])->name('browser-game.economy.show');
});
