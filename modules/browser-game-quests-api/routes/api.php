<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\QuestsApi\Http\Controllers\QuestsController;

Route::prefix('api/v1/browser-game/quests')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [QuestsController::class, 'index'])->name('browser-game.quests.index');
    Route::get('/{quest}', [QuestsController::class, 'show'])->name('browser-game.quests.show');
    Route::post('/{quest}/progress', [QuestsController::class, 'progress'])->name('browser-game.quests.progress');
});
