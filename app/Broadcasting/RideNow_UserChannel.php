<?php

namespace App\Broadcasting;

use App\User;

class RideNow_UserChannel
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
     * @return array|bool
     */
    public function join(User $user, $userId)
    {
        return $user->id == $userId;
    }
}
