<?php

namespace App\Http\Controllers\RideNowAPI;

use Exception;
use App\RideNow_Payments;
use App\RideNow_UserDetails;
use Illuminate\Http\Request;
use App\RideNow_PaymentAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Http\Resources\RideNowRideResource;
use App\Http\Resources\RideNowUserResource;
use Illuminate\Contracts\Encryption\DecryptException;

class PaymentController extends Controller
{

    public function initPayment(Request $request, $transaction_token)
    {

        try {
            $fpx_sellerExOrderNo = Crypt::decryptString($transaction_token);

            $payment = RideNow_Payments::findOrFail($fpx_sellerExOrderNo);

            if ($payment->status == 'completed') {
                return view('ride_now.payment_error', [
                    'message' => 'Payment is completed',
                ]);
            }
        } catch (DecryptException $e) {
            return view('ride_now.payment_error', [
                'message' => 'Invalid transaction token. Please try again or contact support.',
            ]);
        } catch (Exception $e) {
            return view('ride_now.payment_error', [
                'message' => 'An unexpected error occurred. Please try again later.',
            ]);
        }

        $fpx_buyerEmail = $payment->user->email;
        $fpx_buyerName = $payment->user->name;
        //TODO: Delete in production
        $private_key = env('FPX_PRIVATE_KEY',"3c0e8bbc-88a2-4037-aa72-c6581a720670");
        $fpx_txnAmount = $payment->amount;

        // dd(env('FPX_PRIVATE_KEY'));

        return view('ride_now.payment', compact(
            'fpx_buyerEmail',
            'fpx_buyerName',
            'private_key',
            'fpx_txnAmount',
            'fpx_sellerExOrderNo'
        ));
    }

    public function showTestCallBack()
    {
        return view('ride_now.test_callback');
    }

    public function paymentCallBack(Request $request)
    {
        $status = $request->Status;
        $paymentId = $request->Fpx_SellerOrderNo;
        $transactionAmount = $request->TransactionAmount;

        try {
            $payment = RideNow_Payments::findOrFail($paymentId);
        } catch (Exception $e) {
            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "Payment not found",
            ],  404);
        }

        if ($status == "Success") {

            if ($payment->amount != $transactionAmount) {
                return response()->json([
                    "data" => null,
                    "success" => false,
                    "message" => "Invalid payment amount",
                ],  400);
            }

            try {
                if ($payment->voucher != null) {
                    $voucher = $payment->voucher;
                    $voucher->redeemed = true;
                    $voucher->save();
                }

                $payment->status = 'completed';
                $payment->save();

                $ride = $payment->ride;

                // Check if user cannot join their own created ride to avoid duplicates
                if ($ride->driver->id == $payment->user->id) {
                    $paymentAllocation = RideNow_PaymentAllocation::create([
                        'status' => 'pending',
                        'description' => 'Ride completed income',
                        'total_amount' => $payment->amount,
                        'ride_id' => $ride->ride_id,
                        'user_id' => $payment->user->id,
                    ]);

                    $payment->payment_allocation_id = $paymentAllocation->payment_allocation_id;
                    $payment->save();
                    
                    return response()->json([
                        "data" => NULL,
                        "success" => false,
                        "message" => "User cannot join their own ride. Contact admin for refund",
                    ], 409); // 409 Conflict
                }

                // Check if user is already joined to avoid duplicates
                if ($ride->passengers()->where('user_id', $payment->user->id)->exists()) {
                    $paymentAllocation = RideNow_PaymentAllocation::create([
                        'status' => 'pending',
                        'description' => 'Ride completed income',
                        'total_amount' => $payment->amount,
                        'ride_id' => $ride->ride_id,
                        'user_id' => $payment->user->id,
                    ]);

                    $payment->payment_allocation_id =  $paymentAllocation->payment_allocation_id;
                    $payment->save();
                

                    return response()->json([
                        "data" => "refund",
                        "success" => false,
                        "message" => "User is already joined in this ride. Contact admin for refund",
                    ], 409); // 409 Conflict
                }

                //Retrieve vehicle seat count
                $vehicleSeats = $ride->vehicle->seats;

                $currentPassengersCount = $ride->passengers()->count();

                $requiredSeats = $payment->required_seats;

                $currentAvailableSeats = $vehicleSeats - $currentPassengersCount;
                if ($requiredSeats > $currentAvailableSeats) {
                    
                    $paymentAllocation = RideNow_PaymentAllocation::create([
                        'status' => 'pending',
                        'description' => 'Ride completed income',
                        'total_amount' => $payment->amount,
                        'ride_id' => $ride->ride_id,
                        'user_id' => $payment->user->id,
                    ]);

                    $payment->payment_allocation_id =  $paymentAllocation->payment_allocation_id;
                    $payment->save();

                    return response()->json([
                        "data" => $payment,
                        "success" => false,
                        "message" => "The required seats is exceed the current vehicle available seats",
                    ], 403); // 403 Forbidden
                }
        

                // Loop through the required seats and attach the user for each seat
                for ($i = 0; $i < $requiredSeats; $i++) {
                    $ride->passengers()->attach($payment->user->id);
                }

                $ride->refresh();

                // Reload ride with relationships
                $ride->load(['driver', 'passengers', 'vehicle']);

                return response()->json([
                    'success' => true,
                    'message' => 'User joined the ride successfully',
                    'data' => new RideNowRideResource($ride),
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    "data" => $e,
                    "success" => false,
                    "message" => "Exception occurred while joining the ride",
                ], 500);
            }
        } else {
            $payment->status = 'failed';
            $payment->save();

            return response()->json([
                "data" => null,
                "success" => false,
                "message" => "Payment failed",
            ], 200);
        }
    }
}
