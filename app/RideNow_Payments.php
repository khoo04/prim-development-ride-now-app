<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RideNow_Payments extends Model
{
    protected $fillable = [
        'status',
        'amount',
        'ride_id',
        'voucher_id',
    ];

    public function ride(){
        return $this->belongsTo(RideNow_Rides::class,'ride_id');
    }

    public function voucher(){
        return $this->belongsTo(RideNow_Vouchers::class,'voucher_id');
    }
}
