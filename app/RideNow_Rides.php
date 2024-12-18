<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RideNow_Rides extends Model
{
    protected $primaryKey = 'ride_id';
    //
    protected $fillable = [
        'origin_name',
        'origin_formatted_address',
        'origin_latitude',
        'origin_longitude',
        'destination_name',
        'destination_formatted_address',
        'destination_latitude',
        'destination_longitude',
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
        ->withTimestamps();
    }

    public function vehicle(){
        return $this->belongsTo(RideNow_Vehicles::class, 'vehicle_id');
    }

    public function payments(){
        return $this->hasMany(RideNow_Payments::class,'ride_id');
    }
}
