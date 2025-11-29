<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\InitializeDeposit;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class WalletFundedNotification extends Notification
{
    use Queueable;
    public $paymentData;
    /**
     * Create a new notification instance.
     */
    public function __construct(InitializeDeposit $paymentData)
    {
        $this->paymentData = $paymentData;
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
        ->subject('Wallet Funded Successfully')
        ->greeting('Hello ' . $notifiable->name . ',')
        ->line('Your wallet has been funded successfully.')
        ->line('Amount Credited: NGN' . number_format($this->paymentData->amount / 100, 2))
        ->line('The funds have been added to your account and are now available for use.')
        ->action('View Wallet', config('dashboard') . '/fund-wallet')
        ->line('Thank you for choosing our platform!');
}

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Wallet Funded Successfully',
            'message' => 'Your wallet has been funded successfully with NGN' . number_format($this->paymentData['amount'] / 100, 2) . '. Thank you!',
            'paymentData' => $this->paymentData,
            'type' => 'wallet_funding',
            'timestamp' => now(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
