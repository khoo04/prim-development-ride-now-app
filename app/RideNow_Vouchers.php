<?php

namespace App;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class RideNow_Vouchers extends Model
{
    protected $primaryKey = 'voucher_id'; // Define custom primary key
    public $incrementing = false; // Disable auto-incrementing
    protected $keyType = 'string'; // Define key type

    protected $fillable = [
        'amount',
        'redeemed',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'redeemed' => 'boolean',
    ];
    

    public static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            if (empty($voucher->voucher_id)) {
                $voucher->voucher_id = self::generateUniqueVoucherId();
            }
        });
    }

    /**
     * Generate a unique voucher ID.
     *
     * @return string
     */
    private static function generateUniqueVoucherId()
    {
        do {
            $voucherId = 'VCH-' . strtoupper(Str::random(8)); // Generate a candidate ID
        } while (self::where('voucher_id', $voucherId)->exists()); // Ensure it doesn't exist

        return $voucherId;
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function payment(){
        return $this->hasOne(RideNow_Payments::class, 'voucher_id');
    }
}
