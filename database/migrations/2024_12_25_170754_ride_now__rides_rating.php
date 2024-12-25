<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RideNowRidesRating extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ride_now__rides_rating', function (Blueprint $table) {

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ride_id');
            $table->decimal('rating', 2, 1)->nullable()->check('rating >= 0 AND rating <= 5'); // Rating column
            $table->timestamps();

            // Set composite primary key for user_id and ride_id
            $table->primary(['user_id', 'ride_id']);

            //Refer to user who joined the rides
            $table->foreign('user_id')->references('id')->on('users');
            //Foreign Key
            $table->foreign('ride_id')->references('ride_id')->on('ride_now__rides');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ride_now__rides_rating');
    }
}
