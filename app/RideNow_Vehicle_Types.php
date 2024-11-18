<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RideNow_Vehicle_Types extends Model
{
    protected $primaryKey = 'vehicle_type_id';
    //Vehicles that belongs to particular type
    public function vehicles(){
        return $this->hasMany(RideNow_Vehicles::class,'vehicle_type_id');
    }
}
