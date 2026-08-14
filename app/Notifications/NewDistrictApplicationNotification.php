<?php

namespace App\Notifications;

use App\Models\RegistrationApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDistrictApplicationNotification extends Notification
{
    use Queueable;

    public function __construct(public RegistrationApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New registration awaiting your review — '.$this->application->reference_no)
            ->greeting('Namaste '.$notifiable->name.',')
            ->line('A new registration has been submitted and paid for in your district and is awaiting your review.')
            ->line('Applicant: '.$this->application->applicant_name)
            ->line('Reference: '.$this->application->reference_no)
            ->action('Review application', url('/admin/registration-applications'))
            ->line('Please review and approve or reject it at your earliest convenience.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New registration awaiting review',
            'message' => $this->application->applicant_name.' ('.$this->application->reference_no.') has completed payment and needs review.',
            'application_id' => $this->application->id,
            'reference_no' => $this->application->reference_no,
        ];
    }
}
