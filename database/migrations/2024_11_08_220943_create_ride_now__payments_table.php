<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRideNowPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ride_now__payments', function (Blueprint $table) {
            $table->string('payment_id')->primary();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->decimal('amount', 8, 2);
            $table->unsignedBigInteger('ride_id');
            $table->unsignedBigInteger('user_id');
            $table->string('voucher_id')->nullable(); // Match data type and allow null
            $table->timestamps();
        
            // Foreign Keys
            $table->foreign('ride_id')->references('ride_id')->on('ride_now__rides');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('voucher_id')->references('voucher_id')->on('ride_now__vouchers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ride_now__payments');
    }
}
