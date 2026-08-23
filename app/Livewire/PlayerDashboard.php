<?php

namespace App\Livewire;

use App\Events\PlayerLeveledUp;
use App\Models\Player;
use Livewire\Component;

class PlayerDashboard extends Component
{
    public $player;

    public $experiencePercentage;

    public $nextLevelXp;

    protected $listeners = [
        'player-updated' => 'refreshPlayer',
        'quest-completed' => 'handleQuestCompleted',
    ];

    public function mount()
    {
        $this->loadPlayer();
    }

    public function loadPlayer()
    {
        // For demo purposes, we'll use the first player or create a demo player
        // In production, this would be linked to the authenticated user
        $this->player = Player::with([
            'activeQuests.quest',
            'items',
            'guilds',
            'resources',
        ])->first();

        if (! $this->player) {
            // Create a demo player for testing
            $this->player = Player::create([
                'username' => 'Demo Player',
                'email' => 'demo@example.com',
                'password' => bcrypt('password'),
                'level' => 1,
                'experience' => 0,
            ]);
        }

        $this->calculateProgress();
    }

    public function calculateProgress()
    {
        // Simple XP calculation: Level * 100 XP needed for next level
        $this->nextLevelXp = $this->player->level * 100;
        $currentLevelXp = ($this->player->level - 1) * 100;
        $xpInCurrentLevel = $this->player->experience - $currentLevelXp;
        $xpNeededForLevel = $this->nextLevelXp - $currentLevelXp;

        $this->experiencePercentage = $xpNeededForLevel > 0
            ? ($xpInCurrentLevel / $xpNeededForLevel) * 100
            : 0;
    }

    public function refreshPlayer()
    {
        $this->loadPlayer();
        $this->dispatch('player-stats-updated');
    }

    public function handleQuestCompleted($questId, $experienceReward)
    {
        $this->player->experience += $experienceReward;

        // Check for level up
        while ($this->player->experience >= ($this->player->level * 100)) {
            $this->player->level++;
            $this->dispatch('player-leveled-up', level: $this->player->level);

            // Broadcast level up event
            event(new PlayerLeveledUp($this->player, $this->player->level));
        }

        $this->player->save();
        $this->refreshPlayer();
    }

    public function render()
    {
        return view('livewire.player-dashboard');
    }
}
