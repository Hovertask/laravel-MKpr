<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewProductCreatedNotification extends Notification 
{
    use Queueable;

    public $product;

    public function __construct(array $product)
    {
        $this->product = $product;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $baseUrl = config('dashboard');

        if (!$baseUrl || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $baseUrl = 'https://app.hovertask.com';
        }

        $id = $this->product['id'] ?? null;

        // Product preview link
        $fullUrl = rtrim($baseUrl, '/') . "/marketplace/p/{$id}";

        return (new MailMessage)
            ->subject('A New Product Has Been Added to the Marketplace')
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line('A new product has just been added to the marketplace.')
            ->line('Product Name: ' . ($this->product['name'] ?? 'N/A'))
            ->line('Category: ' . ($this->product['category_name'] ?? 'N/A'))
            ->line('Price: ' . ($this->product['price'] ?? 'N/A') . ' ' . ($this->product['currency'] ?? ''))
            ->action('View Product', $fullUrl)
            ->line('Check it out and explore more amazing listings!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product['id'] ?? null,
            'name'       => $this->product['name'] ?? null,
            'price'      => $this->product['price'] ?? null,
            'currency'   => $this->product['currency'] ?? null,
            'timestamp'  => now()
        ];
    }
}
