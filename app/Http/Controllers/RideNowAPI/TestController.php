<?php

namespace App\Http\Controllers\RideNowAPI;

use App\Events\RideStatusChanged;
use App\Http\Controllers\Controller;
use App\RideNow_Rides;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function getRoles()
    {
        //event(new RideStatusChanged("hELLO"));

        return response()->json(["success" => true]);
    }

    public function testMessage(Request $request)
    {
       $ride = RideNow_Rides::find($request->id);
       $status = $request->status;

       $ride->status=$status;
       $ride->save();

        // Trigger the event
        event(new RideStatusChanged($ride));
       // event(new PaymentStatusChanged(null, $payment, $payment->user->id, false, "Payment failed"));

        return response()->json("ok");
    }
}
