<?php

namespace App\Events;

use App\Models\Player;
use App\Models\Quest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $player;

    public $quest;

    public $experienceGained;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Player $player, Quest $quest, int $experienceGained)
    {
        $this->player = $player;
        $this->quest = $quest;
        $this->experienceGained = $experienceGained;
        $this->message = "{$player->username} completed quest: {$quest->name}!";
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('game-events');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'quest.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'player_id' => $this->player->id,
            'player_username' => $this->player->username,
            'quest_id' => $this->quest->id,
            'quest_name' => $this->quest->name,
            'experience_gained' => $this->experienceGained,
            'message' => $this->message,
        ];
    }
}
