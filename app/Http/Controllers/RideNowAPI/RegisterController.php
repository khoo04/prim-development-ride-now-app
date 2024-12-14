<?php

namespace App\Http\Controllers\RideNowAPI;

use App\User;
use App\RideNow_UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use App\Http\Controllers\PointController;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\RideNowUserResource;
use Illuminate\Foundation\Auth\RegistersUsers;


class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Register a new user via API.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerViaApi(Request $request)
    {
        // Validate the incoming request
        $validator = $this->validator($request->all());
            
        // If validation fails, return a JSON response with errors
        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => "Validation failed",
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Proceed with registration
            $user = $this->create($request->all());
        
            event(new Registered($user));

            //refresh the user instance (to load any relationships)
            $user->refresh();
            // Generate an auth token for the new user
            $token = $user->createToken('auth_token')->plainTextToken;
           

            // Return the newly registered user along with the auth token
            return response()->json([
                "success" => true,
                'data' => new RideNowUserResource($user),
                'message' => 'User registered successfully',
                'access_token' => $token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                'message' => 'Registration failed', 
            ], 500);
        }
    }

    /**
     * Get a validator for an incoming registration request.
     * 
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $validator = Validator::make($data, [
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'          => ['required', 'min:8', 'confirmed', 'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[@!$#%^&*()]).*$/'],
            'phone_number'      => ['required', 'numeric', 'regex:/^\+60\d{9,10}$/','unique:users,telno'], // Validate phone number length
        ], [
            'password.regex' => 'Password must contain at least 1 number, 1 uppercase letter, and 1 special character (@!$#%^&*()).'
        ]);

        // Additional validation for referral code if not an admin
        $validator->after(function ($validator) use ($data) {
            if (isset($data['isAdmin'])) {
                return;
            }
            if (isset($data['referral_code'])) {
                $valid = PointController::validateReferralCode($data['referral_code']);
                if (!$valid) {
                    $validator->errors()->add('referral_code', 'Expired referral code.');
                }
            }
        });

        return $validator;
    }

    /**
     * Create a new user instance after a valid registration.
     * 
     * @param array $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        // Create the user
        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'telno'             => $data['phone_number'],
        ]);

        // Assign a role to the user, default to role_id 15 (non-admin)
        if (!isset($data['isAdmin'])) {
            DB::table('model_has_roles')->insert([
                'role_id' => 15, // User role ID
                'model_type' => "App\User",
                'model_id' => $user->id,
            ]);
        } 

        RideNow_UserDetails::create([
            'user_id' => $user,
            'profile_picture' => null, // Default to null 
            'ratings' => null, // Default to null
        ]);

        // Process referral code if provided
        $referral_code = $data['referral_code'] ?? 'YahyaNMd0005'; // Default referral code if not provided
        $this->referral_code_member_registration($referral_code, $user);

        return $user;
    }

    /**
     * Handle referral code registration.
     * 
     * @param string $referral_code
     * @param \App\User $user
     * @return void
     */
    protected function referral_code_member_registration($referral_code, $user)
    {
        $code = DB::table('referral_code')->where('code', $referral_code)->first();
        DB::table('referral_code_member')->insert([
            'created_at' => now(),
            'updated_at' => now(),
            'leader_referral_code_id' => $code->id,
            'member_user_id' => $user->id,
            'status' => 1,
        ]);
    }
}