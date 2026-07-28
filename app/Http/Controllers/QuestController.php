<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Quest;
use App\Services\QuestService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestController extends Controller
{
    protected QuestService $questService;

    public function __construct(QuestService $questService)
    {
        $this->questService = $questService;
    }

    /**
     * Get all available quests for the authenticated player.
     */
    public function available(Request $request): JsonResponse
    {
        $player = $this->getPlayer($request);
        $quests = $this->questService->getAvailableQuests($player);

        return response()->json([
            'success' => true,
            'data' => $quests,
        ]);
    }

    /**
     * Get active quests for the authenticated player.
     */
    public function active(Request $request): JsonResponse
    {
        $player = $this->getPlayer($request);
        $quests = $this->questService->getActiveQuests($player);

        return response()->json([
            'success' => true,
            'data' => $quests,
        ]);
    }

    /**
     * Get completed quests for the authenticated player.
     */
    public function completed(Request $request): JsonResponse
    {
        $player = $this->getPlayer($request);
        $quests = $this->questService->getCompletedQuests($player);

        return response()->json([
            'success' => true,
            'data' => $quests,
        ]);
    }

    /**
     * Accept a quest.
     */
    public function accept(Request $request, Quest $quest): JsonResponse
    {
        try {
            $player = $this->getPlayer($request);
            $playerQuest = $this->questService->acceptQuest($player, $quest);

            return response()->json([
                'success' => true,
                'message' => 'Quest accepted successfully',
                'data' => $playerQuest->load('quest'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Complete a quest.
     */
    public function complete(Request $request, Quest $quest): JsonResponse
    {
        try {
            $player = $this->getPlayer($request);
            $rewards = $this->questService->completeQuest($player, $quest);

            return response()->json([
                'success' => true,
                'message' => 'Quest completed successfully',
                'data' => [
                    'quest' => $quest,
                    'rewards' => $rewards,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Abandon a quest.
     */
    public function abandon(Request $request, Quest $quest): JsonResponse
    {
        try {
            $player = $this->getPlayer($request);
            $this->questService->abandonQuest($player, $quest);

            return response()->json([
                'success' => true,
                'message' => 'Quest abandoned successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get the authenticated player.
     * For now, we'll use player_id from request or default to first player.
     */
    protected function getPlayer(Request $request): Player
    {
        $player = $request->user();
        abort_unless($player instanceof Player, 403, 'Player authentication required.');

        return $player;
    }
}
