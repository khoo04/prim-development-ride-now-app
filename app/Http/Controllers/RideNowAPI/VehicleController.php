<?php

namespace App\Http\Controllers\RideNowAPI;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\RideNow_Vehicle_Types;
use App\RideNow_Vehicles;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{

    /**
     * Retrieve AlL Available Vehicle Type
     * @return JsonResponse
     */
    public function listVehicleType()
    {
        try {
            $vehicle_type = RideNow_Vehicle_Types::get();
            return response()->json([
                "data" => $vehicle_type,
                "success" => true,
                "message" => "Vehicle type retrieved successfully",
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in retrieving in vehicle type",
            ], 500);
        }
    }

    public function createVehicle(Request $request)
    {

        $user = Auth::user();

        $validator = Validator::make(
            $request->all(),
            [
                'vehicle_registration_number' => [
                    'required',
                    'string',
                    'regex:/^[A-Za-z]{1,3}\d{1,4}[A-Za-z]{0,1}$/',
                    'unique:ride_now__vehicles,vehicle_registration_number'
                ],
                'manufacturer' => 'required | string',
                'model' => 'required | string',
                'seats' => 'required | string',
                'average_fuel_consumptions' => 'required| numeric',
                'vehicle_type_id' => 'required | integer',
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
            $vehicleData = $request->all();
            $vehicleData['user_id'] = $user->id;
            $vehicle = RideNow_Vehicles::create($vehicleData);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle created successfully',
                'data' => $vehicle,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in creating vehicle",
            ], 500);
        }
    }

    public function updateVehicle(Request $request, $vehicle_id)
    {
        try {
            $vehicle = RideNow_Vehicles::findOrFail($vehicle_id);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Vehicle not found",
            ], 404);
        }

        $user = Auth::user();

        if ($vehicle->user_id != $user->id) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Unauthorized access.",
            ], 401);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'car_registration_number' => [
                    'sometimes',
                    'string',
                    'regex:/^[A-Za-z]{1,3}\d{1,4}[A-Za-z]{0,1}$/'
                ],
                'manufacturer' => 'sometimes|string',
                'model' => 'sometimes|string',
                'seats' => 'sometimes|integer',
                'average_fuel_consumptions' => 'sometimes|numeric',
                'vehicle_type_id' => 'sometimes|integer',
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

        try {
            $vehicle->update($request->only([
                'car_registration_number',
                'manufacturer',
                'model',
                'seats',
                'average_fuel_consumptions',
                'vehicle_type_id'
            ]));

            $vehicle->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully',
                'data' => $vehicle,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in updating vehicle",
            ], 500);
        }
    }

    public function deleteVehicle($vehicle_id)
    {
        try {
            $vehicle = RideNow_Vehicles::findOrFail($vehicle_id);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Vehicle not found",
            ], 404);
        }

        $user = Auth::user();

        if ($vehicle->user_id != $user->id) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Unauthorized access.",
            ], 401);
        }

        try {
            $vehicle->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle deleted successfully',
                'data' => NULL,
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in deleting vehicle",
            ], 500);
        }
    }

    public function listVehicle()
    {
        //Retrieve users context
        $user = Auth::user();

        try {
            $vehicles = $user->vehicles;
            return response()->json([
                "data" => $vehicles,
                "success" => true,
                "message" => "Vehicle type retrieved successfully",
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "data" => NULL,
                "success" => false,
                "message" => "Exception occurred in retrieving in vehicles",
            ], 500);
        }
    }
}
