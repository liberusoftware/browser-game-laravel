<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Player_Quest;
use App\Models\Quest;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class QuestService
{
    /**
     * Accept a quest for a player.
     *
     * @throws Exception
     */
    public function acceptQuest(Player $player, Quest $quest): Player_Quest
    {
        // Check if player already has this quest
        $existingQuest = Player_Quest::where('player_id', $player->id)
            ->where('quest_id', $quest->id)
            ->first();

        if ($existingQuest) {
            if ($existingQuest->status === 'completed') {
                throw new Exception('Quest already completed');
            }
            throw new Exception('Quest already in progress');
        }

        return Player_Quest::create([
            'player_id' => $player->id,
            'quest_id' => $quest->id,
            'status' => 'in-progress',
        ]);
    }

    /**
     * Complete a quest and distribute rewards.
     *
     * @throws Exception
     */
    public function completeQuest(Player $player, Quest $quest): array
    {
        DB::beginTransaction();

        try {
            // Find the player's quest record
            $playerQuest = Player_Quest::where('player_id', $player->id)
                ->where('quest_id', $quest->id)
                ->where('status', 'in-progress')
                ->first();

            if (! $playerQuest) {
                throw new Exception('Quest not found or not in progress');
            }

            // Update quest status
            $playerQuest->status = 'completed';
            $playerQuest->save();

            // Distribute rewards
            $rewards = $this->distributeRewards($player, $quest);

            DB::commit();

            return $rewards;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Distribute quest rewards to the player.
     */
    protected function distributeRewards(Player $player, Quest $quest): array
    {
        $rewards = [];

        // Award experience
        if ($quest->experience_reward > 0) {
            $player->experience += $quest->experience_reward;

            // Level up if enough experience (simple formula: 100 XP per level)
            $requiredXpForNextLevel = $player->level * 100;
            if ($player->experience >= $requiredXpForNextLevel) {
                $player->level++;
                $rewards['level_up'] = true;
            }

            $player->save();
            $rewards['experience'] = $quest->experience_reward;
        }

        // Award item
        if ($quest->item_reward_id) {
            // Add item to player's inventory
            DB::table('player__items')->insert([
                'player_id' => $player->id,
                'item_id' => $quest->item_reward_id,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rewards['item'] = $quest->itemReward;
        }

        return $rewards;
    }

    /**
     * Get available quests for a player (not started yet).
     *
     * @return Collection
     */
    public function getAvailableQuests(Player $player)
    {
        $acceptedQuestIds = $player->quests()->pluck('quests.id');

        return Quest::whereNotIn('id', $acceptedQuestIds)->get();
    }

    /**
     * Get active quests for a player.
     *
     * @return Collection
     */
    public function getActiveQuests(Player $player)
    {
        return $player->activeQuests()->get();
    }

    /**
     * Get completed quests for a player.
     *
     * @return Collection
     */
    public function getCompletedQuests(Player $player)
    {
        return $player->completedQuests()->get();
    }

    /**
     * Abandon a quest.
     *
     * @throws Exception
     */
    public function abandonQuest(Player $player, Quest $quest): bool
    {
        $playerQuest = Player_Quest::where('player_id', $player->id)
            ->where('quest_id', $quest->id)
            ->where('status', 'in-progress')
            ->first();

        if (! $playerQuest) {
            throw new Exception('Quest not found or not in progress');
        }

        return $playerQuest->delete();
    }
}
