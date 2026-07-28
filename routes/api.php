<?php

use App\Http\Controllers\Api\CombatController;
use App\Http\Controllers\Api\CraftingController;
use App\Http\Controllers\Api\DailyRewardController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\PlayerAchievementsController;
use App\Http\Controllers\Api\PlayerStatisticsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QuestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Notification routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('api.notifications.index');
        Route::get('/unread', [NotificationController::class, 'unread'])->name('api.notifications.unread');
        Route::get('/count', [NotificationController::class, 'count'])->name('api.notifications.count');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.mark-as-read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.mark-all-as-read');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    // Player statistics routes
    Route::prefix('players/{player}')->scopeBindings()->group(function () {
        Route::get('/statistics', [PlayerStatisticsController::class, 'show']);
        Route::get('/progression', [PlayerStatisticsController::class, 'progression']);
        Route::get('/achievements', [PlayerAchievementsController::class, 'index']);
        Route::get('/achievements/unlocked', [PlayerAchievementsController::class, 'unlocked']);
        Route::get('/daily-reward/status', [DailyRewardController::class, 'status']);
        Route::post('/daily-reward/claim', [DailyRewardController::class, 'claim']);
        Route::get('/recipes', [CraftingController::class, 'playerRecipes']);
    });

    // Achievement routes
    Route::get('/achievements', [PlayerAchievementsController::class, 'available']);

    // Quest API routes
    Route::prefix('quests')->group(function () {
        Route::get('/available', [QuestController::class, 'available']);
        Route::get('/active', [QuestController::class, 'active']);
        Route::get('/completed', [QuestController::class, 'completed']);
        Route::post('/{quest}/accept', [QuestController::class, 'accept']);
        Route::post('/{quest}/complete', [QuestController::class, 'complete']);
        Route::delete('/{quest}/abandon', [QuestController::class, 'abandon']);
    });

    // Combat routes
    Route::prefix('combat')->group(function () {
        Route::post('/pve', [CombatController::class, 'pve']);
        Route::post('/pvp', [CombatController::class, 'pvp']);
        Route::post('/heal', [CombatController::class, 'heal']);
    });

    // Crafting routes
    Route::prefix('crafting')->group(function () {
        Route::post('/recipes/{recipe}/craft', [CraftingController::class, 'craft']);
        Route::post('/recipes/{recipe}/learn', [CraftingController::class, 'learn']);
    });

    // Marketplace routes
    Route::prefix('marketplace')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index']);
        Route::post('/', [MarketplaceController::class, 'store']);
        Route::post('/{listing}/purchase', [MarketplaceController::class, 'purchase']);
        Route::delete('/{listing}/cancel', [MarketplaceController::class, 'cancel']);
    });
});
