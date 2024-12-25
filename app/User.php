<?php

namespace App;

use App\Models\Donation;
use App\Models\KoopOrder;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\PickUpOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    
    protected $fillable = ['name', 'email', 'password', 'telno', 'remember_token','device_token'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [ 
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsToMany(Organization::class, 'organization_user', 'user_id', 'organization_id');
    }

    public function organizationRole()
    {
        return $this->belongsToMany(OrganizationRole::class, 'organization_user', '', 'role_id');
    }
    
    public function donation()
    {
        return $this->belongsToMany(Donation::class, 'donation_user');
    }

    public function pickup_order()
    {
        return $this->hasOne(PickUpOrder::class);
    }

    public function getUserById()
    {
        $id = Auth::id();
        // dd($id);
        $user = auth()->user();
        
        return $user;
    }

    public function getUser($id)
    {
        $user = User::find($id);
        return $user;
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'id' ,'customerid');
    }

    //RideNow Relationship

    // Vouchers that user obtain
    public function vouchers()
    {
        return $this->hasMany(RideNow_Vouchers::class, 'user_id');
    }

    // Rides the user has joined as a passenger
    public function joinedRides(){
        return $this
        ->belongsToMany(RideNow_Rides::class,'ride_now__rides_users','user_id','ride_id')
        ->withTimestamps();
    }

    public function ratings(){
        return $this->belongsToMany(User::class,'ride_now__rides_rating','user_id','ride_id')
        ->withPivot('rating');
    }

    // Rides the user has created as a driver or organizer
    public function createdRides(){
        return $this->hasMany(RideNow_Rides::class,'user_id');
    }

    // Payments that belongs to users
    public function payments(){
        return $this->hasMany(RideNow_Payments::class,'user_id');
    }

    //Vehicles that belongs to uesrs
    public function vehicles(){
        return $this->hasMany(RideNow_Vehicles::class,'user_id');
    }

    //User Details 
    public function userDetails()
    {
        return $this->hasOne(RideNow_UserDetails::class,'user_id');
    }
}
