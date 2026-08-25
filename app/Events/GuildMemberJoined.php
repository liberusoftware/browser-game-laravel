<?php

namespace App\Events;

use App\Models\Guild;
use App\Models\Player;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuildMemberJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $guild;

    public $player;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Guild $guild, Player $player)
    {
        $this->guild = $guild;
        $this->player = $player;
        $this->message = "{$player->username} joined {$guild->name}!";
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('guild.'.$this->guild->id);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'guild.member-joined';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'guild_id' => $this->guild->id,
            'guild_name' => $this->guild->name,
            'player_id' => $this->player->id,
            'player_username' => $this->player->username,
            'player_level' => $this->player->level,
            'message' => $this->message,
        ];
    }
}
