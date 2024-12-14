<?php

namespace App\Http\Controllers\RideNowAPI;

use Exception;
use App\RideNow_Payments;
use App\RideNow_UserDetails;
use Illuminate\Http\Request;
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
        $private_key = env('FPX_PRIVATE_KEY');
        $fpx_txnAmount = $payment->amount;

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
                    return response()->json([
                        "data" => null,
                        "success" => false,
                        "message" => "User cannot join their own ride",
                    ], 409); // 409 Conflict
                }

                // Check if user is already joined to avoid duplicates
                if ($ride->passengers()->where('user_id', $payment->user->id)->exists()) {
                    return response()->json([
                        "data" => null,
                        "success" => false,
                        "message" => "User is already joined in this ride",
                    ], 409); // 409 Conflict
                }


                $ride->passengers()->attach($payment->user->id);

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
