<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dish extends Model
{
      /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    
     protected $fillable = ['name', 'price', 'dish_image', 'organ_id', 'dish_type'];


    public function type(){
        return $this->hasOne(Dish_Type::class,'dish_type');
    }

    public function organization(){
        return $this->hasOne(Organization::class,'organ_id');
    }
}
