<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRideNowVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ride_now__vouchers', function (Blueprint $table) {
            $table->id('voucher_id');
            $table->decimal('amount',8,2);
            $table->boolean('redeemed');
            $table->unsignedBigInteger('user_id'); 
            $table->timestamps();

            //Foreign Key
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
        Schema::dropIfExists('ride_now__vouchers');
    }
}
