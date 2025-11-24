<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ProductSoldNotification extends Notification
{
    use Queueable;

    protected $product;
    protected $quantitySold;

    public function __construct($product, $quantitySold)
    {
        $this->product = $product;
        $this->quantitySold = $quantitySold;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your product was sold!')
            ->line("Good news! Your product **{$this->product->name}** was just sold.")
            ->line("Quantity sold: **{$this->quantitySold}**")
            ->action('View Product', url('/products/' . $this->product->id));
    }

    public function toArray($notifiable)
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity_sold' => $this->quantitySold,
            'message' => "Your product '{$this->product->name}' was sold. Quantity: {$this->quantitySold}",
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
