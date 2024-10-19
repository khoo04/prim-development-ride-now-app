<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dish_Type extends Model
{
    public function dishes()
    {
        return $this->hasMany(Dish::class);
    }
}
