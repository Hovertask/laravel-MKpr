<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UserWalletUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $walletBalance;

    public function __construct($userId, $walletBalance)
    {
        $this->userId = $userId;
        $this->walletBalance = $walletBalance;
    }

    public function broadcastOn()
    {
        return new Channel("user.{$this->userId}");
    }

    public function broadcastAs()
    {
        return 'wallet-updated';
    }
}
