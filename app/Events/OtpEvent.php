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

class OtpEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $user_id;
    public $user_type;
    public $expired;
    public $otp_code;
    /**
     * Create a new event instance.
     */
    public function __construct($otp_code,$expired,$user_id,$user_type)//$listNoti,
    {
        $this->expired = $expired;
        $this->otp_code = $otp_code;
        $this->user_id = $user_id;
        $this->user_type = $user_type;

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel('otp_code.'.$this->user_type.'.' . $this->user_id);
    }

    public function broadcastAs()
    {
        return 'otp.new';
    }
}
