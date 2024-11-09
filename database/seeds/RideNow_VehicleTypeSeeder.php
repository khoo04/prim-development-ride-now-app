<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RideNow_VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('ride_now__vehicle__types')->insert([
            ['types' => 'car'],
            ['types' => 'motorcycle'],
            ['types' => 'bus'],
            ['types' => 'van'],
        ]);
    }
}
