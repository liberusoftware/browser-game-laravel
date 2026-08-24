<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\CompetitionApi\Http\Controllers\CompetitionController;

Route::prefix('api/v1/browser-game/competition')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [CompetitionController::class, 'index'])->name('browser-game.competition.index');
    Route::post('/', [CompetitionController::class, 'store'])->name('browser-game.competition.store');
    Route::post('/{competition}/queue', [CompetitionController::class, 'queue'])->name('browser-game.competition.queue');
    Route::post('/{competition}/matches', [CompetitionController::class, 'match'])->name('browser-game.competition.match');
    Route::post('/matches/{match}/resolve', [CompetitionController::class, 'resolve'])->name('browser-game.competition.resolve');
    Route::get('/{competition}', [CompetitionController::class, 'show'])->name('browser-game.competition.show');
});
