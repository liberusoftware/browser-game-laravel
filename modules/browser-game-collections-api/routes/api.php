<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\CollectionsApi\Http\Controllers\CollectionsController;

Route::prefix('api/v1/browser-game/collections')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [CollectionsController::class, 'index'])->name('browser-game.collections.index');
    Route::post('/{collections}/progress', [CollectionsController::class, 'record'])->name('browser-game.collections.progress');
    Route::get('/{collections}', [CollectionsController::class, 'show'])->name('browser-game.collections.show');
});
