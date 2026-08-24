<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\CraftingApi\Http\Controllers\CraftingController;

Route::prefix('api/v1/browser-game/crafting')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [CraftingController::class, 'index'])->name('browser-game.crafting.index');
    Route::get('/{crafting}', [CraftingController::class, 'show'])->name('browser-game.crafting.show');
});
