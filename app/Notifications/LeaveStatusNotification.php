<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private LeaveRequest $leaveRequest,
        private string       $action, 
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
        $isApproved = $this->action === 'approved';
        $type       = $this->leaveRequest->leaveType->name;
        $start      = $this->leaveRequest->start_date->format('M d, Y');
        $end        = $this->leaveRequest->end_date->format('M d, Y');

        $mail = (new MailMessage)
            ->subject("Leave Request " . ucfirst($this->action))
            ->greeting("Hello {$notifiable->first_name},")
            ->line("Your {$type} leave request from {$start} to {$end} has been **" . strtoupper($this->action) . "**.");

        if (!$isApproved && $this->leaveRequest->rejection_reason) {
            $mail->line("Reason: {$this->leaveRequest->rejection_reason}");
        }

        return $mail->line('You can view your leave history in the app.');
    }

     public function toDatabase(object $notifiable): array
    {
        return [
            'title'            => 'Leave Request ' . ucfirst($this->action),
            'body'             => "Your {$this->leaveRequest->leaveType->name} leave has been {$this->action}.",
            'type'             => 'leave_status',
            'leave_request_id' => $this->leaveRequest->id,
        ];
    }
}
