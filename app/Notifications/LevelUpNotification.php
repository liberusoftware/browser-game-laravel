<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LevelUpNotification extends Notification
{
    use Queueable;

    protected $newLevel;

    protected $oldLevel;

    public function __construct($newLevel, $oldLevel)
    {
        $this->newLevel = $newLevel;
        $this->oldLevel = $oldLevel;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Level Up!')
            ->greeting('Congratulations, '.$notifiable->username.'!')
            ->line('You have leveled up from Level '.$this->oldLevel.' to Level '.$this->newLevel.'!')
            ->line('You are getting stronger! Keep playing to unlock new abilities and features.')
            ->action('View Your Profile', url('/player/profile'))
            ->line('Thank you for playing!');
    }

    public function toArray($notifiable): array
    {
        return [
            'new_level' => $this->newLevel,
            'old_level' => $this->oldLevel,
            'message' => 'Leveled up from '.$this->oldLevel.' to '.$this->newLevel.'!',
        ];
    }
}
