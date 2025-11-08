<?php
namespace Database\Seeders;

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
        DB::table('inf_carrier_service_team')->insert([
          'id'                    => 1,
          'team_name'             => '超级管理员',
          'is_administrator'      => 1,
          'is_kefu'               => 0,
          'status'                => 1,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_carrier_service_team')->insert([
          'id'                    => 2,
          'team_name'             => '客服',
          'is_administrator'      => 0,
          'is_kefu'               => 1,
          'status'                => 1,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_carrier_service_team')->insert([
          'id'                    => 3,
          'team_name'             => '风控',
          'is_administrator'      => 0,
          'is_kefu'               => 1,
          'status'                => 1,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_carrier_service_team')->insert([
          'id'                    => 4,
          'team_name'             => '财务',
          'is_administrator'      => 0,
          'status'                => 1,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_carrier_user')->insert([
            'id'                     => 1,
            'team_id'                => 1,
            'username'               => 'winwinasia',
            'password'               => \Hash::make('e10adc3949ba59abbe56e057f20f883e'),
            'login_at'               => null,
            'deleted_at'             => null,
            'status'                 => 1,
            'is_super_admin'         => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
    }
}
