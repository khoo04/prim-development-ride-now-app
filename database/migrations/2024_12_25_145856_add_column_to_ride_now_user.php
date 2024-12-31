<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToRideNowUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('ride_now__rides_users', function (Blueprint $table) {
        //     $table->decimal('rating', 2, 1)->nullable()->after('user_id')->check('rating >= 0 AND rating <= 5'); ; // Rating column
        // });

        // Add a check constraint to ensure the rating is between 0 and 5
        // DB::statement('ALTER TABLE ride_now__rides_users ADD CONSTRAINT check_rating CHECK (rating >= 0 AND rating <= 5)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('ride_now__rides_users', function (Blueprint $table) {
        //     $table->dropColumn('rating');
        // });

        // DB::statement('ALTER TABLE ride_now__rides_users DROP CONSTRAINT check_rating');
    }
}
