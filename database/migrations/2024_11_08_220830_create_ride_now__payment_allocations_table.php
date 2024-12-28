<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRideNowPaymentAllocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ride_now__payment_allocations', function (Blueprint $table) {
            $table->id('payment_allocation_id');
            $table->string('description');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->decimal('total_amount', 8, 2);
            $table->unsignedBigInteger('ride_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('ride_id')->references('ride_id')->on('ride_now__rides');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ride_now__payment_allocations');
    }
}
