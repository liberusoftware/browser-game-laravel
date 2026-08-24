<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\LiveOpsApi\Http\Controllers\LiveOpsController;

Route::prefix('api/v1/browser-game/live-ops')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [LiveOpsController::class, 'index'])->name('browser-game.live-ops.index');
    Route::post('/', [LiveOpsController::class, 'store'])->name('browser-game.live-ops.store');
    Route::post('/{liveOps}/publish', [LiveOpsController::class, 'publish'])->name('browser-game.live-ops.publish');
    Route::post('/{liveOps}/claim', [LiveOpsController::class, 'claim'])->name('browser-game.live-ops.claim');
    Route::post('/{liveOps}/rollback', [LiveOpsController::class, 'rollback'])->name('browser-game.live-ops.rollback');
    Route::get('/{liveOps}', [LiveOpsController::class, 'show'])->name('browser-game.live-ops.show');
});
