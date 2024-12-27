<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RideNowUserResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'telno' => $this->telno,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'email' => $this->email,
            'profile_picture' => $this->userDetails->profile_picture
                ? asset($this->userDetails->profile_picture)
                : null,
            'ratings' => $this->userDetails->ratings,
        ];
    }
}
