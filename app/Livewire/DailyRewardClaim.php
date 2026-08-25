<?php

namespace App\Livewire;

use App\Models\Player;
use App\Services\DailyRewardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DailyRewardClaim extends Component
{
    public $player;

    public $canClaim = false;

    public $currentStreak = 0;

    public $lastReward;

    public function mount()
    {
        $user = Auth::user();
        $this->player = $user->player ?? Player::where('email', $user->email)->first();
        $this->refreshRewardStatus();
    }

    public function refreshRewardStatus()
    {
        $rewardService = app(DailyRewardService::class);
        $this->canClaim = $rewardService->canClaimReward($this->player);
        $this->currentStreak = $rewardService->getCurrentStreak($this->player);
        $this->lastReward = $this->player->dailyRewards()->latest('reward_date')->first();
    }

    public function claimReward()
    {
        $rewardService = app(DailyRewardService::class);
        $reward = $rewardService->claimDailyReward($this->player);

        if ($reward) {
            $this->player->refresh();
            $this->refreshRewardStatus();
            $this->dispatch('show-message', message: "Daily reward claimed! +{$reward->gold_rewarded} Gold, +{$reward->experience_rewarded} XP. Streak: {$reward->day_streak} days!");
            $this->dispatch('reward-claimed');
        } else {
            $this->dispatch('show-error', message: 'Already claimed today!');
        }
    }

    public function render()
    {
        return view('livewire.daily-reward-claim');
    }
}
