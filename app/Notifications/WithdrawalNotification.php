<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class WithdrawalNotification extends Notification
{
    use Queueable;

    protected $amount;
    protected $status;
    protected $reference;

    public function __construct($amount, $status = 'pending', $reference = null)
    {
        $this->amount = $amount;
        $this->status = $status;
        $this->reference = $reference;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Withdrawal ' . ucfirst($this->status),
            'message' => "Your withdrawal of NGN " . number_format($this->amount, 2) . " has been {$this->status}.",
            'amount' => $this->amount,
            'status' => $this->status,
            'reference' => $this->reference,
            'timestamp' => now(),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
