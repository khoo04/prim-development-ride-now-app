<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RideNow_Vehicle_Types extends Model
{
    //Vehicles that belongs to particular type
    public function vehicles(){
        return $this->hasMany(RideNow_Vehicles::class,'vehicle_type_id');
    }
}
