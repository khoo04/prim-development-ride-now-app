<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RideNow_Payments extends Model
{
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'payment_id',
        'status',
        'amount',
        'ride_id',
        'user_id',
        'voucher_id',
    ];

    public function ride(){
        return $this->belongsTo(RideNow_Rides::class,'ride_id');
    }

    public function voucher(){
        return $this->belongsTo(RideNow_Vouchers::class,'voucher_id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
}
