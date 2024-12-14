<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RideNow_UserDetails extends Model
{
    
    protected $fillable = [
        'user_id',
        'profile_picture',
        'ratings',
    ];

    // Define relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
