<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class RideNowRideResource extends JsonResource
{
    protected $userId;

    /**
     * Inject user into the resource.
     */
    public function __construct($resource, $userId = null)
    {
        parent::__construct($resource);
        $this->userId = $userId ?? Auth::id();
    }


    /**
     * Determine if the user has rated the ride.
     */
    protected function getIsRatedForUser($userId)
    {
        // First check if the user is a passenger
        $isPassenger = $this->passengers->contains('id', $userId);

        if (!$isPassenger) {
            return null;
        }

        // Check if ratings relationship is loaded
        if (!$this->relationLoaded('ratings')) {
            return null;
        }

        // Find if the user has rated this ride
        return $this->ratings->contains('user_id', $userId);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $isRated = $this->getIsRatedForUser($this->userId);

        return [
            'ride_id' => $this->ride_id,
            'origin' => [
                'name' => $this->origin_name,
                'formatted_address' => $this->origin_formatted_address,
                'latitude' => $this->origin_latitude,
                'longitude' => $this->origin_longitude,
            ],
            'destination' => [
                'name' => $this->destination_name,
                'formatted_address' => $this->destination_formatted_address,
                'latitude' => $this->destination_latitude,
                'longitude' => $this->destination_longitude,
            ],
            'departure_time' => $this->departure_time,
            'status' => $this->status,
            'base_cost' => $this->base_cost,
            'driver' => new RideNowUserResource($this->whenLoaded('driver')),
            'passengers' => RideNowUserResource::collection($this->whenLoaded('passengers')),
            'isRated' => $isRated,
            'vehicle' => $this->whenLoaded('vehicle'),
        ];
    }
}
