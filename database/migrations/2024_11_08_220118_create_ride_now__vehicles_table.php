<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRideNowVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ride_now__vehicles', function (Blueprint $table) {
            $table->id('vehicle_id');
            $table->string('vehicle_registration_number');
            $table->string('manufacturer');
            $table->string('model');
            $table->integer('seats');
            $table->double('average_fuel_consumptions');
            $table->unsignedBigInteger('vehicle_type_id');
            $table->unsignedBigInteger('user_id');
            //Define Soft Deletes
            $table->softDeletes('deleted_at');
            $table->timestamps();

            //Foreign Key
            $table->foreign('vehicle_type_id')->references('vehicle_type_id')->on('ride_now__vehicle__types');
            $table->foreign('user_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ride_now__vehicles');
    }
}
