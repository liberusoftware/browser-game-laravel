<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\GameCoreApi\Http\Controllers\GameCoreController;

Route::prefix('api/v1/browser-game/game-core')
    ->middleware(['api', 'auth:sanctum', 'throttle:api'])
    ->group(function (): void {
        Route::get('/', [GameCoreController::class, 'index'])->name('browser-game.game-core.index');
        Route::post('/', [GameCoreController::class, 'store'])->name('browser-game.game-core.store');
        Route::get('/{world}', [GameCoreController::class, 'show'])->name('browser-game.game-core.show');
        Route::patch('/{world}', [GameCoreController::class, 'update'])->name('browser-game.game-core.update');
        Route::put('/{world}/clock', [GameCoreController::class, 'clock'])->name('browser-game.game-core.clock');
        Route::post('/{world}/rulesets', [GameCoreController::class, 'ruleset'])->name('browser-game.game-core.ruleset');
        Route::post('/{world}/content-versions', [GameCoreController::class, 'content'])->name('browser-game.game-core.content');
        Route::put('/{world}/feature-flags/{key}', [GameCoreController::class, 'flag'])->name('browser-game.game-core.flag');
        Route::get('/{world}/feature-flags/{key}', [GameCoreController::class, 'evaluateFlag'])->name('browser-game.game-core.flag.evaluate');
        Route::put('/{world}/maintenance', [GameCoreController::class, 'maintenance'])->name('browser-game.game-core.maintenance');
    });
