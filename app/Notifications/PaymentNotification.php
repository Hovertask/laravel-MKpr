<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\InitializeDeposit;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class PaymentNotification extends Notification
{
    use Queueable;
    public $responseData;
    /**
     * Create a new notification instance.
     */
    public function __construct(InitializeDeposit $responseData)
    {
        $this->responseData = $responseData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Order Successfull',
            'message' => 'Your Order is successfull with NGN' . number_format($this->responseData['amount'] / 100, 2) . '. Thank you!',
            'paymentData' => $this->responseData,
            'type' => 'Order',
            'timestamp' => now(),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
