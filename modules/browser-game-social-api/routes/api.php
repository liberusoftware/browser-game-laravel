<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\BrowserGame\SocialApi\Http\Controllers\SocialController;

Route::prefix('api/v1/browser-game/social')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/', [SocialController::class, 'index'])->name('browser-game.social.index');
    Route::post('/', [SocialController::class, 'store'])->name('browser-game.social.store');
    Route::post('/friends', [SocialController::class, 'friendRequest'])->name('browser-game.social.friends.create');
    Route::post('/groups/{kind}', [SocialController::class, 'createGroup'])->whereIn('kind', ['party', 'chat', 'guild', 'alliance'])->name('browser-game.social.groups.create');
    Route::post('/mail', [SocialController::class, 'mail'])->name('browser-game.social.mail.create');
    Route::get('/activity', [SocialController::class, 'activity'])->name('browser-game.social.activity');
    Route::post('/{social}/members', [SocialController::class, 'member'])->name('browser-game.social.member');
    Route::patch('/{social}/permissions', [SocialController::class, 'permissions'])->name('browser-game.social.permissions');
    Route::patch('/{social}/friend-response', [SocialController::class, 'respondToFriendRequest'])->name('browser-game.social.friends.respond');
    Route::post('/{social}/messages', [SocialController::class, 'message'])->name('browser-game.social.message');
    Route::post('/reports', [SocialController::class, 'report'])->name('browser-game.social.report');
    Route::get('/{social}', [SocialController::class, 'show'])->name('browser-game.social.show');
});
