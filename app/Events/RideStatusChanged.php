<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\RideNowRideResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RideStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ride;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($ride)
    {
        //
        $this->ride = $ride;  // Assign the ride model to the event
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        //return new PrivateChannel('channel-name');
        // return new Channel('chat');
        return new Channel('ride');
    }

    public function broadcastAs()
    {
        return 'ride.status.changed';
    }

    /**
     * Data sent with the broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Eager load related models if not already loaded
        $this->ride->load(['driver', 'passengers', 'vehicle']);

        // Return transformed data using the RideNowRideResource
        return [
            "success" => true,
            "message" => "Retrieved updated ride with id " . $this->ride->ride_id ,
            "data" => new RideNowRideResource($this->ride)
        ];
    }
}
