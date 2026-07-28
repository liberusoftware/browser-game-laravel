<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Services\DailyRewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyRewardController extends Controller
{
    protected DailyRewardService $dailyRewardService;

    public function __construct(DailyRewardService $dailyRewardService)
    {
        $this->dailyRewardService = $dailyRewardService;
    }

    /**
     * Check if a player can claim their daily reward.
     */
    public function status(Request $request, Player $player): JsonResponse
    {
        abort_unless($player->is($request->user()), 403);
        $canClaim = $this->dailyRewardService->canClaimReward($player);
        $streak = $this->dailyRewardService->getCurrentStreak($player);

        return response()->json([
            'success' => true,
            'data' => [
                'can_claim' => $canClaim,
                'current_streak' => $streak,
            ],
        ]);
    }

    /**
     * Claim the daily reward for a player.
     */
    public function claim(Request $request, Player $player): JsonResponse
    {
        abort_unless($player->is($request->user()), 403);
        $reward = $this->dailyRewardService->claimDailyReward($player);

        if (! $reward) {
            return response()->json([
                'success' => false,
                'message' => 'Daily reward already claimed today.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daily reward claimed successfully!',
            'data' => $reward,
        ]);
    }
}
