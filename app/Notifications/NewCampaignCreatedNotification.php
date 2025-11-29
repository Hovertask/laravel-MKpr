<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewCampaignCreatedNotification extends Notification
{
    use Queueable;

    public $type;
    public $data;

    public function __construct(string $type, array $data)
    {
        $this->type = $type;     // advert or engagement
        $this->data = $data;     // advert/task data
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
{
    $title = $this->type === 'advert'
        ? 'A New Advert Has Been Created'
        : 'A New Engagement Task Is Now Available';

    $description = $this->type === 'advert'
        ? 'A new advert has been launched on the platform. Feel free to explore and participate if it matches your interest.'
        : 'A new engagement task is now live and participants are needed. You can join and earn by completing the task.';

    // Determine correct frontend URL
    $baseUrl = config('dashboard'); // Example: https://yourfrontend.com
    $id = $this->data['id'] ?? null;

    $path = $this->type === 'advert'
        ? "earn/adverts/{$id}"
        : "earn/tasks/{$id}";

    $fullUrl = rtrim($baseUrl, '/') . '/' . $path;

    return (new MailMessage)
        ->subject($title)
        ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
        ->line($description)
        ->line('Title: ' . ($this->data['title'] ?? 'No title provided'))
        ->line('Category: ' . ($this->data['category'] ?? 'Uncategorized'))
        ->line('Estimated Cost: NGN' . number_format(($this->data['estimated_cost'] ?? 0), 2))
        ->action('View on Platform', $fullUrl)
        ->line('Thank you for staying active on our platform!');
}

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->data['title'] ?? null,
            'category' => $this->data['category'] ?? null,
            'estimated_cost' => $this->data['estimated_cost'] ?? null,
            'timestamp' => now()
        ];
    }
}
