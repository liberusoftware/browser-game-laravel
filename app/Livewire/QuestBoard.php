<?php

namespace App\Livewire;

use App\Events\PlayerLeveledUp;
use App\Events\QuestCompleted as QuestCompletedEvent;
use App\Models\Player;
use App\Models\Quest;
use Livewire\Component;

class QuestBoard extends Component
{
    public $player;

    public $availableQuests;

    public $activeQuests;

    public $completedQuests;

    protected $listeners = [
        'player-stats-updated' => 'refreshQuests',
    ];

    public function mount()
    {
        $this->loadQuests();
    }

    public function loadQuests()
    {
        // Get the first player for demo
        $this->player = Player::with(['quests', 'items'])->first();

        if (! $this->player) {
            $this->player = Player::create([
                'username' => 'Demo Player',
                'email' => 'demo@example.com',
                'password' => bcrypt('password'),
                'level' => 1,
                'experience' => 0,
            ]);
        }

        // Get player's quest IDs
        $playerQuestIds = $this->player->quests()->pluck('quests.id');

        // Get available quests (not started by player)
        $this->availableQuests = Quest::with('itemReward')
            ->whereNotIn('id', $playerQuestIds)
            ->get();

        // Get active quests
        $this->activeQuests = $this->player->activeQuests()
            ->with('itemReward')
            ->get();

        // Get completed quests
        $this->completedQuests = $this->player->completedQuests()
            ->with('itemReward')
            ->get();
    }

    public function acceptQuest($questId)
    {
        $quest = Quest::find($questId);

        if (! $quest) {
            session()->flash('error', 'Quest not found!');

            return;
        }

        // Attach quest to player with in-progress status
        $this->player->quests()->attach($questId, [
            'status' => 'in-progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->loadQuests();
        $this->dispatch('quest-accepted', questId: $questId);
        session()->flash('success', "Quest '{$quest->name}' accepted!");
    }

    public function completeQuest($questId)
    {
        $quest = Quest::with('itemReward')->find($questId);

        if (! $quest) {
            session()->flash('error', 'Quest not found!');

            return;
        }

        // Update quest status to completed
        $this->player->quests()->updateExistingPivot($questId, [
            'status' => 'completed',
            'updated_at' => now(),
        ]);

        // Award experience
        $experienceGained = $quest->experience_reward;
        $this->player->experience += $experienceGained;

        // Check for level up
        while ($this->player->experience >= ($this->player->level * 100)) {
            $this->player->level++;
            $this->dispatch('player-leveled-up', level: $this->player->level);

            // Broadcast level up event
            event(new PlayerLeveledUp($this->player, $this->player->level));
        }

        $this->player->save();

        // Award item if available
        if ($quest->item_reward_id) {
            $existingItem = $this->player->items()->where('item_id', $quest->item_reward_id)->first();

            if ($existingItem) {
                // Increment quantity
                $this->player->items()->updateExistingPivot($quest->item_reward_id, [
                    'quantity' => $existingItem->pivot->quantity + 1,
                ]);
            } else {
                // Add new item
                $this->player->items()->attach($quest->item_reward_id, [
                    'quantity' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Broadcast quest completed event
        event(new QuestCompletedEvent($this->player, $quest, $experienceGained));

        $this->loadQuests();
        $this->dispatch('quest-completed', questId: $questId, experienceReward: $experienceGained);
        $this->dispatch('player-updated');

        session()->flash('success', "Quest '{$quest->name}' completed! You earned {$experienceGained} XP!");
    }

    public function abandonQuest($questId)
    {
        $quest = Quest::find($questId);

        if (! $quest) {
            session()->flash('error', 'Quest not found!');

            return;
        }

        // Remove quest from player
        $this->player->quests()->detach($questId);

        $this->loadQuests();
        $this->dispatch('quest-abandoned', questId: $questId);
        session()->flash('info', "Quest '{$quest->name}' abandoned.");
    }

    public function refreshQuests()
    {
        $this->loadQuests();
    }

    public function render()
    {
        return view('livewire.quest-board');
    }
}
