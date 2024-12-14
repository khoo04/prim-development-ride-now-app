<?php

namespace App\Http\Controllers\RideNowAPI;

use App\User;
use Exception;
use App\RideNow_UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\RideNowUserResource;

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

            $token  = $user->createToken('auth_token')->plainTextToken;

             // Ensure user details exist
            $this->ensureUserDetailsExist($user->id);

            return response()->json([
                "success" => true,
                'data' => new RideNowUserResource($user),
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

    public function logout()
    {
        $user = Auth::user();

        try {
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Failed in logging out user",
            ], 500);
        }
    }


    public function getUserData(Request $request)
    {
        $user = Auth::user();
        
        $this->ensureUserDetailsExist($user->id);

        if ($user != null) {
            return  response()->json([
                'success' => true,
                'data' => new RideNowUserResource($user),
                //'isAdmin' => $isAdmin,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
    }

    /**
     * Ensure user details exist for the given user ID.
     *
     * @param int $userId
     * @return void
     */
    protected function ensureUserDetailsExist($userId)
    {
        // Check if the user_details entry exists
        $userDetail = RideNow_UserDetails::where('user_id', $userId)->first();

        // Create a new entry if it doesn't exist
        if (!$userDetail) {
            RideNow_UserDetails::create([
                'user_id' => $userId,
                'profile_picture' => null, // Default or null
                'ratings' => null, // Default or null
            ]);
        }
    }
}
