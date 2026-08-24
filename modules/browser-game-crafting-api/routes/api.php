<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\CraftingApi\Http\Controllers\CraftingController;

Route::prefix('api/v1/browser-game/crafting')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [CraftingController::class, 'index'])->name('browser-game.crafting.index');
    Route::post('/queue', [CraftingController::class, 'queue'])->name('browser-game.crafting.queue');
    Route::get('/queue', [CraftingController::class, 'queues'])->name('browser-game.crafting.queues');
    Route::post('/queue/{queue}/complete', [CraftingController::class, 'complete'])->name('browser-game.crafting.queue.complete');
    Route::post('/queue/{queue}/cancel', [CraftingController::class, 'cancel'])->name('browser-game.crafting.queue.cancel');
    Route::post('/queue/{queue}/salvage', [CraftingController::class, 'salvage'])->name('browser-game.crafting.queue.salvage');
    Route::get('/professions', [CraftingController::class, 'professions'])->name('browser-game.crafting.professions');
    Route::post('/{crafting}/discover', [CraftingController::class, 'discover'])->name('browser-game.crafting.discover');
    Route::get('/{crafting}', [CraftingController::class, 'show'])->name('browser-game.crafting.show');
});
