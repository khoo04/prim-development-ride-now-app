<?php

namespace App\Http\Controllers\RideNowAPI;

use App\Models\Organization;
use Illuminate\Http\Request;
use App\RideNow_Vehicle_Types;
use App\Http\Controllers\Controller;

class PublicController extends Controller
{
    /**
     * List Available Shops (Organizations)
     */
    public function index(){
        $data = Organization::where('type_org',12)
            ->where('seller_id','!=',NULL)
            ->where('private_key','!=',NULL)
            ->get();

        //TODO: Pagination
        // Iterate through organizations and unset sensitive data
        foreach ($data as $orgData) {
            unset($orgData['private_key']);
            unset($orgData['remember_token']);
            unset($orgData['type_org']);
            unset($orgData['fixed_charges']);
            unset($orgData['email']);
            
            // Check if organization picture exists and generate URL
            if (!is_null($orgData->organization_picture)) {
                $orgData->organization_picture = url('organization-picture/' . $orgData->organization_picture);
            } else {
                $orgData->organization_picture = null; // If no picture is set
            }
        }
        return response()->json($data);  
    }

    public function getMenu(Request $request){
        $restaurantId = $request->query('restaurant_id');
        $organization = Organization::where('id',$restaurantId)
        ->first();

        // Check if the organization exists
        if (!$organization) {
            return response()->json(['errors' => 'Organization not found'], 404);
        }

        $menu = $organization->dishes;

        foreach($menu as $dish){
            if (!is_null($dish->dish_image)){
                $dish->dish_image = url('dish-image/'. $dish->dish_image);
            } else{
                $dish->dish_image = null;
            }
        }

        return response()->json($menu);
    }

  

}
