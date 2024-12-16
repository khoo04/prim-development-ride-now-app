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
            /*
            pending - Awaiting buyer action.
            success - Payment successfully completed by the buyer.
            failed - Payment failed (buyer action).
            ready_to_pay - Ready for the seller to receive payment.
            paid - Payment completed to the seller.
            */
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->integer('required_seats');
            $table->decimal('amount', 8, 2);
            $table->unsignedBigInteger('ride_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('payment_allocation_id')->nullable();
            $table->string('voucher_id')->nullable(); // Match data type and allow null
            $table->timestamps();
        
            // Foreign Keys
            $table->foreign('ride_id')->references('ride_id')->on('ride_now__rides');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('voucher_id')->references('voucher_id')->on('ride_now__vouchers');
            $table->foreign('payment_allocation_id')->references('payment_allocation_id')->on('ride_now__payment_allocations');
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
