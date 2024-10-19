<?php

namespace App\Http\Controllers\OrderSAPI;

use App\Http\Controllers\Controller;
use App\Models\Dish_Type;
use App\Models\Organization;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function getShop(){
        $user = Auth::user();
        $isAdmin = $user->hasRole('OrderS Admin');

        if (!$isAdmin){
            return response()->json(['message' => 'Unauthenticated.'],401);
        }

         // Fetch organizations related to the current user using a join
        $shopList = DB::table('organizations')
        ->join('organization_user', 'organizations.id', '=', 'organization_user.organization_id')
        ->where('organization_user.user_id', $user->id)
        ->where('organization_user.role_id',17) //Indicate only OrderS Organization belongs to this user is shown
        ->where('organizations.seller_id','!=',NULL)
        ->where('organizations.private_key','!=',NULL)
        ->select('organizations.*')  // Select only organization fields
        ->get();

        //TODO: Pagination
        foreach ($shopList as $shop) {
        
            // Check if organization picture exists and generate URL
            if (!is_null($shop->organization_picture)) {
                $shop->organization_picture = url('organization-picture/' . $shop->organization_picture);
            } else {
                $shop->organization_picture = null; // If no picture is set
            }
        }


        // Return the list of organizations (shops) as a JSON response
        return response()->json($shopList);
    }

    public function getDishTypeList(){
        $data = DB::table('dish_type')->get();

        return response()->json($data);
    }

    //Add Dishes to Organization
    public function addDishes(Request $request) {
        $organization_id = intval($request->organization_id);

        $authorized = $this->isAuthorized($organization_id);
        if (!$authorized){
            return response()->json([
                "success" => false,
                "message" => "Unauthenticated.",
            ], 401);
        }

        $dish_name = $request->dish_name;
        $dish_price = doubleval($request->dish_price);
        $dish_type = intval($request->dish_type);

        $file_name = '';

        if (!is_null($request->file('dish_image'))) {
            $extension = $request->file('dish_image')->extension();
            $storagePath = $request->file('dish_image')->move(public_path('dish-image'), 'dish-image-' . time(). '-' . $organization_id . '.' . $extension);
            $file_name = basename($storagePath);
        }else{
            $file_name = NULL;
        }

        try{
            $params = [
                'name' => $dish_name,
                'price' => $dish_price,
                'dish_image' => $file_name,
                'organ_id' => $organization_id,
                'dish_type' => $dish_type
            ];

            $data = Dish::create($params);

            return response()->json([
                "data" => $data,
                "success" => true,
                "message" => "Dish was added successfully",
            ], 201);
            
        }catch (Exception $e){
            return response()->json([
                "success" => false,
                "message" => "Failed in adding dish",
            ], 500);
        }
    }

    public function updateDishes(Request $request) {

        // Validate request data
        $validator = Validator::make($request->all(), [
            'organization_id' => 'required|integer',
            'dish_id' => 'required|integer',
            'dish_name' => 'nullable|string',
            'dish_price' => 'nullable|numeric',
            'dish_type' => 'nullable|integer',
            'dish_image' => 'nullable|image',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        $organization_id = intval($request->organization_id);

        $authorized = $this->isAuthorized($organization_id);
        if (!$authorized){
            return response()->json([
                "organization_id" => $organization_id,
                "success" => false,
                "message" => "Unauthenticated.",
            ], 401);
        }

        $dish_id = intval($request->dish_id);
        $dish = Dish::find($dish_id); // Find the dish by ID
    
        if (!$dish) {
            return response()->json(['message' => 'Dish not found.'], 404);
        }
    
        $updateData = [];
    
        // Check for each field if it's provided in the request and add it to the updateData array
        if ($request->has('dish_name')) {
            $updateData['name'] = $request->dish_name;
        }
    
        if ($request->has('dish_price')) {
            $updateData['price'] = doubleval($request->dish_price);
        }
    
        if ($request->has('dish_type')) {
            $updateData['dish_type'] = intval($request->dish_type);
        }
    
        // Check if a new image is uploaded
        if ($request->hasFile('dish_image')) {
            // Check if there is an existing image
            if ($dish->dish_image) {
                $existingImagePath = public_path('dish-image') . '/' . $dish->dish_image;
                if (file_exists($existingImagePath)) {
                    unlink($existingImagePath); // Delete the existing image file
                }
            }
    
            // Store the new image
            $extension = $request->file('dish_image')->extension();
            $storagePath = $request->file('dish_image')->move(public_path('dish-image'), 'dish-image-' . time() . '-' . $organization_id . '.' . $extension);
            $updateData['dish_image'] = basename($storagePath); // Add image name to updateData
        }
    
        // Update the dish in the database
        if (!empty($updateData)) {
            $updateData['updated_at'] = now(); // Always update the timestamp
            $data = $dish->update($updateData); // Use Eloquent's update method
        }
    
        return response()->json([
            "data" => $data,
            "success" => true,
            "message" => "Dish was updated successfully",
        ], 200);
    }

    public function deleteDishes(Request $request) {
        $organization_id = intval($request->organization_id);
        //Check authorized or not
        $authorized = $this->isAuthorized($organization_id);
        if (!$authorized){
            return response()->json([
                "success" => false,
                "message" => "Unauthenticated.",
            ], 401);
        }

        $dish = Dish::find($request->dish_id);

        if ($dish) {

            if ($dish->dish_image) {
                $existingImagePath = public_path('dish-image') . '/' . $dish->dish_image;
                if (file_exists($existingImagePath)) {
                    unlink($existingImagePath); // Delete the image file from the filesystem
                }
                $dish->dish_image = null; // Set the dish_image column to NULL
            }
            $dish->save();

            $dish->delete(); // Performs soft delete

            return response()->json([
                'success' => true,
                'message' => 'Dish successfully deleted.'
            ]);
        }
        return response()->json([
            "success" => false,
            "message" => "Dish not found.",
        ], 404);
    }

    /**
     * Private Function to Determine the Admin only "Allow" to modify their own resources
     */
    private function isAuthorized($organization_id){
        $user = Auth::user();
        $isAdmin = $user->hasRole('OrderS Admin');
    
        // Check if the user is an admin
        if (!$isAdmin){
            return false;
        }
    
        // Fetch organizations related to the current user using a join
        $shopList = DB::table('organizations')
            ->join('organization_user', 'organizations.id', '=', 'organization_user.organization_id')
            ->where('organization_user.user_id', $user->id)
            ->where('organization_user.role_id', 17) // OrderS Organization role
            ->where('organizations.seller_id', '!=', NULL)
            ->where('organizations.private_key', '!=', NULL)
            ->where('organizations.id', $organization_id) // Check if the user owns this specific organization
            ->select('organizations.*')  // Select only organization fields
            ->first(); // Using first() to get a single result, since we expect only one
    
        // If no organization is found, return unauthorized response
        if (is_null($shopList)) {
            return false;
        }
    
        // If the organization exists, the user is authorized
        return true;
    }    
}
