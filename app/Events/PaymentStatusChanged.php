<?php

namespace App\Events;

use App\RideNow_Rides;
use App\RideNow_Payments;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\RideNowRideResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PaymentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    protected $ride; // Removed type hint
    protected $payment; // Removed type hint
    protected $success;
    protected $message;

    /**
     * Create a new event instance.
     *
     * @param RideNow_Rides|null $ride
     * @param RideNow_Payments $payment
     * @param int $userId
     * @param bool $success
     * @param string $message
     */
    public function __construct($ride, $payment, $userId, $success, $message)
    {
        $this->ride = $ride; // Nullable ride assignment
        $this->payment = $payment;
        $this->userId = $userId;
        $this->success = $success;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.'. $this->userId);
    }

    public function broadcastAs()
    {
        return 'payment.status.changed';
    }

    /**
     * Data sent with the broadcast.
     *
     * @return array
     */
    /**
     * Data sent with the broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            "success" => $this->success,
            'message' => $this->message,
            "data" => [
                "ride" => $this->ride ? new RideNowRideResource($this->ride) : null,
                "payment" => $this->payment,
            ],
        ];
    }
}
