<?php

use App\Broadcasting\RideChannel;
use App\Broadcasting\RideNow_UserChannel;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Broadcast::channel('App.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });


Broadcast::channel('ride',RideChannel::class);

Broadcast::channel('user.{userId}',RideNow_UserChannel::class);
// Broadcast::channel('ride.{rideId}', function ($user, $rideId) {
//     return true; // Temporarily allow all users to access the channel
// });

