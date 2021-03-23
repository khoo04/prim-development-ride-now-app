<?php

namespace App\Models;

use App\User;
use DonationTransactionTableSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use SoftDeletes;

    public $table = "donations";
    protected $fillable = ['nama', 'description', 'date_created', 'date_started', 'date_end', 'status'];
    
    public $timestamps = false;

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsToMany(User::class, 'donation_user');
    }

    public function reminder()
    {
        return $this->belongsToMany(Reminder::class, 'user_donation_reminder', 'donation_id', 'reminder_id');
    }

    public function transaction()
    {
        return $this->belongsToMany(Transaction::class, 'donation_transaction', 'donation_id', 'transaction_id');
    }

    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, DonationTransaction::class, 'donation_id', 'id', 'id', 'transaction_id');
    }

    public function organization()
    {
        return $this->hasOneThrough(Organization::class, DonationOrganization::class, 'donation_id', 'id', 'id', 'organization_id');
    }
}
