<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RideNow_Payments extends Model
{
    protected $primaryKey = 'payment_id';
    public $incrementing = false; // Important for non-integer primary keys
    protected $keyType = 'string'; // Explicitly declare the primary key type as string

    protected $fillable = [
        'payment_id',
        'status',
        'required_seats',
        'amount',
        'ride_id',
        'user_id',
        'voucher_id',
        'payment_allocation_id',
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
