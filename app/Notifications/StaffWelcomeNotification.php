<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffWelcomeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private Organization $organization,
        private string $tempPassword
    )
    {
        //
    }

    
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You've been added to {$this->organization->name}")
            ->greeting("Hello {$notifiable->first_name}!")
            ->line("You have been added as a staff member of **{$this->organization->name}**.")
            ->line("**Your login credentials:**")
            ->line("Email: {$notifiable->email}")
            ->line("Temporary password: `{$this->tempPassword}`")
            ->line("**Organization Code:** `{$this->organization->org_code}`")
            ->line("Please log in and change your password immediately.")
            ->line("On your first login, you will be asked to take a photo for attendance verification.")
            ->action('Download the App', url('/'));
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Welcome to ' . $this->organization->name,
            'body'  => 'Your account has been created. Log in to complete your setup.',
            'type'  => 'welcome',
        ];
    }
}
