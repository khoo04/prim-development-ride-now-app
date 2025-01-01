<?php

namespace App\Http\Controllers\RideNowAPI;

use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\RideNowUserResource;

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

    public function retrieveUserBalance()
    {
        $user = Auth::user();

        try {
            $allocations = DB::table('ride_now__payment_allocations')
                ->where('user_id', $user->id)
                ->get();

            $earnings = [];
            $refunds = [];
            $credited = 0; //Credited Earnings + Credited Refunds
            $uncredited = 0; //Uncredited Earnings + Uncredited Refunds

            $creditedEarnings = 0;
            $uncreditedEarnings = 0;

            // Initialize earnings by month (1-12) with default values
            $earningsByMonthPerYear = array_fill(1, 12, ['credited' => 0, 'uncredited' => 0]);
            $currentYear = (int) date('Y'); // Get the current year dynamically
            $currentMonth = (int) date('n');

            foreach ($allocations as $allocation) {
                $status = strtolower($allocation->status) === 'completed' ? 'credited' : 'uncredited';
                $record = [
                    'created_date_time' => $allocation->created_at,
                    'status' => $status,
                    'amount' => (float) $allocation->total_amount,
                ];

                $allocationMonth = (int) date('n', strtotime($allocation->created_at));
                $allocationYear = (int) date('Y', strtotime($allocation->created_at));


                // Process earnings or refunds based on description
                if (strpos(strtolower($allocation->description), 'income') !== false) {
                    $earnings[] = $record;

                    if ($record['status'] === 'credited') {
                        $credited += $record['amount'];
                        $creditedEarnings += round($record['amount'], 2);
                        // Process only allocations for the current year
                        if ($allocationYear === $currentYear) {
                            $earningsByMonthPerYear[$allocationMonth]['credited'] += round($record['amount'], 2);
                        }
                    } else {
                        $uncredited += $record['amount'];
                        $uncreditedEarnings += $record['amount'];

                        // Process only allocations for the current year
                        if ($allocationYear === $currentYear) {
                            $earningsByMonthPerYear[$allocationMonth]['uncredited'] += $record['amount'];
                        }
                    }
                } elseif (strpos(strtolower($allocation->description), 'refund') !== false) {
                    $refunds[] = $record;

                    if ($record['status'] === 'credited') {
                        $credited += $record['amount'];
                    } else {
                        $uncredited += $record['amount'];
                    }
                }
            }


            // Prepare graph data
            $earningsGraphData = [];
            foreach ($earningsByMonthPerYear as $month => $data) {
                $earningsGraphData[] = [
                    'months' => $month,
                    'graph_data' => [
                        'credited' => round($data['credited'], 2),  // Round credited value to 2 decimal places
                        'uncredited' => round($data['uncredited'], 2),  // Round uncredited value to 2 decimal places
                    ],
                ];
            }

            $totalBalance = $credited + $uncredited;
            $totalEarningsBalance = $creditedEarnings + $uncreditedEarnings;

            $currentMonthTotalEarnings = round($earningsByMonthPerYear[$currentMonth]['credited'], 2) + round($earningsByMonthPerYear[$currentMonth]['uncredited'], 2);


            return response()->json([
                "success" => true,
                "message" => "User balance retrieved successfully",
                "data" => [
                    'total_balance' => round($totalBalance, 2),
                    'total_uncredited_balance' => round($uncredited, 2),
                    'total_credited_balance' => round($credited, 2),
                    'total_earnings_balance' => round($totalEarningsBalance, 2),
                    'current_month_total_earnings' => [
                        'year_month' => now()->format('Y-m-d'), //Current Year Month
                        'earnings' => $currentMonthTotalEarnings,
                    ],
                    'total_earnings_graph_data' => $earningsGraphData,
                    'earnings_records' => $earnings,
                    'refunds_records' => $refunds,
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "success" => true,
                "message" => "User balance retrieved successfully",
                "data" => $e->getMessage(),
            ], 500);
        }
    }
}
