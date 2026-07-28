<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Player;
use Illuminate\Http\Request;

class PlayerAchievementsController extends Controller
{
    /**
     * Get all achievements for a player
     */
    public function index(Request $request, Player $player)
    {
        abort_unless($player->is($request->user()), 403);
        $achievements = $player->achievements()
            ->withPivot('unlocked_at', 'progress')
            ->get();

        return response()->json([
            'data' => $achievements,
        ]);
    }

    /**
     * Get all available achievements
     */
    public function available()
    {
        $achievements = Achievement::all();

        return response()->json([
            'data' => $achievements,
        ]);
    }

    /**
     * Get player's unlocked achievements
     */
    public function unlocked(Request $request, Player $player)
    {
        abort_unless($player->is($request->user()), 403);
        $achievements = $player->achievements()
            ->whereNotNull('player_achievements.unlocked_at')
            ->withPivot('unlocked_at', 'progress')
            ->get();

        return response()->json([
            'data' => $achievements,
        ]);
    }
}
