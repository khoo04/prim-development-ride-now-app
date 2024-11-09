<?php

namespace App\Http\Controllers\RideNowAPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;

class RideController extends Controller
{
    public function searchRide(){

    }

    public function createRide(Request $request){
        $user = Auth::user();


    }
}