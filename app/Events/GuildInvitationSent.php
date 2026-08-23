<?php

namespace App\Events;

use App\Models\Guild;
use App\Models\Player;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuildInvitationSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $player;

    public $guild;

    public $inviter;

    public function __construct(Player $player, Guild $guild, Player $inviter)
    {
        $this->player = $player;
        $this->guild = $guild;
        $this->inviter = $inviter;
    }
}
