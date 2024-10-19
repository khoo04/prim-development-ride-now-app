<?php

namespace App\Http\Controllers\OrderSAPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
    * Login Function for OrderS API
    * 
    * This function handles the user login for the OrderS API. Users can log in 
    * either with a Malaysia phone number (formatted as +60XXXXXXXXX) or an email address. 
    * Upon successful authentication, a Bearer token is generated and returned.
    * 
    * @param Request $request Incoming request containing 'username' (either phone number or email) and 'password'
    * @return JsonResponse JSON response containing user data and access token if successful, or an error message if unauthorized
    * 
    * Example usage:
    * 
    * Request (phone number):
    * {
    *     "username": "+60123456789",
    *     "password": "yourpassword"
    * }
    * 
    * Request (email):
    * {
    *     "username": "user@example.com",
    *     "password": "yourpassword"
    * }
    * 
    * Successful Response:
    * {
    *     "data": "user json data"
    *     "message": "Login success",
    *     "access_token": "token_string",
    *     "token_type": "Bearer"
    * }
    * 
    * Unauthorized Response:
    * {
    *     "message": "Unauthorized"
    * }
    */
    public function login(Request $request)
    {
        //Check if match Malaysia phone number or not
        if(preg_match('/^\+60\d{9,10}$/', $request->get('username'))){
            $phone= $request->get('username');

             $credentials = [
                'telno' => $phone,
                'password' => $request->get('password')
            ];

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $user = 
                $token  = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'data' => $user,
                    'message'       => 'Login success',
                    'access_token'  => $token,
                    'token_type'    => 'Bearer'
                ]);
            }
        }
        //Assume another is using email
        else
        { 
            $credentials = ['email'=> $request->get('username'), 'password' => $request->get('password')];

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $isAdmin = $user->hasRole('OrderS Admin');

                $token  = $user->createToken('auth_token')->plainTextToken;
                
                return response()->json([
                    'data' => $user,
                    'message'       => 'Login success',
                    'access_token'  => $token,
                    'isAdmin' => $isAdmin,
                    'token_type'    => 'Bearer'
                ]);
            }
        }

        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }
}