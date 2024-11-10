<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRideNowRidesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ride_now__rides', function (Blueprint $table) {
            $table->id('ride_id');
            $table->string('origin_address');
            $table->string('destination_address');
            $table->dateTime('departure_time');
            //Default Pending Status
            $table->enum('status', ['pending', 'confirmed', 'completed', 'canceled'])->default('pending');
            $table->decimal('base_cost', 8, 2);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('vehicle_id');

            //Created_At and Updated_At
            $table->timestamps();
            
            // Set up the foreign key constraint, refer to user who created the rides
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('ride_now__vehicles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ride_now__rides');
    }
}
