<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuestCompletedNotification extends Notification
{
    use Queueable;

    protected $quest;

    protected $reward;

    public function __construct($quest, $reward = null)
    {
        $this->quest = $quest;
        $this->reward = $reward;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject('Quest Completed!')
            ->greeting('Congratulations, '.$notifiable->username.'!')
            ->line('You have successfully completed the quest: '.$this->quest->name);

        if ($this->reward) {
            $message->line('Reward: '.$this->reward);
        }

        $message->line('Keep up the great work!')
            ->action('View Quest Details', url('/quests/'.$this->quest->id));

        return $message;
    }

    public function toArray($notifiable): array
    {
        return [
            'quest_id' => $this->quest->id,
            'quest_name' => $this->quest->name,
            'reward' => $this->reward,
            'message' => 'Quest completed: '.$this->quest->name,
        ];
    }
}
