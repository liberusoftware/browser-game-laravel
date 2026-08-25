<?php

namespace App\Livewire;

use App\Models\Player;
use App\Services\CombatService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CombatArena extends Component
{
    public $player;

    public $selectedOpponent;

    public $opponentLevel;

    public $battle;

    public $battleInProgress = false;

    public function mount()
    {
        $user = Auth::user();
        $player = $user->player ?? Player::where('email', $user->email)->first();
        $this->player = $player;
        $this->opponentLevel = max(1, $player->level - 2);
    }

    public function startPvEBattle()
    {
        if ($this->player->health < 10) {
            $this->dispatch('show-message', message: 'You need to heal before battling!');

            return;
        }

        $opponents = [
            'Goblin Scout', 'Forest Troll', 'Dark Mage', 'Orc Warrior',
            'Shadow Assassin', 'Fire Elemental', 'Ice Giant', 'Demon Lord',
        ];

        $opponentName = $opponents[array_rand($opponents)];

        $combatService = app(CombatService::class);
        $this->battle = $combatService->initiatePvEBattle($this->player, $opponentName, $this->opponentLevel);
        $this->battleInProgress = true;
        $this->player->refresh();

        $this->dispatch('battle-completed');
    }

    public function heal()
    {
        $goldResource = $this->player->resources()->where('resource_type', 'gold')->first();
        $healCost = 50;

        if (! $goldResource || $goldResource->quantity < $healCost) {
            $this->dispatch('show-message', message: 'Not enough gold! Need 50 gold to heal.');

            return;
        }

        $goldResource->decrement('quantity', $healCost);

        $combatService = app(CombatService::class);
        $combatService->healPlayer($this->player);
        $this->player->refresh();

        $this->dispatch('show-message', message: 'You have been healed!');
    }

    public function viewBattleLog()
    {
        $this->battleInProgress = false;
    }

    public function render()
    {
        return view('livewire.combat-arena', [
            'recentBattles' => $this->player->battlesAsAttacker()
                ->latest()
                ->with(['defender', 'winner'])
                ->limit(5)
                ->get(),
        ]);
    }
}
