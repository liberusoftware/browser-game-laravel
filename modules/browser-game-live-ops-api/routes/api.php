<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\LiveOpsApi\Http\Controllers\LiveOpsController;

Route::prefix('api/v1/browser-game/live-ops')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [LiveOpsController::class, 'index'])->name('browser-game.live-ops.index');
    Route::get('/{liveOps}', [LiveOpsController::class, 'show'])->name('browser-game.live-ops.show');
});
