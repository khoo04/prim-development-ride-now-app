<?php

namespace App\Broadcasting;

use App\RideNow_Rides;
use App\User;

class RideChannel
{
    /**
     * Create a new channel instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @param  \App\User  $user
     * @param int $rideId
     * @return array|bool
     */
    public function join(User $user)
    {
        return true;
        // // Manually retrieve the RideNow_Rides model using the ride_id
        // $ride = RideNow_Rides::find($rideId);

        // if (!$ride) {
        //     return false; // If no ride is found, deny access
        // }

        // // Check if the user is the driver or a passenger
        // if ($ride->driver && $ride->driver->id === $user->id) {
        //     return true; // User is the driver, allow access
        // }

        // // Check if the user is a passenger
        // return $ride->passengers()->where('user_id', $user->id)->exists();
    }
}
