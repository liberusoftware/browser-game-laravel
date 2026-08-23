<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\Request;

class PlayerStatisticsController extends Controller
{
    /**
     * Get statistics for a specific player
     */
    public function show(Request $request, Player $player)
    {
        abort_unless($player->is($request->user()), 403);
        $statistics = $player->statistics;

        if (! $statistics) {
            $statistics = $player->statistics()->create([
                'highest_level_achieved' => $player->level,
                'total_experience_earned' => $player->experience,
            ]);
        }

        return response()->json([
            'data' => $statistics,
        ]);
    }

    /**
     * Get player progression summary
     */
    public function progression(Request $request, Player $player)
    {
        abort_unless($player->is($request->user()), 403);
        $questsCompleted = $player->quests()->where('status', 'completed')->count();
        $questsInProgress = $player->quests()->where('status', 'in-progress')->count();
        $totalItems = $player->items()->sum('quantity');
        $achievementsUnlocked = $player->achievements()->whereNotNull('player_achievements.unlocked_at')->count();

        return response()->json([
            'data' => [
                'level' => $player->level,
                'experience' => $player->experience,
                'quests_completed' => $questsCompleted,
                'quests_in_progress' => $questsInProgress,
                'total_items' => $totalItems,
                'achievements_unlocked' => $achievementsUnlocked,
            ],
        ]);
    }
}
