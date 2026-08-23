<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AchievementUnlockedNotification extends Notification
{
    use Queueable;

    protected $achievementName;

    protected $achievementDescription;

    public function __construct($achievementName, $achievementDescription = null)
    {
        $this->achievementName = $achievementName;
        $this->achievementDescription = $achievementDescription;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject('Achievement Unlocked!')
            ->greeting('Great Job, '.$notifiable->username.'!')
            ->line('You have unlocked a new achievement: '.$this->achievementName);

        if ($this->achievementDescription) {
            $message->line($this->achievementDescription);
        }

        $message->action('View Achievements', url('/achievements'))
            ->line('Keep achieving greatness!');

        return $message;
    }

    public function toArray($notifiable): array
    {
        return [
            'achievement_name' => $this->achievementName,
            'achievement_description' => $this->achievementDescription,
            'message' => 'Achievement unlocked: '.$this->achievementName,
        ];
    }
}
