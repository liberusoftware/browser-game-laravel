<?php

namespace App\Livewire;

use App\Models\Leaderboard;
use Livewire\Component;

class LeaderboardPanel extends Component
{
    public $selectedCategory = 'level';

    public $categories = [
        'level' => 'Top Players by Level',
        'pvp_wins' => 'PvP Champions',
        'quests' => 'Quest Masters',
        'wealth' => 'Richest Players',
    ];

    public function selectCategory($category)
    {
        $this->selectedCategory = $category;
    }

    public function render()
    {
        $leaderboard = Leaderboard::where('category', $this->selectedCategory)
            ->with('player')
            ->orderBy('rank')
            ->limit(20)
            ->get();

        return view('livewire.leaderboard-panel', [
            'leaderboard' => $leaderboard,
        ]);
    }
}
