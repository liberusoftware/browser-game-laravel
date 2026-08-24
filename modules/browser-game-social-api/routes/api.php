<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\SocialApi\Http\Controllers\SocialController;

Route::prefix('api/v1/browser-game/social')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [SocialController::class, 'index'])->name('browser-game.social.index');
    Route::get('/{social}', [SocialController::class, 'show'])->name('browser-game.social.show');
});
