<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\CompetitionApi\Http\Controllers\CompetitionController;

Route::prefix('api/v1/browser-game/competition')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [CompetitionController::class, 'index'])->name('browser-game.competition.index');
    Route::get('/{competition}', [CompetitionController::class, 'show'])->name('browser-game.competition.show');
});
