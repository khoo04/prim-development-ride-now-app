<?php

namespace App\Http\Controllers\RideNowAPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use Exception;
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
     *     "success" => true,
     *     "data": "user json data"
     *     "message": "Login success",
     *     "access_token": "token_string",
     * }
     * 
     * Unauthorized Response:
     * {
     *     "success" => false,
     *     "message": "Unauthorized"
     * }
     */
    public function login(Request $request)
    {
        $credentials = ['email' => $request->get('email'), 'password' => $request->get('password')];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            //$isAdmin = $user->hasRole('OrderS Admin');
            $token  = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                "success" => true,
                'data' => $user,
                'message' => 'Login success',
                'access_token'  => $token,
                //'isAdmin' => $isAdmin,
            ]);
        }

        return response()->json([
            "success" => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    public function logout() {
        $user = Auth::user();

        try{
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successfully',
            ],200);
        }catch (Exception $e){
            return response()->json([
                "success" => false,
                "message" => "Failed in logging out user",
            ], 500);
        } 
    }


    public function getUserData(Request $request){
        $user = Auth::user();
        if ($user != null){
            return  response()->json([
                'success' => true,
                'data' => $user,             
                //'isAdmin' => $isAdmin,
            ]);
        }else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ],401);
        }
    }
}
