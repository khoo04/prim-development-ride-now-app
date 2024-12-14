<?php

namespace App\Http\Controllers\RideNowAPI;

use App\User;
use Exception;
use App\RideNow_Rides;
use App\RideNow_Payments;
use App\RideNow_Vehicles;
use App\RideNow_Vouchers;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\RideNowRideResource;

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
            'data' => new RideNowRideResource($ride),
        ], 200);
    }

    public function listAllAvailableRides(Request $request)
    {
        $perPage = $request->query('per_page', 10); // default to 10 rides per page if not specified
        $page = $request->query('page', 1); // default to page 1 if not specified

        try {
            $rides = RideNow_Rides::with(['driver', 'passengers', 'vehicle'])
            ->where('departure_time', '>', now()) // Filter rides with departure_time after the current time
            ->orderBy('departure_time', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
            
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
            'data' => RideNowRideResource::collection($rides->items()),
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
                'origin_name' => 'required|string',
                'origin_formatted_address' => 'required | string',
                'destination_name' => 'nullable | string',
                'destination_formatted_address' => 'nullable | string',
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

        $originName = $validatedData['origin_name'];
        $originFormattedAddress = $validatedData['origin_formatted_address'];
        $destinationName = $validatedData['destination_name'] ?? null;
        $destinationFormattedAddress = $validatedData['destination_formatted_address'] ?? null;
        $seats = $validatedData['seats'];
        $departureTime = $validatedData['departure_time'];


        try {
            // Start the query for matching origin and destination
            // Start the query for matching origin and destination
            $rides = RideNow_Rides::with(['driver', 'passengers', 'vehicle'])
                ->where(function ($query) use ($originName, $originFormattedAddress) {
                    // Match anywhere in the origin_name or origin_formatted_address
                    $query->where('origin_name', 'LIKE', "%{$originName}%")
                        ->orWhere('origin_formatted_address', 'LIKE', "%{$originFormattedAddress}%");
                });

            // Conditionally add destination filtering
            if ($destinationName && $destinationName !="Any Places" || $destinationFormattedAddress && $destinationFormattedAddress !="Any Places") {
                $rides->where(function ($query) use ($destinationName, $destinationFormattedAddress) {
                    if ($destinationName) {
                        $query->where('destination_name', 'LIKE', "%{$destinationName}%");
                    }
                    if ($destinationFormattedAddress) {
                        $query->orWhere('destination_formatted_address', 'LIKE', "%{$destinationFormattedAddress}%");
                    }
                });
            }

            // Add remaining conditions
            $rides->where('departure_time', '>=', $departureTime) // Filter by departure time
                ->whereHas('vehicle', function ($query) use ($seats) {
                    // Filter by available seats
                    $query->where('seats', '>=', $seats);
                })
                ->where('status', '=', 'confirmed') // Ensure the status is confirmed
                ->orderBy('departure_time', 'asc'); // Optional: Order by departure time

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
            'message' => 'Rides information with ' . $seats . ' seats from ' . $originFormattedAddress . ' to ' . $destinationFormattedAddress . ' on ' . $departureTime,
            'data' => RideNowRideResource::collection($rides),
        ], 200);
    }

    //Show Joined Rides
    public function getJoinedRides()
    {
        $user = Auth::user();

        try {
            $joinedRides = $user->joinedRides()->with(['driver', 'passengers', 'vehicle'])->get();



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
            'data' =>  RideNowRideResource::collection($joinedRides),
        ], 200);
    }

    //TODO: Join Rides - Integrate With Payment Gateway
    public function joinRides(Request $request, $ride_id)
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

        $validator = Validator::make(
            $request->all(),
            [
                'payment_amount' => 'required | numeric',
                'voucher_id' => 'sometimes | nullable | integer',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userPayAmount = $request['payment_amount'];
        $voucherId = $request['voucher_id'];

        $amount_should_pay = $ride->base_cost;

        if($currentPassengersCount > 1){
            $amount_should_pay = $ride->base_cost * (1-0.2);
        }

        //Retrieve voucher
        $voucher = null; // Initialize the voucher variable
        
        if ($voucherId != null){
            try {
                $voucher = RideNow_Vouchers::findOrFail($voucherId);
            } catch (Exception $e) {
                return response()->json([
                    "data" => NULL,
                    "success" => false,
                    "message" => "Voucher not found",
                ], 404);
            }
        }
     
        if ($voucher != null){
            $amount_should_pay = max(0, $amount_should_pay - $voucher->amount);
        }

        if ($amount_should_pay != $userPayAmount){
            return response()->json([
                "data" => [
                    "should" => $amount_should_pay,
                    "user" => $userPayAmount,
                ],
                "success" => false,
                "message" => "User pay amount is different with amount should pay",
            ], 409);
        }

        $fpx_txnAmount = $amount_should_pay;
        $appliedVoucherId = $voucher ? $voucher->id : null;
        //Generate Order No
        // Generate a unique order number (payment ID)
        $fpx_sellerExOrderNo = 'RideNow_TRANS-'. now()->format('YmdHis');

        try{
            RideNow_Payments::create([
                'payment_id' => $fpx_sellerExOrderNo,
                'status' => 'pending', // Default status
                'amount' => $fpx_txnAmount,
                'user_id' => $user->id,
                'ride_id' => $ride->ride_id,
                'voucher_id' => $appliedVoucherId ?? null, // Optional
            ]);
        } catch (Exception $e){
            return response()->json([
                "data" => $e,
                "user" => $user->id,
                "success" => false,
                "message" => "Unable to initiate the payments",
            ], 500);
        }

        $transaction_token  = Crypt::encryptString($fpx_sellerExOrderNo);

        $paymentUrl = route('ride_now.payment', ['transaction_token' => $transaction_token]);

        //Should return url in this view,
        return response()->json([
            "data" => $paymentUrl,
            "success" => true,
            "message" => "Success to get payment links",
        ], 200);
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
                'origin_name' => 'required | string',
                'origin_formatted_address' => 'required | string',
                'origin_latitude' => 'required | numeric',
                'origin_longitude' => 'required | numeric',
                'destination_name' => 'required | string',
                'destination_formatted_address' => 'required | string',
                'destination_latitude' => 'required | numeric',
                'destination_longitude' => 'required | numeric',
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

            // Reload ride with relationships
            $ride->load(['driver', 'passengers', 'vehicle']);

            $ride->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Ride created successfully',
                'data' => new RideNowRideResource($ride),
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
            $rides = $user->createdRides()->with(['driver', 'passengers', 'vehicle'])->get();

            foreach ($rides as $ride) {
                if ($ride->departure_time < now() && $ride->status === 'confirmed') {
                    $ride->status = 'canceled';
                    $ride->save(); // Save the updated status to the database
                }
            }

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
            'data' => RideNowRideResource::collection($rides),
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
                'origin_name' => 'sometimes | string',
                'origin_formatted_address' => 'sometimes | string',
                'origin_latitude' => 'sometimes | numeric',
                'origin_longitude' => 'sometimes | numeric',
                'destination_name' => 'sometimes | string',
                'destination_formatted_address' => 'sometimes | string',
                'destination_latitude' => 'sometimes | numeric',
                'destination_longitude' => 'sometimes | numeric',
                'departure_time' => [
                    'sometimes',
                    'date',
                    function ($attribute, $value, $fail) use ($user, $ride_id) {
                        // Check if there's already a ride with the same departure time for this user
                        $existingRide = RideNow_Rides::where('user_id', $user->id)
                            ->where('departure_time', $value)
                            ->first();

                            if ($existingRide && $existingRide->id !== $ride_id) {
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
                'origin_name',
                'origin_formatted_address',
                'origin_latitude',
                'origin_longitude',
                'destination_name',
                'destination_formatted_address',
                'destination_latitude',
                'destination_longitude',
                'departure_time',
                'base_cost',
                'vehicle_id',
            ]));

            // Reload ride with relationships
            $ride->load(['driver', 'passengers', 'vehicle']);

            $ride->refresh();


            return response()->json([
                'success' => true,
                'message' => 'Ride updated successfully',
                'data' => new RideNowRideResource($ride),
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

    public function completeRide($ride_id){
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
            $joinedPassengersCount = $ride->passengers()->count();

            if ($joinedPassengersCount >= 2) {
                // Find the first user who joined the ride
                $firstJoinedPassenger = $ride->passengers()
                    ->where('joined', true)
                    ->orderBy('created_at', 'asc')
                    ->first();
            
                if ($firstJoinedPassenger) {
                    // Grant a voucher to the first joined user
                    RideNow_Vouchers::create([
                        'user_id' => $firstJoinedPassenger->id,
                        'amount' => 10, // Amount should depends on the total ride fees
                        'redeemed' => false,
                    ]);
                }
            }

            $ride->status = 'completed';
            $ride->save();

            return response()->json([
                'success' => true,
                'message' => 'Ride completed successfully',
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

    //Utils
    private function roundToNearestFiveCents($totalAmount) {
        // Extract the last digit of cents
        $cents = round($totalAmount * 100) % 10;
    
        if (in_array($cents, [1, 2, 6, 7])) {
            // Round down to the nearest 0.05
            return floor($totalAmount * 20) / 20;
        } elseif (in_array($cents, [3, 4, 8, 9])) {
            // Round up to the nearest 0.05
            return ceil($totalAmount * 20) / 20;
        }
        // If already a multiple of 5 cents (0 or 5), return as is
        return number_format($totalAmount, 2, '.', '');
    }
    
}
