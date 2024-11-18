<?php

namespace App\Http\Controllers\RideNowAPI;

use App\User;
use Exception;
use App\RideNow_Rides;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\RideNow_Vehicles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RideController extends Controller
{
    /**
     * Shared Function
     */
    public function getRideDetails($ride_id)
    {
        try {
            $ride = RideNow_Rides::with(['driver', 'passengers', 'vehicle'])->findOrFail($ride_id);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Ride not found",
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Ride details retrieved successfully',
            'ride' => $ride,
        ], 200);
    }

    public function listAllAvailableRides(Request $request){
        $perPage = $request->query('per_page', 10); // default to 10 rides per page if not specified
        $page = $request->query('page', 1); // default to page 1 if not specified

        try {
            $rides = RideNow_Rides::with(['driver', 'passengers', 'vehicle'])->orderBy('departure_time', 'asc')->paginate($perPage, ['*'], 'page', $page);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "An error occurred while retrieving available rides.",
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully retrieved page ' . $page . ' with up to ' . $perPage . ' rides per page.',
            'data' => $rides->items(),
        ], 200);
    }

    /**
     * Passengers Module
     */
    public function searchRide(Request $request)
    {

        $validator = Validator::make(
            $request->query(),
            [
                'origin' => 'required|string|max:255',
                'destination' => 'required|string|max:255',
                'seats' => 'required|integer|min:1',
                'departure_time' => 'required|date',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        $origin = $validatedData['origin'];
        $destination = $validatedData['destination'];
        $seats = $validatedData['seats'];
        $departureTime = $validatedData['departure_time'];


        try {
            // Start the query for matching any word in origin
            $rides = RideNow_Rides::with('vehicle')
                ->where(function ($query) use ($origin) {
                    // Match anywhere in the origin_address but prioritize beginning matches
                    $query->where('origin_address', 'LIKE', "%{$origin}%")
                        ->orWhereRaw("SUBSTRING_INDEX(origin_address, ',', 1) LIKE ?", ["%{$origin}%"])
                        // Match the second part of the origin_address (between the first and second comma)
                        ->orWhereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(origin_address, ',', 2), ',', -1) LIKE ?", ["%{$origin}%"]);
                })
                ->where(function ($query) use ($destination) {
                    $query->where('destination_address', 'LIKE', "%{$destination}%")
                        ->orWhereRaw("SUBSTRING_INDEX(destination_address, ',', 1) LIKE ?", ["%{$destination}%"])
                        // Match the second part of the origin_address (between the first and second comma)
                        ->orWhereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(destination_address, ',', 2), ',', -1) LIKE ?", ["%{$destination}%"]);
                })
                ->where('departure_time', '>=', $departureTime)
                ->whereHas('vehicle', function ($query) use ($seats) {
                    $query->where('seats', '>=', $seats);
                })
                ->where('status', '=', 'confirmed');


            // Execute the query
            $rides = $rides->get();
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in retrieving rides",
            ], 500);
        }

        // Return the results
        return response()->json([
            'success' => true,
            'message' => 'Rides information with ' . $seats . ' seats from ' . $origin . ' to ' . $destination . ' on ' . $departureTime,
            'data' => $rides,
        ], 200);
    }

    //Show Joined Rides
    public function getJoinedRides()
    {
        $user = Auth::user();

        try {
            $joinedRides = $user->joinedRides;
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in getting joined rides",
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'List of joined rides for user ' . $user->name,
            'data' => $joinedRides,
        ], 200);
    }

    //TODO: Join Rides - Integrate With Payment Gateway
    public function joinRides($ride_id)
    {
        $user = Auth::user();

        try {
            $ride = RideNow_Rides::findOrFail($ride_id);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Ride not found",
            ], 404);
        }

        // Check if user cannot join their own created ride to avoid duplicates
        if ($ride->driver->id == $user->id) {
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "User cannot join their own ride",
            ], 409); // 409 Conflict
        }

        // Check if user is already joined to avoid duplicates
        if ($ride->passengers()->where('user_id', $user->id)->exists()) {
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "User is already joined in this ride",
            ], 409); // 409 Conflict
        }

        //Retrieve vehicle seat count
        $vehicleSeats = $ride->vehicle->seats;

        $currentPassengersCount = $ride->passengers()->count();

        // Check if the ride is at capacity
        if ($currentPassengersCount >= $vehicleSeats) {
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "This ride is at full capacity",
            ], 403); // 403 Forbidden
        }

        try {
            // Attach the user to the ride
            $ride->passengers()->attach($user->id);

            return response()->json([
                'success' => true,
                'message' => 'User joined the ride successfully',
                'ride' => $ride,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "Exception occurred while joining the ride",
            ], 500);
        }
    }

    /**
     * Drivers Module 
     */

    public function createRide(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make(
            $request->all(),
            [
                'origin_address' => 'required | string',
                'destination_address' => 'required | string',
                'departure_time' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) use ($user) {
                        // Check if there's already a ride with the same departure time for this user
                        $existingRide = RideNow_Rides::where('user_id', $user->id)
                            ->where('departure_time', $value)
                            ->first();

                        if ($existingRide) {
                            $fail('You already have a ride scheduled at this departure time.');
                        }
                    },
                ],
                'base_cost' => 'required | numeric',
                'vehicle_id' => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) use ($user) {
                        // Check if the vehicle belongs to the user
                        $vehicleModel = RideNow_Vehicles::find($value);
                        if ($vehicleModel == null) {
                            $fail('Vehicle not found.');
                        } else if ($vehicleModel->user_id != $user->id) {
                            $fail('The selected vehicle does not belong to the authenticated user.');
                        }
                    }
                ],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $rideData = $request->all();
            $rideData['user_id'] = $user->id;
            $ride = RideNow_Rides::create($rideData);

            $ride->status = 'confirmed';
            $ride->save();

            $ride->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Ride created successfully',
                'data' => $ride,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in creating ride",
            ], 500);
        }
    }

    //Driver - Show Upcoming Ride
    public function getCreatedRides()
    {
        $user = Auth::user();

        try {
            $rides = $user->createdRides;
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in getting created rides",
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'List of created rides for user ' . $user->name,
            'data' => $rides,
        ], 200);
    }

    //Driver - Update Ride
    public function updateRide(Request $request, $ride_id)
    {
        try {
            $ride = RideNow_Rides::findOrFail($ride_id);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Ride not found",
            ], 404);
        }

        $user = Auth::user();

        if ($ride->user_id != $user->id) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Unauthorized access.",
            ], 401);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'origin_address' => 'sometimes | string',
                'destination_address' => 'sometimes | string',
                'departure_time' => [
                    'sometimes',
                    'date',
                    function ($attribute, $value, $fail) use ($user, $ride_id) {
                        // Check if there's already a ride with the same departure time for this user
                        $existingRide = RideNow_Rides::where('user_id', $user->id)
                            ->where('departure_time', $value)
                            ->first();

                        if ($existingRide->ride_id != $ride_id) {
                            $fail('You already have a ride scheduled at this departure time.');
                        }
                    },
                ],
                'base_cost' => 'sometimes | numeric',
                'vehicle_id' => [
                    'sometimes',
                    'integer',
                    function ($attribute, $value, $fail) use ($user) {
                        // Check if the vehicle belongs to the user
                        $vehicleModel = RideNow_Vehicles::find($value);
                        if ($vehicleModel == null) {
                            $fail('Vehicle not found.');
                        } else if ($vehicleModel->user_id != $user->id) {
                            $fail('The selected vehicle does not belong to the authenticated user.');
                        }
                    }
                ],
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if the ride has passengers; if so, prevent updates
        if (!$ride->passengers->isEmpty()) {
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "Forbidden to update this ride since passengers have joined",
            ], 403);
        }

        try {
            $ride->update($request->only([
                'origin_address',
                'destination_address',
                'departure_time',
                'base_cost',
                'vehicle_id',
            ]));

            $ride->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Ride updated successfully',
                'data' => $ride,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in updating ride",
            ], 500);
        }
    }

    //Driver - Cancel Ride
    public function cancelRide($ride_id)
    {
        $user = Auth::user();

        try {
            $ride = RideNow_Rides::findOrFail($ride_id);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Ride not found",
            ], 404);
        }

        if ($ride->user_id != $user->id) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Unauthorized access",
            ], 401);
        }

        try {
            $ride->status = 'canceled';
            $ride->save();

            return response()->json([
                'success' => true,
                'message' => 'Ride canceled successfully',
                'data' => $ride,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in canceling ride",
            ], 500);
        }
    }
}
