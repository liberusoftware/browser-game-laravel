<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\CombatApi\Http\Controllers\CombatController;

Route::prefix('api/v1/browser-game/combat')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [CombatController::class, 'index'])->name('browser-game.combat.index');
    Route::get('/{battle}', [CombatController::class, 'show'])->name('browser-game.combat.show');
    Route::post('/', [CombatController::class, 'store'])->name('browser-game.combat.store');
    Route::post('/{battle}/actions', [CombatController::class, 'action'])->name('browser-game.combat.action');
});
