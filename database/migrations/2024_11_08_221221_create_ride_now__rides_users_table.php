<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRideNowRidesUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ride_now__rides_users', function (Blueprint $table) {
            $table->unsignedBigInteger('ride_id');
            $table->unsignedBigInteger('user_id');
         
            $table->boolean('joined')->default(false);
            $table->timestamps();

            //Foreign Key
            $table->foreign('ride_id')->references('ride_id')->on('ride_now__rides');
            //Refer to user who joined the rides
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
        Schema::dropIfExists('ride_now__rides_users');
    }
}
