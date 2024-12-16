<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RideNow_PaymentAllocation extends Model
{
    protected $primaryKey = 'payment_allocation_id';

    protected $fillable = [
        'status',
        'description',
        'total_amount',
        'ride_id',
        'user_id',
    ];

    public function ride(){
        return $this->belongsTo(RideNow_Rides::class,'ride_id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
}
