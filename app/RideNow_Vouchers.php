<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RideNow_Vouchers extends Model
{
    protected $fillable = [
        'amount',
        'status',
        'user_id',
    ];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function payment(){
        return $this->hasOne(RideNow_Payments::class, 'voucher_id');
    }
}
