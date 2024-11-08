<?php

namespace App\Http\Controllers\RideNowAPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function getRoles(){
        $user = Auth::user();

        $isAdmin = $user->hasRole('OrderS Admin');

        return response()->json([
            'isAdmin' => $isAdmin,
        ]);
    }
}