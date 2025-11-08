<?php

use Illuminate\Database\Seeder;

class CarrierUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('inf_carrier_user')->insert([
            'id'                => 1,
            'username'               => 'winwinasia',
            'password'               => \Hash::make('e10adc3949ba59abbe56e057f20f883e'),
            'status'                 => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
    }
}
