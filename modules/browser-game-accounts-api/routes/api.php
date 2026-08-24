<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\AccountsApi\Http\Controllers\AccountsController;

Route::prefix('api/v1/browser-game/accounts')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [AccountsController::class, 'index'])->name('browser-game.accounts.index');
    Route::get('/{account}', [AccountsController::class, 'show'])->name('browser-game.accounts.show');
});
