<?php

namespace App\Notifications;

use App\Models\KYC;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class KYCSubmittedNotification extends Notification
{
    use Queueable;
    public $kyc;
    /**
     * Create a new notification instance.
     */
    public function __construct(KYC $kyc)
    {
        $this->kyc = $kyc;
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
            ->subject('KYC Submission Received')
            ->greeting("Hello {$notifiable->lname},")
            ->line('We have successfully received your KYC documents.')
            ->line('Your submission details:')
            ->line("Type: {$this->kyc->document_type}")
            ->line("Number: {$this->kyc->document_number}")
            ->line('Status: Pending Approval')
            ->line('Our team will review your documents shortly. You will be notified once the verification is complete.')
            //->action('View Status', route('kyc.status'))
            ->line('Thank you for your patience!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
             'kyc_id' => $this->kyc->id,
            'title' => 'KYC Submission Received',
            'message' => 'Your KYC documents are under review. We will notify you once approved.',
            //'url' => route('kyc.status'),
            'type' => 'kyc_submission'
        ];
    }
}
