<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\ModerationAndAnalyticsApi\Http\Controllers\ModerationAndAnalyticsController;

Route::prefix('api/v1/browser-game/moderation-and-analytics')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [ModerationAndAnalyticsController::class, 'index'])->name('browser-game.moderation-and-analytics.index');
    Route::post('/', [ModerationAndAnalyticsController::class, 'store'])->name('browser-game.moderation-and-analytics.store');
    Route::post('/{moderationAndAnalytics}/resolve', [ModerationAndAnalyticsController::class, 'resolve'])->name('browser-game.moderation-and-analytics.resolve');
    Route::get('/{moderationAndAnalytics}', [ModerationAndAnalyticsController::class, 'show'])->name('browser-game.moderation-and-analytics.show');
});
