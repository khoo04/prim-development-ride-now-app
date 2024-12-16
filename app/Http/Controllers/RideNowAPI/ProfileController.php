<?php

namespace App\Http\Controllers\RideNowAPI;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function listUserVouchers(){
        $user = Auth::user();

        $vouchers = $user->vouchers()->where('redeemed', false)->get();

        return response()->json([
            "success" => true,
            'data' => $vouchers,
            'message' => 'User vouchers retrieved successfully',
        ], 200);
    }
}
