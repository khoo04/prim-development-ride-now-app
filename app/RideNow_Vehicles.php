<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RideNow_Vehicles extends Model
{
    //Soft Deletes for Vehicles
    use SoftDeletes;
    //

    protected $primaryKey = 'vehicle_id';

    protected $fillable = [
        'vehicle_registration_number', 
        'manufacturer', 
        'model', 
        'seats', 
        'average_fuel_consumptions',
        'vehicle_type_id',
        'user_id',
    ];


    //User that obtain this vehicles
    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function vehicleType(){
        return $this->belongsTo(RideNow_Vehicle_Types::class,'vehicle_type_id');
    }

    public function rides()
{
    return $this->hasMany(RideNow_Rides::class, 'vehicle_id');
}
}
