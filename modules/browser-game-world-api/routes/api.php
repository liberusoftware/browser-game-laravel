<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\WorldApi\Http\Controllers\WorldController;

Route::prefix('api/v1/browser-game/world')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [WorldController::class, 'index'])->name('browser-game.world.index');
    Route::post('/', [WorldController::class, 'store'])->name('browser-game.world.store');
    Route::post('/travel', [WorldController::class, 'travel'])->name('browser-game.world.travel');
    Route::post('/{entity}/unlock', [WorldController::class, 'unlock'])->name('browser-game.world.unlock');
    Route::delete('/unlocks/{unlock}', [WorldController::class, 'revokeUnlock'])->name('browser-game.world.unlock.revoke');
    Route::patch('/{entity}', [WorldController::class, 'update'])->name('browser-game.world.update');
    Route::get('/{entity}', [WorldController::class, 'show'])->name('browser-game.world.show');
});
