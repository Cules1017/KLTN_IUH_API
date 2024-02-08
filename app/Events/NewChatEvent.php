<?php

namespace App\Events;

use App\Models\ChatRooms;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $chatRoom;
    public $reciver;
    public $type;
    /**
     * Create a new event instance.
     */
    public function __construct(ChatRooms $chatRooms,$reciver,string $type)
    {
        $this->chatRoom = $chatRooms;
        $this->reciver = $reciver;
        $this->type = $type;

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new PrivateChannel('chat_room.'.$this->type.'.' . $this->reciver);
    }

    public function broadcastAs()
    {
        return 'chat.new';
    }
}
