<?php

namespace App\Http\Controllers\RideNowAPI;

use App\User;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\RideNowUserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function listUserVouchers()
    {
        $user = Auth::user();

        $vouchers = $user->vouchers()->where('redeemed', false)->get();

        return response()->json([
            "success" => true,
            'data' => $vouchers,
            'message' => 'User vouchers retrieved successfully',
        ], 200);
    }


    public function updateUserProfile(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => ['sometimes', 'string', 'max:255'],
                'profile_picture' => ['sometimes', 'file', 'image'],
                'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users'],
                'old_password' => [
                    'required_with:new_password',
                    'min:8',
                    function ($attribute, $value, $fail) {
                        if (!Hash::check($value, auth()->user()->password)) {
                            $fail('The old password is incorrect.');
                        }
                    }
                ],
                'new_password' => [
                    'required_with:old_password',
                    'min:8',
                    'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[@!$#%^&*()]).*$/'
                ],
                'phone_number' => ['sometimes', 'regex:/^\+60\d{9,10}$/', 'unique:users,telno'],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        try {

            // Handle profile image upload
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                // Generate a unique file name using user_id and current timestamp
                $fileName = $user->id . '-' . time() . '.' . $file->getClientOriginalExtension(); // user_id-currenttimestamp.extension

                // Define the file path to store the image in the public folder
                $filePath = 'ride_now_profile_picture/' . $fileName;

                // Move the file to the public directory
                $file->move(public_path('ride_now_profile_picture'), $filePath);

                // Delete the old profile image if it exists
                if (!empty($user->userDetails->profile_picture)) {
                    $oldImagePath = public_path($user->userDetails->profile_picture);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);  // Delete old file
                    }
                }

                // Save the new profile image path in the database
                $user->userDetails->profile_picture = $filePath;
                $user->userDetails->save();
            }

            // Update other fields if they exist
            if ($request->filled('name')) {
                $user->name = $request->input('name');
            }
            if ($request->filled('email')) {
                $user->email = $request->input('email');
            }
            if ($request->filled('new_password')) {
                $user->password = Hash::make($request->input('new_password'));
            }
            if ($request->filled('phone_number')) {
                $user->telno = $request->input('phone_number');
            }

            $user->save();

            $user->refresh();


            return response()->json([
                'success' => true,
                'message' => 'User profile updated successfully',
                'data' => new RideNowUserResource($user),
            ]);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in updating profile",
            ], 500);
        }
    }
}
