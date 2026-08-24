<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\ItemsApi\Http\Controllers\ItemsController;

Route::prefix('api/v1/browser-game/items')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [ItemsController::class, 'index'])->name('browser-game.items.index');
    Route::get('/{items}', [ItemsController::class, 'show'])->name('browser-game.items.show');
});
