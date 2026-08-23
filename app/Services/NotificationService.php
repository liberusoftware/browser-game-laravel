<?php

namespace App\Services;

use App\Events\AchievementUnlocked;
use App\Events\GuildInvitationSent;
use App\Events\PlayerLeveledUp;
use App\Events\QuestCompleted;
use App\Models\GameNotification;
use App\Models\Guild;
use App\Models\Player;
use App\Models\Quest;

class NotificationService
{
    /**
     * Notify player about quest completion
     */
    public function notifyQuestCompleted(Player $player, Quest $quest, int $experienceGained = 0): void
    {
        event(new QuestCompleted($player, $quest, $experienceGained));
    }

    /**
     * Notify player about level up
     */
    public function notifyLevelUp(Player $player, $newLevel, $oldLevel): void
    {
        event(new PlayerLeveledUp($player, $newLevel, $oldLevel));
    }

    /**
     * Notify player about achievement unlock
     */
    public function notifyAchievementUnlocked(Player $player, string $achievementName, ?string $achievementDescription = null): void
    {
        event(new AchievementUnlocked($player, $achievementName, $achievementDescription));
    }

    /**
     * Notify player about guild invitation
     */
    public function notifyGuildInvitation(Player $player, Guild $guild, Player $inviter): void
    {
        event(new GuildInvitationSent($player, $guild, $inviter));
    }

    /**
     * Get unread notifications for a player
     */
    public function getUnreadNotifications(Player $player)
    {
        return $player->unreadNotifications()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get all notifications for a player
     */
    public function getAllNotifications(Player $player, int $limit = 50)
    {
        return $player->gameNotifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(GameNotification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a player
     */
    public function markAllAsRead(Player $player): void
    {
        $player->unreadNotifications()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Get unread notification count for a player
     */
    public function getUnreadCount(Player $player): int
    {
        return $player->unreadNotifications()->count();
    }
}
