<?php

namespace App\Services;

use App\Models\DailyReward;
use App\Models\Player;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyRewardService
{
    /**
     * Check if player can claim daily reward.
     */
    public function canClaimReward(Player $player): bool
    {
        $today = Carbon::today();
        $lastReward = $player->dailyRewards()->latest('reward_date')->first();

        if (! $lastReward) {
            return true;
        }

        return ! $lastReward->reward_date->isToday();
    }

    /**
     * Claim daily reward for player.
     */
    public function claimDailyReward(Player $player): ?DailyReward
    {
        if (! $this->canClaimReward($player)) {
            return null;
        }

        $today = Carbon::today();
        $lastReward = $player->dailyRewards()->latest('reward_date')->first();

        // Calculate streak
        $streak = 1;
        if ($lastReward && $lastReward->reward_date->isYesterday()) {
            $streak = $lastReward->day_streak + 1;
        }

        // Calculate rewards based on streak
        $baseGold = 100;
        $baseExp = 50;

        $goldReward = $baseGold + ($streak * 20); // +20 gold per day streak
        $expReward = $baseExp + ($streak * 10); // +10 exp per day streak

        // Bonus items on certain streak days
        $itemsRewarded = [];
        if ($streak % 7 === 0) { // Weekly bonus
            $itemsRewarded[] = [
                'item_id' => 8, // Health Potion (assuming ID)
                'quantity' => 5,
            ];
        }
        if ($streak % 30 === 0) { // Monthly bonus
            $itemsRewarded[] = [
                'item_id' => 4, // Legendary item (assuming ID)
                'quantity' => 1,
            ];
        }

        return DB::transaction(function () use ($player, $today, $streak, $goldReward, $expReward, $itemsRewarded) {
            // Award gold
            $goldResource = $player->resources()->where('resource_type', 'gold')->first();
            if ($goldResource) {
                $goldResource->increment('quantity', $goldReward);
            } else {
                $player->resources()->create([
                    'resource_type' => 'gold',
                    'quantity' => $goldReward,
                ]);
            }

            // Award experience
            $player->increment('experience', $expReward);

            // Award items
            foreach ($itemsRewarded as $itemReward) {
                $existingItem = $player->playerItems()
                    ->where('item_id', $itemReward['item_id'])
                    ->first();

                if ($existingItem) {
                    $existingItem->increment('quantity', $itemReward['quantity']);
                } else {
                    $player->playerItems()->create([
                        'item_id' => $itemReward['item_id'],
                        'quantity' => $itemReward['quantity'],
                    ]);
                }
            }

            // Create reward record
            return DailyReward::create([
                'player_id' => $player->id,
                'reward_date' => $today,
                'day_streak' => $streak,
                'gold_rewarded' => $goldReward,
                'experience_rewarded' => $expReward,
                'items_rewarded' => $itemsRewarded,
            ]);
        });
    }

    /**
     * Get current streak for player.
     */
    public function getCurrentStreak(Player $player): int
    {
        $lastReward = $player->dailyRewards()->latest('reward_date')->first();

        if (! $lastReward) {
            return 0;
        }

        if ($lastReward->reward_date->isToday() || $lastReward->reward_date->isYesterday()) {
            return $lastReward->day_streak;
        }

        return 0;
    }
}
