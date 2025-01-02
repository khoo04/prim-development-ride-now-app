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
use Illuminate\Support\Carbon;
use App\Events\RideStatusChanged;
use App\RideNow_PaymentAllocation;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use App\Events\NotifyRidePassengersStarted;
use App\Http\Resources\RideNowRideResource;
use App\Events\NotifyRidePassengersCompleted;

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
        $user = Auth::user();

        $perPage = $request->query('per_page', 10); // default to 10 rides per page if not specified
        $page = $request->query('page', 1); // default to page 1 if not specified

        try {
            $ridesQuery = RideNow_Rides::with(['driver', 'passengers', 'vehicle'])
                ->where('status', '=', 'confirmed')
                ->where('departure_time', '>', now()) // Filter rides with departure_time after the current time
                ->orderBy('departure_time', 'asc');

            // Get all rides and filter them using collection methods
            $rides = $ridesQuery->get()->filter(function ($ride) {
                return $ride->available_seats > 0; // Use the accessor here
            });

            // Paginate the filtered collection manually
            $paginatedRides = $rides->forPage($page, $perPage);
        } catch (Exception $e) {
            return response()->json([
                "e" => $e->getMessage(),
                "data" => NULL,
                "success" => false,
                "message" => "An error occurred while retrieving available rides.",
            ], 500);
        }

        $rides =  $paginatedRides->map(function ($ride) use ($user) {
            return new RideNowRideResource($ride, $user->id);
        });

        return response()->json([
            'success' => true,
            'message' => 'Successfully retrieved page ' . $page . ' with up to ' . $perPage . ' rides per page.',
            'data' => $rides,
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
            $rides = RideNow_Rides::with(['driver', 'passengers', 'vehicle'])
                ->where(function ($query) use ($originName, $originFormattedAddress) {
                    // Match anywhere in the origin_name or origin_formatted_address
                    $query->where('origin_name', 'LIKE', "%{$originName}%")
                        ->orWhere('origin_formatted_address', 'LIKE', "%{$originFormattedAddress}%");
                });

            // Conditionally add destination filtering
            if ($destinationName && $destinationName != "Any Places" || $destinationFormattedAddress && $destinationFormattedAddress != "Any Places") {
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
                // ->filter(function ($ride) {
                //     return $ride->available_seats > 0; // Use the accessor here
                // }) 
                ->orderBy('departure_time', 'asc'); // Optional: Order by departure time

            // Execute the query
            $rides = $rides->get()->filter(function ($ride) use ($seats) {
                return $ride->available_seats >= $seats; // Use the accessor for available seats
            });
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in retrieving rides",
            ], 500);
        }

        $user = Auth::user();

        $rides = $rides->map(function ($ride) use ($user) {
            return new RideNowRideResource($ride, $user->id);
        });


        // Return the results
        return response()->json([
            'success' => true,
            'message' => 'Rides information with ' . $seats . ' seats from ' . $originFormattedAddress . ' to ' . $destinationFormattedAddress . ' on ' . $departureTime,
            'data' => $rides,
        ], 200);
    }

    //Show Joined Rides
    public function getJoinedRides()
    {
        $user = Auth::user();

        try {
            $joinedRides = $user->joinedRides()->with(['driver', 'passengers', 'vehicle', 'ratings'])->distinct('ride_id')->get();

            $joinedRides = $joinedRides->map(function ($ride) use ($user) {
                return new RideNowRideResource($ride, $user->id); // Pass currentUserId to the resource
            });
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
            'data' =>   $joinedRides,
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

        if (Carbon::now()->greaterThan($ride->departure_time)) {
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "The ride is expired",
            ], 403); // 403 Forbidden
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
                'voucher_id' => 'sometimes | nullable | string',
                'required_seats' => 'required | numeric',
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
        $voucherId = $request['voucher_id'] ?? null;
        $requiredSeats = $request['required_seats'];

        if ($requiredSeats <= 0) {
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "Invalid number of required seats",
            ], 400); // 400 Bad Request
        }

        $currentAvailableSeats = $vehicleSeats - $currentPassengersCount;
        if ($requiredSeats > $currentAvailableSeats) {
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "The required seats is exceed the current vehicle available seats",
            ], 403); // 403 Forbidden
        }

        //Initialize
        $subtotal = 0;

        $discountedCost = $this->roundToNearestFiveCents($ride->base_cost * 0.8);

        if ($currentPassengersCount > 1) {
            // Case: Ride already has one or more passengers
            $subtotal = $discountedCost * $requiredSeats;
        } else {
            // Case: Ride has no passengers
            $subtotal = $ride->base_cost + $discountedCost * ($requiredSeats - 1);
        }

        //Retrieve voucher
        $voucher = null; // Initialize the voucher variable

        if ($voucherId != null) {
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

        if ($voucher != null) {
            $subtotal = max(0, $subtotal - $voucher->amount);
        }


        //TODO: Read it from database dynamically
        // Step 4: Apply platform charge (5%)
        $platformCharge = $this->roundToNearestFiveCents($subtotal * 0.01);
        $amount_should_pay = $subtotal + $platformCharge;

        // Step 5: Add bank service charge
        $bankServiceCharge = 0.70;
        $amount_should_pay += $bankServiceCharge;

        // Step 6: Round to nearest 5 cents
        $amount_should_pay = $this->roundToNearestFiveCents($amount_should_pay);

        if ($amount_should_pay != $userPayAmount) {
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
        $fpx_sellerExOrderNo = 'RideNow_TRANS-' . now()->format('YmdHis');

        try {
            RideNow_Payments::create([
                'payment_id' => $fpx_sellerExOrderNo,
                'status' => 'pending', // Default status
                'required_seats' => $requiredSeats,
                'amount' => $fpx_txnAmount,
                'user_id' => $user->id,
                'ride_id' => $ride->ride_id,
                'voucher_id' => $appliedVoucherId ?? null, // Optional
            ]);
        } catch (Exception $e) {
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

    public function leaveRide($ride_id)
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

        if ($ride->status != "confirmed"){
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Cannot leave the ride once the ride is started/completed/canceled",
            ], 404);
        }

        // Check if user is passengers or not
        if (!($ride->passengers()->where('user_id', $user->id)->exists())) {
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "User is not the passengers for this ride",
            ], 409); // 409 Conflict
        }

        try {
            //Retrieve payments record associate with this ride
            $payment = $ride->payments()
                ->where('payment_allocation_id', '=', NULL)
                ->where('status', '=', 'completed')
                ->where('user_id', '=', $user->id)
                ->first();

            //Refund the payment to user
            if ($payment) {
                $paymentAllocation = RideNow_PaymentAllocation::create([
                    'status' => 'pending',
                    'description' => 'Refund due to user leaves ride',
                    'total_amount' =>  $payment->amount,
                    'ride_id' => $payment->ride_id,
                    'user_id' => $payment->user_id,
                ]);

                $payment->payment_allocation_id = $paymentAllocation->payment_allocation_id;
                $payment->save();

                $ride->passengers()
                    ->wherePivot('user_id', '=', $payment->user->id)
                    ->detach();

                $ride->refresh();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment record is not found',
                    'data' => NULL,
                ], 500);
            }

            $ride->save();

            $ride->load(['driver', 'passengers', 'vehicle']);

            event(new RideStatusChanged($ride));

            return response()->json([
                'success' => true,
                'message' => 'Leave ride successfully',
                'data' => new RideNowRideResource($ride),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in canceling ride",
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
                //If the ride is not started after 30 minutes the departure time, cancel it automatically
                if (
                    Carbon::parse($ride->departure_time)->lessThan(Carbon::now()->addMinutes(30)) &&
                    $ride->status === 'confirmed'
                ) {
                    $this->cancelRide($ride->ride_id);
                }
            }
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in getting created rides",
            ], 500);
        }

        $rides = $rides->map(function ($ride) use ($user) {
            return new RideNowRideResource($ride, $user->id); // Pass currentUserId to the resource
        });


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

            event(new RideStatusChanged($ride));

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

        if ($ride->status != 'confirmed') {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Ride is not in confirmed status",
            ], 403);
        }


        try {
            //Retrieve payments record associate with this ride
            $payments = $ride->payments()
                ->where('payment_allocation_id', '=', NULL)
                ->where('status', '=', 'completed')
                ->get();

            //Refund the payment to user
            foreach ($payments as $payment) {
                $paymentAllocation = RideNow_PaymentAllocation::create([
                    'status' => 'pending',
                    'description' => 'Refund due to driver cancel ride',
                    'total_amount' =>  $payment->amount,
                    'ride_id' => $payment->ride_id,
                    'user_id' => $payment->user_id,
                ]);

                $payment->payment_allocation_id = $paymentAllocation->payment_allocation_id;
                $payment->save();
            }


            $ride->status = 'canceled';
            $ride->save();

            $ride->load(['driver', 'passengers', 'vehicle']);

            event(new RideStatusChanged($ride));

            return response()->json([
                'success' => true,
                'message' => 'Ride canceled successfully',
                'data' => new RideNowRideResource($ride),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in canceling ride",
            ], 500);
        }
    }

    public function startRide($ride_id)
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


        // if ($ride->status != 'confirmed'){
        //     return response()->json([
        //         "data" => NULL,
        //         "success" => false,
        //         "message" => "Ride is already started / completed / canceled",
        //     ], 403);
        // }

        try {
            $ride->status = 'started';
            $ride->save();

            $ride->load(['driver', 'passengers', 'vehicle', 'ratings']);


            $distinctPassengers = $ride->passengers()->distinct('id')->get();

            event(new RideStatusChanged($ride));

            foreach ($distinctPassengers as $passenger) {
                event(new NotifyRidePassengersStarted($passenger, $ride));
            }

            return response()->json([
                'success' => true,
                'message' => 'Ride started successfully',
                'data' => new RideNowRideResource($ride),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => $e,
                "success" => false,
                "message" => "Exception occurred in starting ride",
            ], 500);
        }
    }

    public function completeRide($ride_id)
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

        if ($ride->status != 'started') {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Ride is already completed or has not started.",
            ], 403);
        }


        try {
            $payments = $ride->payments()
                ->where('payment_allocation_id', '=', NULL)
                ->where('status', '=', 'completed')
                ->get();

            // Calculate the cumulative payment amount
            $cumulativePaymentAmount = $payments->reduce(function ($total, $payment) {
                // Reverse the charges to calculate the original amount
                //TODO: Payment charge read from database dynamically
                $amountBeforeBankCharge = ($payment->amount - 0.70); // Remove bank service charge
                $originalAmount = $amountBeforeBankCharge / 1.01;   // Remove 1% platform service charge

                // Add the original amount to the total
                return $total + $originalAmount;
            }, 0);

            $joinedPassengersCount = $ride->passengers()->count();

            $driverEarning = $this->roundToNearestFiveCents($cumulativePaymentAmount);

            if ($joinedPassengersCount >= 2) {

                // Find the first user who joined the ride
                $firstJoinedPassenger = $ride->passengers()
                    ->orderBy('created_at', 'asc')
                    ->first();

                $voucherValue =  $this->roundToNearestFiveCents($ride->base_cost * 0.3);


                if ($firstJoinedPassenger) {
                    // Grant a voucher to the first joined user
                    RideNow_Vouchers::create([
                        'user_id' => $firstJoinedPassenger->id,
                        'amount' =>  $voucherValue,
                        'redeemed' => false,
                    ]);

                    $driverEarning = $this->roundToNearestFiveCents($driverEarning - $voucherValue);
                }
            }

            $paymentAllocation = RideNow_PaymentAllocation::create([
                'status' => 'pending',
                'description' => 'Ride complete income',
                'total_amount' => $driverEarning,
                'ride_id' => $ride->ride_id,
                'user_id' => $ride->driver->id,
            ]);

            foreach ($payments as $payment) {
                $payment->payment_allocation_id = $paymentAllocation->payment_allocation_id;
                $payment->save();
            }

            $ride->status = 'completed';
            $ride->save();

            $ride->load(['driver', 'passengers', 'vehicle']);

            event(new RideStatusChanged($ride));

            $distinctPassengers = $ride->passengers()->distinct('id')->get();

            foreach ($distinctPassengers as $passenger) {
                event(new NotifyRidePassengersCompleted($passenger, $ride));
            }

            return response()->json([
                'success' => true,
                'message' => 'Ride completed successfully',
                'data' => new RideNowRideResource($ride),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => $e,
                "success" => false,
                "message" => "Exception occurred in completing ride",
            ], 500);
        }
    }


    public function rateRide(Request $request, $ride_id)
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

        if ($ride->status != 'completed') {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "This ride cannot be rated because it is not completed.",
            ], 401);
        }

        if (!$ride->passengers->contains('id', $user->id)) {
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "You are not a passenger on this ride.",
            ], 403); // Use 403 Forbidden for unauthorized actions
        }

        $validator = Validator::make(
            $request->all(),
            [
                'rating' => 'required|numeric|min:1|max:5',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $existingRating = DB::table('ride_now__rides_rating')
            ->where('ride_id', $ride->ride_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingRating) {
            // User has already rated the ride
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "You have already rated this ride.",
            ], 400);
        }


        $ride->ratings()->attach([
            $user->id => [
                'rating' => $request['rating'],
                'created_at' => Carbon::now(),  // or you can use `now()` helper as well
                'updated_at' => Carbon::now(),
            ],
        ]);

        //Retrieve driver
        $driver = $ride->driver;
        // Update the driver's overall rating
        if ($driver && $driver->userDetails) {
            $userDetails = $driver->userDetails;

            // Fetch all ratings for rides created by the driver
            $totalRatings = DB::table('ride_now__rides_rating')
                ->whereIn('ride_id', $driver->createdRides()->pluck('ride_id'))
                ->count();

            // Ensure there is at least one rating to avoid division by zero
            if ($totalRatings > 0) {
                $existingRating = $userDetails->ratings ?? 0;

                // Calculate the new average rating
                $newAverageRating = (($existingRating * ($totalRatings - 1)) + $request['rating']) / $totalRatings;

                // Update the user's details with the new average rating
                $userDetails->update(['ratings' => $newAverageRating]);
            }
        }
        return response()->json([
            "data" => null,
            "success" => true,
            "message" => "Ride rated successfully",
        ], 200); // 
    }

    //Utils
    private function roundToNearestFiveCents($totalAmount)
    {
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
        return (float) number_format($totalAmount, 2, '.', '');
    }
}
