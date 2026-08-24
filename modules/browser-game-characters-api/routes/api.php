<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\CharactersApi\Http\Controllers\CharactersController;

Route::prefix('api/v1/browser-game/characters')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [CharactersController::class, 'index'])->name('browser-game.characters.index');
    Route::post('/', [CharactersController::class, 'store'])->name('browser-game.characters.store');
    Route::get('/{character}', [CharactersController::class, 'show'])->name('browser-game.characters.show');
    Route::post('/{character}/respec', [CharactersController::class, 'respec'])->name('browser-game.characters.respec');
});
