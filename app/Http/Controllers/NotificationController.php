<?php

namespace App\Http\Controllers;

use App\Models\GameNotification;
use App\Models\Player;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for the authenticated player
     */
    public function index(Request $request): JsonResponse
    {
        // Get the authenticated player (either from user->player or Player model directly)
        $player = $request->user();

        // If authenticated as User, try to get the player relationship
        if ($player instanceof User) {
            $player = $player->player ?? null;
        }

        if (! $player || ! ($player instanceof Player)) {
            return response()->json(['error' => 'Player not found'], 404);
        }

        $limit = $request->input('limit', 50);
        $notifications = $this->notificationService->getAllNotifications($player, $limit);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount($player),
        ]);
    }

    /**
     * Get unread notifications for the authenticated player
     */
    public function unread(Request $request): JsonResponse
    {
        $player = $request->user();

        if ($player instanceof User) {
            $player = $player->player ?? null;
        }

        if (! $player || ! ($player instanceof Player)) {
            return response()->json(['error' => 'Player not found'], 404);
        }

        $notifications = $this->notificationService->getUnreadNotifications($player);

        return response()->json([
            'notifications' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $player = $request->user();

        if ($player instanceof User) {
            $player = $player->player ?? null;
        }

        if (! $player || ! ($player instanceof Player)) {
            return response()->json(['error' => 'Player not found'], 404);
        }

        $notification = GameNotification::where('id', $id)
            ->where('player_id', $player->id)
            ->first();

        if (! $notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification->fresh(),
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $player = $request->user();

        if ($player instanceof User) {
            $player = $player->player ?? null;
        }

        if (! $player || ! ($player instanceof Player)) {
            return response()->json(['error' => 'Player not found'], 404);
        }

        $this->notificationService->markAllAsRead($player);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Get unread notification count
     */
    public function count(Request $request): JsonResponse
    {
        $player = $request->user();

        if ($player instanceof User) {
            $player = $player->player ?? null;
        }

        if (! $player || ! ($player instanceof Player)) {
            return response()->json(['error' => 'Player not found'], 404);
        }

        $count = $this->notificationService->getUnreadCount($player);

        return response()->json([
            'unread_count' => $count,
        ]);
    }
}
