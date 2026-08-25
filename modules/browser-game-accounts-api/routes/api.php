<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\AccountsApi\Http\Controllers\AccountsController;

Route::prefix('api/v1/browser-game/accounts')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [AccountsController::class, 'index'])->name('browser-game.accounts.index');
    Route::post('/', [AccountsController::class, 'store'])->name('browser-game.accounts.store');
    Route::patch('/{account}', [AccountsController::class, 'update'])->name('browser-game.accounts.update');
    Route::post('/{account}/verify-email', [AccountsController::class, 'verifyEmail'])->name('browser-game.accounts.verify-email');
    Route::post('/{account}/suspend', [AccountsController::class, 'suspend'])->name('browser-game.accounts.suspend');
    Route::post('/{account}/reactivate', [AccountsController::class, 'reactivate'])->name('browser-game.accounts.reactivate');
    Route::post('/{account}/ban', [AccountsController::class, 'ban'])->name('browser-game.accounts.ban');
    Route::post('/{account}/bans/{ban}/lift', [AccountsController::class, 'liftBan'])->name('browser-game.accounts.bans.lift');
    Route::post('/{account}/age-region', [AccountsController::class, 'ageRegion'])->name('browser-game.accounts.age-region');
    Route::put('/{account}/privacy', [AccountsController::class, 'privacy'])->name('browser-game.accounts.privacy');
    Route::post('/{account}/deletion-request', [AccountsController::class, 'requestDeletion'])->name('browser-game.accounts.deletion-request');
    Route::post('/{account}/deletion-complete', [AccountsController::class, 'completeDeletion'])->name('browser-game.accounts.deletion-complete');
    Route::get('/{account}/sessions', [AccountsController::class, 'sessions'])->name('browser-game.accounts.sessions');
    Route::post('/{account}/recovery', [AccountsController::class, 'issueRecovery'])->name('browser-game.accounts.recovery.issue');
    Route::post('/{account}/sessions/{session}/revoke', [AccountsController::class, 'revokeSession'])->name('browser-game.accounts.sessions.revoke');
    Route::post('/{account}/sessions/revoke-all', [AccountsController::class, 'revokeAllSessions'])->name('browser-game.accounts.sessions.revoke-all');
    Route::get('/{account}', [AccountsController::class, 'show'])->name('browser-game.accounts.show');
});

Route::post('api/v1/browser-game/accounts/recovery/consume', [AccountsController::class, 'consumeRecovery'])
    ->middleware(['api', 'throttle:api'])->name('browser-game.accounts.recovery.consume');
