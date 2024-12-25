<?php

namespace App\Events;

use App\Http\Resources\RideNowRideResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotifyRidePassengersStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $user;
    protected $ride;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $ride)
    {
        $this->user = $user;
        $this->ride= $ride;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.'. $this->user->id);
    }

    public function broadcastAs()
    {
        return 'joinedRide.status.started';
    }

     /**
     * Data sent with the broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $this->ride->load(['driver', 'passengers', 'vehicle','ratings']);
        
        return [
            "success" => true,
            'message' => "Ride started",
            "data" => new RideNowRideResource($this->ride,$this->user->id),
        ];
    }
}
