<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InviteSent implements ShouldBroadcast
{
    public $message;
    public $userId;

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct($message, $userId) {
        $this->message = $message;
        $this->userId = $userId;
    }

    public function broadcastOn() {
        // Channel privat khusus untuk user tersebut
        return new PrivateChannel('user.'.$this->userId);
    }
}
