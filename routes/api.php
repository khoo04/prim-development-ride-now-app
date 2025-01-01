<?php

use App\RideNow_Vehicles;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('fpxIndex', 'PayController@fpxIndex')->name('api.fpxIndex');
Route::post('directpayIndex', 'DirectpayController@directpayIndex')->name('api.directpayIndex');
Route::get('adminHandle', 'DirectPayController@handle');
Route::get('devtrans', 'PayController@devtrans')->name('devtrans');

// mobile api
Route::get('donateFromMobile', 'PayController@donateFromMobile');

Route::group(['prefix' => 'mobile'], function () {
    Route::get('/getAllDonation', 'MobileApiController@getAllDonation');
    Route::get('/getAllDonationType', 'MobileApiController@getAllDonationType');
    Route::get('/getAllDonationQuantity', 'MobileApiController@getAllDonationQuantity');
    Route::get('/getAllDonationTypeQuantity', 'MobileApiController@getAllDonationTypeQuantity');
    Route::get('/getAllStatistic', 'MobileApiController@getAllStatistic');
    Route::get('/getallmytransactionhistory', 'MobileApiController@getallmytransactionhistory');
    Route::get('/getlatesttransaction', 'MobileApiController@getlatesttransaction');
    Route::get('/gettransactionbymonth', 'MobileApiController@gettransactionbymonth');
    Route::get('/gettransactionbyyear', 'MobileApiController@gettransactionbyyear');
    Route::get('/donationnumberbyorganization', 'MobileApiController@donationnumberbyorganization');
    Route::get('/getdonationbycategory', 'MobileApiController@getdonationbycategory');
    
    Route::post('/login', 'MobileApiController@login');
    Route::post('/updateProfile', 'MobileApiController@updateProfile');
    
    //route for mobile dish
    Route::group(['prefix' => 'dish'], function (){
        Route::get('/getOrganizationWithDish', 'DishController@getOrganizationWithDish');
        Route::get('/getAllDishes', 'DishController@getAllDishes');
        Route::get('/getAllAvailableDates', 'DishController@getAllAvailableDates');
        Route::post('/storeDishAvailable', 'DishController@storeDishAvailable');
    });

    //route for mobile order
    Route::group(['prefix' => 'order'], function (){
        Route::get('/getAllOrderById/{id}', 'OrderController@getAllOrderById');
        Route::get('/getAllOrderByOrganId/{id}', 'OrderController@getAllOrderByOrganId');

        Route::post('/orderTransaction', 'OrderController@orderTransaction');
        Route::post('/updateStatusToDelivering', 'OrderController@updateStatusToDelivering');
        Route::post('/updateStatusToDelivered', 'OrderController@updateStatusToDelivered');
    });

    //route for mobile order
    Route::group(['prefix' => 'yuran'], function (){
        Route::post('login', 'MobileAPI\YuranController@login');
        Route::get('getOrganizationByUserId', 'MobileAPI\YuranController@getOrganizationByUserId');
        Route::post('getReceiptByOid', 'MobileAPI\YuranController@getReceiptByOid');
        Route::post('getUserInfo', 'MobileAPI\YuranController@getUserInfo');

        Route::post('getYuran', 'MobileAPI\YuranController@getYuranByParentIdAndOrganId');
        Route::post('yuranTransaction', 'MobileAPI\YuranController@pay');
    });
});

Route::group(['prefix' => 'schedule' , 'namespace' => 'Schedule'],function () {
    Route::post('login', 'ScheduleApiController@login');
    Route::get('getTimeOff', 'ScheduleApiController@getTimeOff');
    Route::get('sendNotification/{id}/{title}/{message}', 'ScheduleApiController@sendNotification');
    Route::get('sendFirebaseNotification/{id}/{title}/{message}', 'ScheduleApiController@sendNotification3');
    Route::get('isNoti/{id}', 'ScheduleApiController@isNoti');
    Route::get('getSchedule/{id}', 'ScheduleApiController@getSchedule');
    Route::get('getTeacherInfo/{id}', 'ScheduleApiController@getTeacherInfo');
    Route::any('submitLeave', 'ScheduleApiController@submitLeave');
    
    Route::get('getLeaveType', 'ScheduleApiController@getLeaveType');
    Route::post('getPendingRelief','ScheduleApiController@getPendingRelief');

    Route::post('submitReliefResponse','ScheduleApiController@submitReliefResponse');
    Route::get('getHistory/{id}','ScheduleApiController@getHistory');
    Route::get('getHistoryByRange/{id}/{year}/{month}','ScheduleApiController@getHistoryByRange');


    
});

//route for mobile orderS
Route::group(['prefix' => 'OrderS'], function (){
    Route::get('test', 'OrderSController@testData');
    Route::post('login', 'OrderSController@login');
    Route::post('isUserOrderSAdmin', 'OrderSController@isUserOrderSAdmin');
    Route::post('logout', 'OrderSController@logout');
    Route::post('updateUser', 'OrderSController@updateUser');
    Route::post('updateOrganization', 'OrderSController@updateOrganization');
    Route::get('randomDishes', 'OrderSController@randomDishes');
    Route::get('listDishes', 'OrderSController@listDishes');
    Route::get('listShops', 'OrderSController@listShops');
    Route::post('listDishesByShop', 'OrderSController@listDishesByShop');
    Route::post('listDishesByShopAdmin', 'OrderSController@listDishesByShopAdmin');
    Route::post('listDishAvailable', 'OrderSController@listDishAvailable');
    Route::post('listOrderAvailable', 'OrderSController@listOrderAvailable');
    Route::post('getOrderCart', 'OrderSController@getOrderCart');
    Route::post('createOrderCart', 'OrderSController@createOrderCart');
    Route::post('getOrderAvailableDish', 'OrderSController@getOrderAvailableDish');
    Route::get('getDishType', 'OrderSController@getDishType');
    Route::post('addDishes', 'OrderSController@addDishes');
    Route::post('updateDishes', 'OrderSController@updateDishes');
    Route::post('deleteDishes', 'OrderSController@deleteDishes');
    Route::post('addOrderAvailable', 'OrderSController@addOrderAvailable');
    Route::post('updateOrderAvailable', 'OrderSController@updateOrderAvailable');
    Route::post('deleteOrderAvailable', 'OrderSController@deleteOrderAvailable');
    Route::post('listOrderAvailableAdmin', 'OrderSController@listOrderAvailableAdmin');
    Route::post('listOADAdmin', 'OrderSController@listOADAdmin');
    Route::post('updateOADAdmin', 'OrderSController@updateOADAdmin');
    Route::post('getUsers', 'OrderSController@getUsers');
    Route::post('getReport', 'OrderSController@getReport');
});

Route::group(['prefix' => 'derma', 'namespace' => 'MobileAPI'], function (){
    Route::post('login','DermaController@login');
    Route::post('validateToken','DermaController@validateToken');

    Route::get('getDerma','DermaController@getDerma');
    Route::post('getDermaInfo','DermaController@getDermaInfo');
  


    //Route::post('returnDermaView','DermaController@returnDermaView');


});

/**
 * Ride Now API
 */
Route::group(['prefix' => 'RideNowV1'], function(){
    Route::group(['prefix' => 'auth'], function() {
        Route::post('login', 'RideNowAPI\AuthController@login');
        Route::post('register', 'RideNowAPI\RegisterController@registerViaApi');
    });
    // Route::group(['prefix' => 'public'], function(){
    //     Route::get('index','RideNowAPI\PublicController@index');
    //     Route::get('menu','RideNowAPI\PublicController@getMenu');
    // });

    Route::group(['prefix' => 'vehicle'], function(){
        Route::get('types','RideNowAPI\VehicleController@listVehicleType');
    });
    // Route::group(['prefix' => 'test'], function(){
    //    Route::get('getRoles','RideNowAPI\TestController@getRoles');
    //    Route::post('message','RideNowAPI\TestController@testMessage');
    // });

    //Authenticated Route
    Route::group(['middleware' => 'auth:sanctum'], function(){
        //Auth
        Route::group(['prefix' => 'auth'], function() {
            Route::post('user', 'RideNowAPI\AuthController@getUserData');
            Route::post('logout','RideNowAPI\AuthController@logout');
        });
        //Ride Route
        Route::group(['prefix' => 'ride'], function(){
            Route::get('createdRides','RideNowAPI\RideController@getCreatedRides');
            Route::get('joinedRides','RideNowAPI\RideController@getJoinedRides');
            Route::get('','RideNowAPI\RideController@listAllAvailableRides');
            Route::get('search','RideNowAPI\RideController@searchRide');
            Route::get('details/{ride_id}','RideNowAPI\RideController@getRideDetails');
            Route::post('','RideNowAPI\RideController@createRide');
            Route::post('join/{ride_id}','RideNowAPI\RideController@joinRides');
            Route::post('complete/{ride_id}','RideNowAPI\RideController@completeRide');
            Route::post('start/{ride_id}','RideNowAPI\RideController@startRide');
            Route::post('rate/{ride_id}','RideNowAPI\RideController@rateRide');
            Route::post('leave/{ride_id}','RideNowAPI\RideController@leaveRide');
            Route::put('{ride_id}','RideNowAPI\RideController@updateRide');
            Route::delete('{ride_id}','RideNowAPI\RideController@cancelRide');
        });
        //Vehicle Route
        Route::group(['prefix' => 'vehicle'], function(){ 
            Route::get('','RideNowAPI\VehicleController@listVehicle');
            Route::post('','RideNowAPI\VehicleController@createVehicle');
            Route::patch('{vehicle_id}','RideNowAPI\VehicleController@updateVehicle');
            Route::delete('{vehicle_id}','RideNowAPI\VehicleController@deleteVehicle');
        });
        Route::group(['prefix' => 'users'], function(){ 
            Route::get('balance','RideNowAPI\ProfileController@retrieveUserBalance');
            Route::get('vouchers','RideNowAPI\ProfileController@listUserVouchers');
            Route::post('update','RideNowAPI\ProfileController@updateUserProfile');
        });
        // //Admin Route
        // Route::group(['prefix'=>'admin'],function(){
        //     Route::get('shops','RideNowAPI\AdminController@getShop');
        //     Route::get('dish_types','RideNowAPI\AdminController@getDishTypeList');
        //     Route::post('dishes','RideNowAPI\AdminController@addDishes');
        //     Route::put('dishes','RideNowAPI\AdminController@updateDishes');
        //     Route::delete('dishes','RideNowAPI\AdminController@deleteDishes');
        // });  
    });
    Route::group(['prefix' => 'payment'], function (){
        Route::post('callback','RideNowAPI\PaymentController@paymentCallBack')->name('ride_now.payment_callback');
        Route::get('testcallback','RideNowAPI\PaymentController@showTestCallBack')->name('ride_now.payment_testcallback');
        Route::post('demo/{transaction_token}','RideNowAPI\PaymentController@demoPayment');
        Route::get('{transaction_token}','RideNowAPI\PaymentController@initPayment')->name('ride_now.payment');
    });
});