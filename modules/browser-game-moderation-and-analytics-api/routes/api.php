<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\ModerationAndAnalyticsApi\Http\Controllers\ModerationAndAnalyticsController;

Route::prefix('api/v1/browser-game/moderation-and-analytics')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [ModerationAndAnalyticsController::class, 'index'])->name('browser-game.moderation-and-analytics.index');
    Route::post('/', [ModerationAndAnalyticsController::class, 'store'])->name('browser-game.moderation-and-analytics.store');
    Route::post('/reports', [ModerationAndAnalyticsController::class, 'report'])->name('browser-game.moderation-and-analytics.reports');
    Route::post('/sanctions', [ModerationAndAnalyticsController::class, 'sanction'])->name('browser-game.moderation-and-analytics.sanctions');
    Route::post('/appeals', [ModerationAndAnalyticsController::class, 'appeal'])->name('browser-game.moderation-and-analytics.appeals');
    Route::post('/telemetry', [ModerationAndAnalyticsController::class, 'telemetry'])->name('browser-game.moderation-and-analytics.telemetry');
    Route::post('/funnels', [ModerationAndAnalyticsController::class, 'funnel'])->name('browser-game.moderation-and-analytics.funnels');
    Route::post('/balance', [ModerationAndAnalyticsController::class, 'balance'])->name('browser-game.moderation-and-analytics.balance');
    Route::post('/economy', [ModerationAndAnalyticsController::class, 'economy'])->name('browser-game.moderation-and-analytics.economy');
    Route::post('/fraud', [ModerationAndAnalyticsController::class, 'fraud'])->name('browser-game.moderation-and-analytics.fraud');
    Route::post('/health', [ModerationAndAnalyticsController::class, 'health'])->name('browser-game.moderation-and-analytics.health');
    Route::post('/{moderationAndAnalytics}/resolve', [ModerationAndAnalyticsController::class, 'resolve'])->name('browser-game.moderation-and-analytics.resolve');
    Route::get('/{moderationAndAnalytics}', [ModerationAndAnalyticsController::class, 'show'])->name('browser-game.moderation-and-analytics.show');
});
