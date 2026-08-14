<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipExpiringNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your membership expires on '.$notifiable->membership_expires_at->format('d M Y'))
            ->greeting('Namaste '.$notifiable->name.',')
            ->line('Your Sepaktakraw Association Of Madhya Pradesh membership (User ID: '.$notifiable->user_id.') is due to expire on '.$notifiable->membership_expires_at->format('d M Y').'.')
            ->line('Please renew before this date to avoid losing access to your account.')
            ->action('Renew membership', url('/membership/renew'))
            ->line('If access lapses, your data and history remain safe and are restored automatically once you renew.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Membership renewal due soon',
            'message' => 'Your membership expires on '.$notifiable->membership_expires_at->format('d M Y').'. Renew to keep your account active.',
            'expires_at' => $notifiable->membership_expires_at->toDateString(),
        ];
    }
}
