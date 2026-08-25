<?php

namespace App\Events;

use App\Models\Player;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AchievementUnlocked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $player;

    public $achievementName;

    public $achievementDescription;

    public function __construct(Player $player, $achievementName, $achievementDescription = null)
    {
        $this->player = $player;
        $this->achievementName = $achievementName;
        $this->achievementDescription = $achievementDescription;
    }
}
