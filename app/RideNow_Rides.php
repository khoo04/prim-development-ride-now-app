<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RideNow_Rides extends Model
{
    protected $primaryKey = 'ride_id';
    //
    protected $fillable = [
        'origin_address',
        'destination_address',
        'departure_time',
        'status',
        'base_cost',
        'vehicle_id',
        'user_id',
    ];

    public function driver(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function passengers(){
        return $this
        ->belongsToMany(User::class,'ride_now__rides_users','ride_id','user_id')
        ->withPivot('joined')
        ->withTimestamps();
    }

    public function vehicle(){
        return $this->belongsTo(RideNow_Vehicles::class, 'vehicle_id');
    }
}
