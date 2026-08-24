<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\ItemsApi\Http\Controllers\ItemsController;

Route::prefix('api/v1/browser-game/items')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [ItemsController::class, 'index'])->name('browser-game.items.index');
    Route::get('/inventory/me', [ItemsController::class, 'inventory'])->name('browser-game.items.inventory');
    Route::post('/inventory/{item}/add', [ItemsController::class, 'addToInventory'])->name('browser-game.items.inventory.add');
    Route::post('/inventory/{item}/remove', [ItemsController::class, 'removeFromInventory'])->name('browser-game.items.inventory.remove');
    Route::post('/inventory/entries/{entry}/equip', [ItemsController::class, 'equip'])->name('browser-game.items.inventory.equip');
    Route::post('/inventory/entries/{entry}/unequip', [ItemsController::class, 'unequip'])->name('browser-game.items.inventory.unequip');
    Route::post('/inventory/entries/{entry}/bind', [ItemsController::class, 'bind'])->name('browser-game.items.inventory.bind');
    Route::post('/inventory/entries/{entry}/durability', [ItemsController::class, 'durability'])->name('browser-game.items.inventory.durability');
    Route::post('/inventory/entries/{entry}/container', [ItemsController::class, 'container'])->name('browser-game.items.inventory.container');
    Route::post('/inventory/entries/{entry}/provenance', [ItemsController::class, 'provenance'])->name('browser-game.items.inventory.provenance');
    Route::get('/{items}', [ItemsController::class, 'show'])->name('browser-game.items.show');
});
