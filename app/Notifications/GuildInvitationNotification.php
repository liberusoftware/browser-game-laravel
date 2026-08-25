<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuildInvitationNotification extends Notification
{
    use Queueable;

    protected $guild;

    protected $inviter;

    public function __construct($guild, $inviter)
    {
        $this->guild = $guild;
        $this->inviter = $inviter;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Guild Invitation')
            ->greeting('Hello, '.$notifiable->username.'!')
            ->line('You have been invited to join the guild: '.$this->guild->name)
            ->line('Invited by: '.$this->inviter->username)
            ->action('View Invitation', url('/guilds/invitations'))
            ->line('Join forces and conquer together!');
    }

    public function toArray($notifiable): array
    {
        return [
            'guild_id' => $this->guild->id,
            'guild_name' => $this->guild->name,
            'inviter_id' => $this->inviter->id,
            'inviter_username' => $this->inviter->username,
            'message' => 'You have been invited to join '.$this->guild->name,
        ];
    }
}
