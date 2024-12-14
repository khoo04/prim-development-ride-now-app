<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RideNowRideResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
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
            'passengers' =>  RideNowUserResource::collection($this->whenLoaded('passengers')),
            'vehicle' => $this->whenLoaded('vehicle'), 
        ];
    }
}
