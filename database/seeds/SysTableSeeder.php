<?php

use Illuminate\Database\Seeder;

class SysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('def_currency')->insert([
            'id'                     => 1,
            'name'                   => 'CNY',
            'zh_name'                => '人民币',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_language')->insert([
            'id'                     => 1,
            'name'                   => 'zh',
            'zh_name'                => '汉语',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 1,
            'name'                   => 'Trc20',
            'currency'               => 'USD',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 2,
            'name'                   => 'Erc20',
            'currency'               => 'USD',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 3,
            'name'                   => 'Okpay',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 4,
            'name'                   => 'Gopay',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 5,
            'name'                   => 'Gcash',
            'currency'               => 'PHP',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 6,
            'name'                   => 'Topay',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 7,
            'name'                   => 'Ebpay',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 8,
            'name'                   => 'Wanb',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 9,
            'name'                   => 'Jdpay',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 10,
            'name'                   => 'Kdpay',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 11,
            'name'                   => 'Nopay',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_third_wallet')->insert([
            'id'                     => 12,
            'name'                   => 'Bobipay',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 1,
            'factory_name'           => 'okpay代收',
            'code'                   => 'okpay',
            'currency'               => 'CNY',
            'ip'                     => '34.92.72.6,35.241.107.191,34.92.181.137,35.220.178.196,35.241.94.149,34.92.190.234,34.92.72.63,34.92.72.6',
            'third_wallet_id'        => 3,
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 2,
            'factory_name'           => 'okpay代付',
            'code'                   => 'outokpay',
            'currency'               => 'CNY',
            'ip'                     => '34.92.72.6,35.241.107.191,34.92.181.137,35.220.178.196,35.241.94.149,34.92.190.234,34.92.72.63,34.92.72.6',
            'third_wallet_id'        => 3,
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 3,
            'factory_name'           => 'topay代收',
            'code'                   => 'topay',
            'currency'               => 'CNY',
            'ip'                     => '34.124.235.89,35.198.208.148,35.247.136.232,34.124.255.118,34.124.166.21,34.124.215.213,34.96.228.61',
            'third_wallet_id'        => 6,
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 4,
            'factory_name'           => 'topay代付',
            'code'                   => 'outtopay',
            'currency'               => 'CNY',
            'ip'                     => '34.124.235.89,35.198.208.148,35.247.136.232,34.124.255.118,34.124.166.21,34.124.215.213,34.96.228.61',
            'third_wallet_id'        => 6,
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 5,
            'factory_name'           => 'gopay代收',
            'code'                   => 'gopay',
            'currency'               => 'CNY',
            'ip'                     => '104.199.209.3,34.80.58.217,35.229.179.75,34.80.19.53,104.199.209.3,34.80.58.217,35.229.179.75',
            'third_wallet_id'        => 4,
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 6,
            'factory_name'           => 'gopay代付',
            'code'                   => 'outgopay',
            'currency'               => 'CNY',
            'ip'                     => '104.199.209.3,34.80.58.217,35.229.179.75,34.80.19.53,104.199.209.3,34.80.58.217,35.229.179.75',
            'third_wallet_id'        => 4,
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 7,
            'factory_name'           => 'copopay代收',
            'code'                   => 'copopay',
            'currency'               => 'USD',
            'ip'                     => '47.75.116.249,47.75.107.130',
            'third_wallet_id'        => 1,
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 8,
            'factory_name'           => 'copopay代付',
            'code'                   => 'outcopopay',
            'currency'               => 'USD',
            'ip'                     => '47.75.116.249,47.75.107.130',
            'third_wallet_id'        => 1,
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 9,
            'factory_name'           => 'ebpay代收',
            'code'                   => 'ebpay',
            'currency'               => 'CNY',
            'ip'                     => '18.167.124.170,18.163.22.178,34.96.248.56,34.92.75.222,182.16.83.50',
            'third_wallet_id'        => 7,
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 10,
            'factory_name'           => 'ebpay代付',
            'code'                   => 'outebpay',
            'currency'               => 'CNY',
            'ip'                     => '18.167.124.170,18.163.22.178,34.96.248.56,34.92.75.222',
            'third_wallet_id'        => 7,
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 11,
            'factory_name'           => 'Wanb代收',
            'code'                   => 'wanbpay',
            'currency'               => 'CNY',
            'ip'                     => '35.241.73.134,34.92.119.232',
            'third_wallet_id'        => 8,
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 12,
            'factory_name'           => 'Wanb代付',
            'code'                   => 'outwanbpay',
            'currency'               => 'CNY',
            'ip'                     => '35.241.73.134,34.92.119.232',
            'third_wallet_id'        => 8,
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 13,
            'factory_name'           => 'jdpay代收',
            'code'                   => 'jdpay',
            'currency'               => 'CNY',
            'ip'                     => '47.242.150.36',
            'third_wallet_id'        => 9,
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 14,
            'factory_name'           => 'jdpay代付',
            'code'                   => 'outjdpay',
            'currency'               => 'CNY',
            'ip'                     => '47.242.150.36',
            'third_wallet_id'        => 9,
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 15,
            'factory_name'           => 'kdpay代收',
            'code'                   => 'kdpay',
            'currency'               => 'CNY',
            'ip'                     => '47.242.150.36',
            'third_wallet_id'        => 10,
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 16,
            'factory_name'           => 'kdpay代付',
            'code'                   => 'outkdpay',
            'currency'               => 'CNY',
            'ip'                     => '47.242.150.36',
            'third_wallet_id'        => 10,
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 17,
            'factory_name'           => 'nopay代收',
            'code'                   => 'nopay',
            'currency'               => 'CNY',
            'ip'                     => '47.242.150.36',
            'third_wallet_id'        => 11,
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 18,
            'factory_name'           => 'nopay代付',
            'code'                   => 'outnopay',
            'currency'               => 'CNY',
            'ip'                     => '47.242.150.36',
            'third_wallet_id'        => 11,
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 19,
            'factory_name'           => 'bobipay代收',
            'code'                   => 'bobipay',
            'currency'               => 'CNY',
            'ip'                     => '34.92.58.249,35.236.190.252',
            'third_wallet_id'        => 12,
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 20,
            'factory_name'           => 'bobipay代付',
            'code'                   => 'outbobipay',
            'currency'               => 'CNY',
            'ip'                     => '34.92.58.249,35.236.190.252',
            'third_wallet_id'        => 12,
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);
        
        DB::table('def_pay_factory_list')->insert([
            'id'                     => 21,
            'factory_name'           => '新汇丰代收',
            'code'                   => 'xffpay',
            'currency'               => 'CNY',
            'ip'                     => '198.13.45.171,158.247.208.201,192.243.127.164,112.205.149.205',
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 22,
            'factory_name'           => '趣支付代收',
            'code'                   => 'qupay',
            'currency'               => 'CNY',
            'ip'                     => '13.112.90.10',
            'status'                 => 1,
            'type'                   => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 23,
            'factory_name'           => '盛银代付',
            'code'                   => 'outsypay',
            'currency'               => 'CNY',
            'ip'                     => '38.181.22.133,38.181.22.226',
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 1,
            'currency'               => 'CNY',
            'sign'                   => 'digital_rate',
            'value'                  => '7',
            'remark'                 => '存款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 2,
            'currency'               => 'CNY',
            'sign'                   => 'withdraw_digital_rate',
            'value'                  => '7',
            'remark'                 => '取款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 3,
            'currency'               => 'CNY',
            'sign'                   => 'in_r_out_u',
            'value'                  => '7',
            'remark'                 => '进人民币出U',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 4,
            'currency'               => 'CNY',
            'sign'                   => 'in_t_out_u',
            'value'                  => '7',
            'remark'                 => '存钱包出U',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 5,
            'currency'               => 'CNY',
            'sign'                   => 'third_wallet',
            'value'                  => json_encode([]),
            'remark'                 => '用户可绑定钱包及数字币地址',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 8,
            'currency'               => 'CNY',
            'sign'                   => 'disable_withdraw_channel',
            'value'                  => json_encode([]),
            'remark'                 => '禁止提现通道',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_pay_factory_list')->insert([
            'id'                     => 24,
            'factory_name'           => '恒生代付',
            'code'                   => 'outhspay',
            'currency'               => 'CNY',
            'ip'                     => '43.198.157.200,18.166.250.177,112.205.159.227,182.16.83.50',
            'status'                 => 1,
            'type'                   => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);
        
        DB::table('def_account_change_type')->insert([
            'sign'                   => 'recharge',
            'name'                   => '充值',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'agent_recharge',
            'name'                   => '代理充值',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'commission_from_child',
            'name'                   => '下级返佣',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 1,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'gift',
            'name'                   => '活动礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 1,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'gift_transfer_add',
            'name'                   => '手动礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'update_level_gift',
            'name'                   => '升级礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'dividend_from_parent',
            'name'                   => '分红',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 1,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'dividend_from_recharge',
            'name'                   => '充值分红',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 1,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'commission_dividends',
            'name'                   => '直属佣金分红',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 1,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'game_score_add',
            'name'                   => '游戏补分',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 1,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'casino_transfer_in',
            'name'                   => '转入中心钱包',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 1,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'casino_transfer_out_error',
            'name'                   => '转出中心钱包失败',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 1,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'luck_draw_prize',
            'name'                   => '幸运轮盘奖金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 1,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'register_gift',
            'name'                   => '注册礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 1,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'signin_gift',
            'name'                   => '签到礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'win_or_loss_gift',
            'name'                   => '亏损金礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'code_gift',
            'name'                   => '体验券',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'break_through_gift',
            'name'                   => '闯关礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'video_break_through_gift',
            'name'                   => '视讯闯关礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'electronic_break_through_gift',
            'name'                   => '电子闯关礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'esport_break_through_gift',
            'name'                   => '电竞闯关礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'card_break_through_gift',
            'name'                   => '棋牌闯关礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'sport_break_through_gift',
            'name'                   => '体育闯关礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'lottery_break_through_gift',
            'name'                   => '彩票闯关礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'fish_break_through_gift',
            'name'                   => '捕鱼闯关礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

         DB::table('def_account_change_type')->insert([
            'sign'                   => 'casino_gift',
            'name'                   => '真人礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'electronic_gift',
            'name'                   => '电子礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'esport_gift',
            'name'                   => '电竞礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'card_gift',
            'name'                   => '棋牌礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'sport_gift',
            'name'                   => '体育礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'lottery_gift',
            'name'                   => '彩票礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'fish_gift',
            'name'                   => '捕鱼礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'rank_list_gift',
            'name'                   => '排行榜礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'inside_transfer_in',
            'name'                   => '站内转入',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);  

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'capitation_fee_add',
            'name'                   => '人头费',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'agent_reimbursement',
            'name'                   => '代理报销',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'wholesale_shares',
            'name'                   => '全盘分红',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'performance_shares',
            'name'                   => '业绩分红',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'regress_gift',
            'name'                   => '回归礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'forum_up_score',
            'name'                   => '论坛上分',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'withdraw_apply',
            'name'                   => '申请提现',
            'type'                   => 3,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'transfer_in_wallet',
            'name'                   => '保险箱转入钱包',
            'type'                   => 3,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'transfer_in_safe',
            'name'                   => '钱包转入保险箱',
            'type'                   => 3,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'withdraw_cancel',
            'name'                   => '取消提现',
            'type'                   => 3,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'withdraw_finish',
            'name'                   => '提现成功',
            'type'                   => 2,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'casino_transfer_out',
            'name'                   => '转出中心钱包',
            'type'                   => 2,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 1,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'gift_transfer_reduce',
            'name'                   => '活动扣减',
            'type'                   => 2,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'buy_video_card_level',
            'name'                   => '购买视频会员',
            'type'                   => 2,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'safe_transfer_reduce',
            'name'                   => '保险箱扣减',
            'type'                   => 2,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'inside_transfer_to',
            'name'                   => '站内转出',
            'type'                   => 2,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'reimbursement_gift',
            'name'                   => '报销礼金',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_account_change_type')->insert([
            'sign'                   => 'agent_support',
            'name'                   => '代理扶持',
            'type'                   => 1,
            'amount'                 => 1,
            'user_id'                => 1,
            'platform_id'            => 0,
            'from_id'                => 0,
            'to_id'                  => 0,
            'activity_id'            => 0,
            'admin_id'               => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 1,
            'bank_name'              => '工商银行',
            'bank_code'              => 'ICBC',
            'bank_background_url'    => '0/bankicon/CNY/ICBC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 2,
            'bank_name'              => '农业银行',
            'bank_code'              => 'ABC',
            'bank_background_url'    => '0/bankicon/CNY/ABC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 3,
            'bank_name'              => '招商银行',
            'bank_code'              => 'CMB',
            'bank_background_url'    => '0/bankicon/CNY/CMB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 4,
            'bank_name'              => '中国银行',
            'bank_code'              => 'BOC',
            'bank_background_url'    => '0/bankicon/CNY/BOC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 5,
            'bank_name'              => '建设银行',
            'bank_code'              => 'CCB',
            'bank_background_url'    => '0/bankicon/CNY/CCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 6,
            'bank_name'              => '民生银行',
            'bank_code'              => 'CMBC',
            'bank_background_url'    => '0/bankicon/CNY/CMBC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 7,
            'bank_name'              => '中信银行',
            'bank_code'              => 'ECITIC',
            'bank_background_url'    => '0/bankicon/CNY/ECITIC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 8,
            'bank_name'              => '交通银行',
            'bank_code'              => 'COMM',
            'bank_background_url'    => '0/bankicon/CNY/COMM.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 9,
            'bank_name'              => '兴业银行',
            'bank_code'              => 'CIB',
            'bank_background_url'    => '0/bankicon/CNY/CIB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 10,
            'bank_name'              => '光大银行',
            'bank_code'              => 'CEB',
            'bank_background_url'    => '0/bankicon/CNY/CEB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 12,
            'bank_name'              => '邮政储蓄银行',
            'bank_code'              => 'PSBC',
            'bank_background_url'    => '0/bankicon/CNY/PSBC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 13,
            'bank_name'              => '北京银行',
            'bank_code'              => 'BCCB',
            'bank_background_url'    => '0/bankicon/CNY/BCCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 14,
            'bank_name'              => '平安银行',
            'bank_code'              => 'PAYH',
            'bank_background_url'    => '0/bankicon/CNY/PAYH.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 15,
            'bank_name'              => '浦发银行',
            'bank_code'              => 'SPDB',
            'bank_background_url'    => '0/bankicon/CNY/SPDB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 16,
            'bank_name'              => '广发银行',
            'bank_code'              => 'CGB',
            'bank_background_url'    => '0/bankicon/CNY/CGB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 17,
            'bank_name'              => '华夏银行',
            'bank_code'              => 'HXB',
            'bank_background_url'    => '0/bankicon/CNY/HXB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 18,
            'bank_name'              => '上海银行',
            'bank_code'              => 'BOS',
            'bank_background_url'    => '0/bankicon/CNY/BOS.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('def_bank')->insert([
            'id'                     => 122,
            'bank_name'              => '安徽农村信用社',
            'bank_code'              => 'AHRCU',
            'bank_background_url'    => '0/bankicon/CNY/AHRCU.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 123,
            'bank_name'              => '北京农商银行',
            'bank_code'              => 'BRCB',
            'bank_background_url'    => '0/bankicon/CNY/BRCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 124,
            'bank_name'              => '成都农商银行',
            'bank_code'              => 'CDRCB',
            'bank_background_url'    => '0/bankicon/CNY/CDRCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 125,
            'bank_name'              => '成都银行',
            'bank_code'              => 'BOCD',
            'bank_background_url'    => '0/bankicon/CNY/BOCD.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 126,
            'bank_name'              => '承德银行',
            'bank_code'              => 'BOCDB',
            'bank_background_url'    => '0/bankicon/CNY/BOCDB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 127,
            'bank_name'              => '大连农商银行',
            'bank_code'              => 'DRCB',
            'bank_background_url'    => '0/bankicon/CNY/DRCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 128,
            'bank_name'              => '大连银行',
            'bank_code'              => 'DLCB',
            'bank_background_url'    => '0/bankicon/CNY/DLCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 129,
            'bank_name'              => '东莞农商银行',
            'bank_code'              => 'DRC',
            'bank_background_url'    => '0/bankicon/CNY/DRC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 130,
            'bank_name'              => '东莞银行',
            'bank_code'              => 'BOD',
            'bank_background_url'    => '0/bankicon/CNY/BOD.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 131,
            'bank_name'              => '福建农村信用社',
            'bank_code'              => 'FJRC',
            'currency'               => 'CNY',
            'bank_background_url'    => '0/bankicon/CNY/FJRC.png',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 132,
            'bank_name'              => '甘肃农村信用社',
            'bank_code'              => 'GSRC',
            'bank_background_url'    => '0/bankicon/CNY/GSRC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 133,
            'bank_name'              => '广东农村信用社',
            'bank_code'              => 'GDRC',
            'bank_background_url'    => '0/bankicon/CNY/GDRC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 134,
            'bank_name'              => '广西北部湾银行',
            'bank_code'              => 'BGBK',
            'bank_background_url'    => '0/bankicon/CNY/BGBK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 135,
            'bank_name'              => '广州农商银行',
            'bank_code'              => 'GRC',
            'bank_background_url'    => '0/bankicon/CNY/GRC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 136,
            'bank_name'              => '广州银行',
            'bank_code'              => 'GZCB',
            'bank_background_url'    => '0/bankicon/CNY/GZCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 137,
            'bank_name'              => '贵阳银行',
            'bank_code'              => 'GYB',
            'bank_background_url'    => '0/bankicon/CNY/GYB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 138,
            'bank_name'              => '贵州农村信用社',
            'bank_code'              => 'GZRC',
            'bank_background_url'    => '0/bankicon/CNY/GZRC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 139,
            'bank_name'              => '贵州银行',
            'bank_code'              => 'BGZB',
            'bank_background_url'    => '0/bankicon/CNY/BGZB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 140,
            'bank_name'              => '哈尔滨银行',
            'bank_code'              => 'HRBB',
            'bank_background_url'    => '0/bankicon/CNY/HRBB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 141,
            'bank_name'              => '海南农村信用社',
            'bank_code'              => 'HNB',
            'bank_background_url'    => '0/bankicon/CNY/HNB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 142,
            'bank_name'              => '邯郸银行',
            'bank_code'              => 'HDCB',
            'bank_background_url'    => '0/bankicon/CNY/HDCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 143,
            'bank_name'              => '杭州银行',
            'bank_code'              => 'HZCB',
            'bank_background_url'    => '0/bankicon/CNY/HZCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 144,
            'bank_name'              => '河北农村信用社',
            'bank_code'              => 'HEBNX',
            'bank_background_url'    => '0/bankicon/CNY/HEBNX.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 145,
            'bank_name'              => '河北银行',
            'bank_code'              => 'BHB',
            'bank_background_url'    => '0/bankicon/CNY/BHB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 146,
            'bank_name'              => '河南农村信用社',
            'bank_code'              => 'HNNX',
            'bank_background_url'    => '0/bankicon/CNY/HNNX.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 147,
            'bank_name'              => '黑龙江农村信用社',
            'bank_code'              => 'HLJRCC',
            'bank_background_url'    => '0/bankicon/CNY/HLJRCC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 148,
            'bank_name'              => '恒丰银行',
            'bank_code'              => 'HFCB',
            'bank_background_url'    => '0/bankicon/CNY/HFCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 149,
            'bank_name'              => '湖北农村信用社',
            'bank_code'              => 'HBNX',
            'bank_background_url'    => '0/bankicon/CNY/HBNX.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 150,
            'bank_name'              => '湖北银行',
            'bank_code'              => 'HBCB',
            'bank_background_url'    => '0/bankicon/CNY/HBCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 151,
            'bank_name'              => '湖南农村信用社',
            'bank_code'              => 'HNNXS',
            'bank_background_url'    => '0/bankicon/CNY/HNNXS.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 152,
            'bank_name'              => '徽商银行',
            'bank_code'              => 'HSBANK',
            'bank_background_url'    => '0/bankicon/CNY/HSBANK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 153,
            'bank_name'              => '吉林省农村信用社',
            'bank_code'              => 'JLNX',
            'bank_background_url'    => '0/bankicon/CNY/JLNX.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 154,
            'bank_name'              => '江苏农商银行',
            'bank_code'              => 'JSRCB',
            'bank_background_url'    => '0/bankicon/CNY/JSRCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 155,
            'bank_name'              => '江苏银行',
            'bank_code'              => 'JSBANK',
            'bank_background_url'    => '0/bankicon/CNY/JSBANK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 156,
            'bank_name'              => '江西省农村信用社',
            'bank_code'              => 'JXNXS',
            'bank_background_url'    => '0/bankicon/CNY/JXNXS.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 157,
            'bank_name'              => '江西银行',
            'bank_code'              => 'NCB',
            'bank_background_url'    => '0/bankicon/CNY/NCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 158,
            'bank_name'              => '兰州银行',
            'bank_code'              => 'LZCB',
            'bank_background_url'    => '0/bankicon/CNY/LZCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 159,
            'bank_name'              => '南京银行',
            'bank_code'              => 'NJCB',
            'bank_background_url'    => '0/bankicon/CNY/NJCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 160,
            'bank_name'              => '内蒙古农村信用社',
            'bank_code'              => 'IMRC',
            'bank_background_url'    => '0/bankicon/CNY/IMRC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 161,
            'bank_name'              => '内蒙古银行',
            'bank_code'              => 'H3CB',
            'bank_background_url'    => '0/bankicon/CNY/H3CB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 162,
            'bank_name'              => '宁波银行',
            'bank_code'              => 'NBBANK',
            'bank_background_url'    => '0/bankicon/CNY/NBBANK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 163,
            'bank_name'              => '厦门银行',
            'bank_code'              => 'XMBANK',
            'bank_background_url'    => '0/bankicon/CNY/XMBANK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 164,
            'bank_name'              => '山东农村信用社',
            'bank_code'              => 'SDRCU',
            'bank_background_url'    => '0/bankicon/CNY/SDRCU.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 165,
            'bank_name'              => '山西农村信用社',
            'bank_code'              => 'SXRCU',
            'bank_background_url'    => '0/bankicon/CNY/SXRCU.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 166,
            'bank_name'              => '上海农商银行',
            'bank_code'              => 'SHRCB',
            'bank_background_url'    => '0/bankicon/CNY/SHRCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

         DB::table('def_bank')->insert([
            'id'                     => 167,
            'bank_name'              => '深圳农商银行',
            'bank_code'              => 'SRCB',
            'bank_background_url'    => '0/bankicon/CNY/SRCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 168,
            'bank_name'              => '四川农村信用社',
            'bank_code'              => 'SCRCU',
            'bank_background_url'    => '0/bankicon/CNY/SCRCU.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 169,
            'bank_name'              => '台州银行',
            'bank_code'              => 'TZCB',
            'bank_background_url'    => '0/bankicon/CNY/TZCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 170,
            'bank_name'              => '天津银行',
            'bank_code'              => 'TCCB',
            'bank_background_url'    => '0/bankicon/CNY/TCCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 171,
            'bank_name'              => '温州银行',
            'bank_code'              => 'WZCB',
            'bank_background_url'    => '0/bankicon/CNY/WZCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 172,
            'bank_name'              => '云南农村信用社',
            'bank_code'              => 'YNRCC',
            'bank_background_url'    => '0/bankicon/CNY/YNRCC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 173,
            'bank_name'              => '浙江农村信用社',
            'bank_code'              => 'ZJNX',
            'bank_background_url'    => '0/bankicon/CNY/ZJNX.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 174,
            'bank_name'              => '重庆农商银行',
            'bank_code'              => 'CRCBANK',
            'bank_background_url'    => '0/bankicon/CNY/CRCBANK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 175,
            'bank_name'              => '渤海银行',
            'bank_code'              => 'CBHB',
            'bank_background_url'    => '0/bankicon/CNY/CBHB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 176,
            'bank_name'              => '东亚银行',
            'bank_code'              => 'HKBEA',
            'bank_background_url'    => '0/bankicon/CNY/HKBEA.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 177,
            'bank_name'              => '浙商银行',
            'bank_code'              => 'CZB',
            'bank_background_url'    => '0/bankicon/CNY/CZB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 178,
            'bank_name'              => '长沙银行',
            'bank_code'              => 'CSCB',
            'bank_background_url'    => '0/bankicon/CNY/CSCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 179,
            'bank_name'              => '桂林银行',
            'bank_code'              => 'GLB',
            'bank_background_url'    => '0/bankicon/CNY/GLB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 180,
            'bank_name'              => '广西农信',
            'bank_code'              => 'GXRCU',
            'bank_background_url'    => '0/bankicon/CNY/GXRCU.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 181,
            'bank_name'              => '吉林银行',
            'bank_code'              => 'JLBANK',
            'bank_background_url'    => '0/bankicon/CNY/JLBANK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 182,
            'bank_name'              => '武汉农商银行',
            'bank_code'              => 'WHRCB',
            'bank_background_url'    => '0/bankicon/CNY/WHRCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 183,
            'bank_name'              => '威海银行',
            'bank_code'              => 'WHCCB',
            'bank_background_url'    => '0/bankicon/CNY/WHCCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 184,
            'bank_name'              => '青岛银行',
            'bank_code'              => 'QDCCB',
            'bank_background_url'    => '0/bankicon/CNY/QDCCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 185,
            'bank_name'              => '莱商银行',
            'bank_code'              => 'LSBANK',
            'bank_background_url'    => '0/bankicon/CNY/LSBANK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 186,
            'bank_name'              => '齐鲁银行',
            'bank_code'              => 'QLBANK',
            'bank_background_url'    => '0/bankicon/CNY/QLBANK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 187,
            'bank_name'              => '烟台银行',
            'bank_code'              => 'YTBANK',
            'bank_background_url'    => '0/bankicon/CNY/YTBANK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 188,
            'bank_name'              => '柳州银行',
            'bank_code'              => 'LZCCB',
            'bank_background_url'    => '0/bankicon/CNY/LZCCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 189,
            'bank_name'              => '北京商业银行',
            'bank_code'              => 'BJCB',
            'bank_background_url'    => '0/bankicon/CNY/BJCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 190,
            'bank_name'              => '泰隆银行',
            'bank_code'              => 'ZJTLCB',
            'bank_background_url'    => '0/bankicon/CNY/ZJTLCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 191,
            'bank_name'              => '稠州银行',
            'bank_code'              => 'CZCB',
            'bank_background_url'    => '0/bankicon/CNY/CZCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 192,
            'bank_name'              => '自贡银行',
            'bank_code'              => 'ZGCCB',
            'bank_background_url'    => '0/bankicon/CNY/ZGCCB.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 193,
            'bank_name'              => '民泰银行',
            'bank_code'              => 'MTBANK',
            'bank_background_url'    => '0/bankicon/CNY/MTBANK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 194,
            'bank_name'              => '银座村镇银行',
            'bank_code'              => 'YZBANK',
            'bank_background_url'    => '0/bankicon/CNY/YZBANK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 195,
            'bank_name'              => '营口银行',
            'bank_code'              => 'BOYK',
            'bank_background_url'    => '0/bankicon/CNY/BOYK.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 196,
            'bank_name'              => '临商银行',
            'bank_code'              => 'LSBC',
            'bank_background_url'    => '0/bankicon/CNY/LSBC.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('def_bank')->insert([
            'id'                     => 197,
            'bank_name'              => '沧州银行',
            'bank_code'              => 'BOCZ',
            'bank_background_url'    => '0/bankicon/CNY/BOCZ.png',
            'currency'               => 'CNY',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 1,
            'category_name'          => 'PC首页',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 9,
            'category_name'          => 'PC优惠活动',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 10,
            'category_name'          => '影视',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 11,
            'category_name'          => 'PC端方形LOGO',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 12,
            'category_name'          => '手机端方形LOGO',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 13,
            'category_name'          => 'IOS二维码',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 14,
            'category_name'          => '安卓二维码',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 15,
            'category_name'          => 'APP游戏页轮播',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_image_category')->insert([
            'id'                     => 16,
            'category_name'          => 'APP首页',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 17,
            'category_name'          => '手机端优惠活动',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 18,
            'category_name'          => '代理PC端轮播',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 19,
            'category_name'          => '代理手机端轮播',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 20,
            'category_name'          => 'PC端注册页面LOGO',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 21,
            'category_name'          => '手机端注册页面LOGO',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 22,
            'category_name'          => 'PC端首页弹窗',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_image_category')->insert([
            'id'                     => 23,
            'category_name'          => '手机端首页弹窗',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_area')->insert([
          'id'                    => 1,
          'type'                  => 1,
          'name'                  => '全部省份',
          'parent_id'             => 0,
          'inner_code'            => '000001',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 110000,
          'type'                  => 2,
          'name'                  => '北京',
          'parent_id'             => 1,
          'inner_code'            => '000001110000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 110100,
          'type'                  => 3,
          'name'                  => '北京市',
          'parent_id'             => 110000,
          'inner_code'            => '000001110000110100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 120000,
          'type'                  => 2,
          'name'                  => '天津',
          'parent_id'             => 1,
          'inner_code'            => '000001120000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 120100,
          'type'                  => 3,
          'name'                  => '天津市',
          'parent_id'             => 120000,
          'inner_code'            => '000001120000120100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 130000,
          'type'                  => 2,
          'name'                  => '河北省',
          'parent_id'             => 1,
          'inner_code'            => '000001130000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 130100,
          'type'                  => 3,
          'name'                  => '石家庄市',
          'parent_id'             => 130000,
          'inner_code'            => '000001130000130100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 130200,
          'type'                  => 3,
          'name'                  => '唐山市',
          'parent_id'             => 130000,
          'inner_code'            => '000001130000130200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 130300,
          'type'                  => 3,
          'name'                  => '秦皇岛市',
          'parent_id'             => 130000,
          'inner_code'            => '000001130000130300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 130400,
          'type'                  => 3,
          'name'                  => '邯郸市',
          'parent_id'             => 130000,
          'inner_code'            => '000001130000130400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 130500,
          'type'                  => 3,
          'name'                  => '邢台市',
          'parent_id'             => 130000,
          'inner_code'            => '000001130000130500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 130600,
          'type'                  => 3,
          'name'                  => '保定市',
          'parent_id'             => 130000,
          'inner_code'            => '000001130000130600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 130700,
          'type'                  => 3,
          'name'                  => '张家口市',
          'parent_id'             => 130000,
          'inner_code'            => '000001130000130700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 130800,
          'type'                  => 3,
          'name'                  => '承德市',
          'parent_id'             => 130000,
          'inner_code'            => '000001130000130800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 130900,
          'type'                  => 3,
          'name'                  => '沧州市',
          'parent_id'             => 130000,
          'inner_code'            => '000001130000130900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 131000,
          'type'                  => 3,
          'name'                  => '廊坊市',
          'parent_id'             => 130000,
          'inner_code'            => '000001130000131000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 131100,
          'type'                  => 3,
          'name'                  => '衡水市',
          'parent_id'             => 130000,
          'inner_code'            => '000001130000131100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 140000,
          'type'                  => 2,
          'name'                  => '山西省',
          'parent_id'             => 1,
          'inner_code'            => '000001140000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 140100,
          'type'                  => 3,
          'name'                  => '太原市',
          'parent_id'             => 140000,
          'inner_code'            => '000001140000140100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 140200,
          'type'                  => 3,
          'name'                  => '大同市',
          'parent_id'             => 140000,
          'inner_code'            => '000001140000140200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 140300,
          'type'                  => 3,
          'name'                  => '阳泉市',
          'parent_id'             => 140000,
          'inner_code'            => '000001140000140300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 140400,
          'type'                  => 3,
          'name'                  => '长治市',
          'parent_id'             => 140000,
          'inner_code'            => '000001140000140400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 140500,
          'type'                  => 3,
          'name'                  => '晋城市',
          'parent_id'             => 140000,
          'inner_code'            => '000001140000140500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 140600,
          'type'                  => 3,
          'name'                  => '朔州市',
          'parent_id'             => 140000,
          'inner_code'            => '000001140000140600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 140700,
          'type'                  => 3,
          'name'                  => '晋中市',
          'parent_id'             => 140000,
          'inner_code'            => '000001140000140700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 140800,
          'type'                  => 3,
          'name'                  => '运城市',
          'parent_id'             => 140000,
          'inner_code'            => '000001140000140800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 140900,
          'type'                  => 3,
          'name'                  => '忻州市',
          'parent_id'             => 140000,
          'inner_code'            => '000001140000140900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 141000,
          'type'                  => 3,
          'name'                  => '临汾市',
          'parent_id'             => 140000,
          'inner_code'            => '000001140000141000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 141100,
          'type'                  => 3,
          'name'                  => '吕梁市',
          'parent_id'             => 140000,
          'inner_code'            => '000001140000141100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 150000,
          'type'                  => 2,
          'name'                  => '内蒙古自治区',
          'parent_id'             => 1,
          'inner_code'            => '000001150000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 150100,
          'type'                  => 3,
          'name'                  => '呼和浩特市',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000150100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 150200,
          'type'                  => 3,
          'name'                  => '包头市',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000150200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 150300,
          'type'                  => 3,
          'name'                  => '乌海市',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000150300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 150400,
          'type'                  => 3,
          'name'                  => '赤峰市',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000150400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 150500,
          'type'                  => 3,
          'name'                  => '通辽市',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000150500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 150600,
          'type'                  => 3,
          'name'                  => '鄂尔多斯市',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000150600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 150700,
          'type'                  => 3,
          'name'                  => '呼伦贝尔市',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000150700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 150800,
          'type'                  => 3,
          'name'                  => '巴彦淖尔市',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000150800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 150900,
          'type'                  => 3,
          'name'                  => '乌兰察布市',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000150900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 152200,
          'type'                  => 3,
          'name'                  => '兴安盟',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000152200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 152500,
          'type'                  => 3,
          'name'                  => '锡林郭勒盟',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000152500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 152900,
          'type'                  => 3,
          'name'                  => '阿拉善盟',
          'parent_id'             => 150000,
          'inner_code'            => '000001150000152900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 210000,
          'type'                  => 2,
          'name'                  => '辽宁省',
          'parent_id'             => 1,
          'inner_code'            => '000001210000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 210100,
          'type'                  => 3,
          'name'                  => '沈阳市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000210100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 210200,
          'type'                  => 3,
          'name'                  => '大连市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000210200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 210300,
          'type'                  => 3,
          'name'                  => '鞍山市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000210300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 210400,
          'type'                  => 3,
          'name'                  => '抚顺市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000210400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 210500,
          'type'                  => 3,
          'name'                  => '本溪市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000210500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 210600,
          'type'                  => 3,
          'name'                  => '丹东市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000210600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 210700,
          'type'                  => 3,
          'name'                  => '锦州市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000210700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 210800,
          'type'                  => 3,
          'name'                  => '营口市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000210800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 210900,
          'type'                  => 3,
          'name'                  => '阜新市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000210900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 211000,
          'type'                  => 3,
          'name'                  => '辽阳市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000211000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 211100,
          'type'                  => 3,
          'name'                  => '盘锦市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000211100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 211200,
          'type'                  => 3,
          'name'                  => '铁岭市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000211200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 211300,
          'type'                  => 3,
          'name'                  => '朝阳市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000211300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 211400,
          'type'                  => 3,
          'name'                  => '葫芦岛市',
          'parent_id'             => 210000,
          'inner_code'            => '000001210000211400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 220000,
          'type'                  => 2,
          'name'                  => '吉林省',
          'parent_id'             => 1,
          'inner_code'            => '000001220000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 220100,
          'type'                  => 3,
          'name'                  => '长春市',
          'parent_id'             => 220000,
          'inner_code'            => '000001220000220100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 220200,
          'type'                  => 3,
          'name'                  => '吉林市',
          'parent_id'             => 220000,
          'inner_code'            => '000001220000220200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 220300,
          'type'                  => 3,
          'name'                  => '四平市',
          'parent_id'             => 220000,
          'inner_code'            => '000001220000220300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 220400,
          'type'                  => 3,
          'name'                  => '辽源市',
          'parent_id'             => 220000,
          'inner_code'            => '000001220000220400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 220500,
          'type'                  => 3,
          'name'                  => '通化市',
          'parent_id'             => 220000,
          'inner_code'            => '000001220000220500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 220600,
          'type'                  => 3,
          'name'                  => '白山市',
          'parent_id'             => 220000,
          'inner_code'            => '000001220000220600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 220700,
          'type'                  => 3,
          'name'                  => '松原市',
          'parent_id'             => 220000,
          'inner_code'            => '000001220000220700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 220800,
          'type'                  => 3,
          'name'                  => '白城市',
          'parent_id'             => 220000,
          'inner_code'            => '000001220000220800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 222400,
          'type'                  => 3,
          'name'                  => '延边朝鲜族自治州',
          'parent_id'             => 220000,
          'inner_code'            => '000001220000222400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 230000,
          'type'                  => 2,
          'name'                  => '黑龙江省',
          'parent_id'             => 1,
          'inner_code'            => '000001230000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 230100,
          'type'                  => 3,
          'name'                  => '哈尔滨市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000230100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 230200,
          'type'                  => 3,
          'name'                  => '齐齐哈尔市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000230200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 230300,
          'type'                  => 3,
          'name'                  => '鸡西市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000230300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 230400,
          'type'                  => 3,
          'name'                  => '鹤岗市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000230400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 230500,
          'type'                  => 3,
          'name'                  => '双鸭山市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000230500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 230600,
          'type'                  => 3,
          'name'                  => '大庆市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000230600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 230700,
          'type'                  => 3,
          'name'                  => '伊春市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000230700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 230800,
          'type'                  => 3,
          'name'                  => '佳木斯市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000230800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 230900,
          'type'                  => 3,
          'name'                  => '七台河市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000230900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 231000,
          'type'                  => 3,
          'name'                  => '牡丹江市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000231000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 231100,
          'type'                  => 3,
          'name'                  => '黑河市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000231100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 231200,
          'type'                  => 3,
          'name'                  => '绥化市',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000231200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 232700,
          'type'                  => 3,
          'name'                  => '大兴安岭地区',
          'parent_id'             => 230000,
          'inner_code'            => '000001230000232700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 310000,
          'type'                  => 2,
          'name'                  => '上海',
          'parent_id'             => 1,
          'inner_code'            => '000001310000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 310100,
          'type'                  => 3,
          'name'                  => '上海市',
          'parent_id'             => 310000,
          'inner_code'            => '000001310000310100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 320000,
          'type'                  => 2,
          'name'                  => '江苏省',
          'parent_id'             => 1,
          'inner_code'            => '000001320000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 320100,
          'type'                  => 3,
          'name'                  => '南京市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000320100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 320200,
          'type'                  => 3,
          'name'                  => '无锡市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000320200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 320300,
          'type'                  => 3,
          'name'                  => '徐州市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000320300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 320400,
          'type'                  => 3,
          'name'                  => '常州市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000320400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 320500,
          'type'                  => 3,
          'name'                  => '苏州市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000320500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 320600,
          'type'                  => 3,
          'name'                  => '南通市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000320600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 320700,
          'type'                  => 3,
          'name'                  => '连云港市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000320700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 320800,
          'type'                  => 3,
          'name'                  => '淮安市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000320800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 320900,
          'type'                  => 3,
          'name'                  => '盐城市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000320900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 321000,
          'type'                  => 3,
          'name'                  => '扬州市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000321000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 321100,
          'type'                  => 3,
          'name'                  => '镇江市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000321100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 321200,
          'type'                  => 3,
          'name'                  => '泰州市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000321200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 321300,
          'type'                  => 3,
          'name'                  => '宿迁市',
          'parent_id'             => 320000,
          'inner_code'            => '000001320000321300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 330000,
          'type'                  => 2,
          'name'                  => '浙江省',
          'parent_id'             => 1,
          'inner_code'            => '000001330000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 330100,
          'type'                  => 3,
          'name'                  => '杭州市',
          'parent_id'             => 330000,
          'inner_code'            => '000001330000330100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 330200,
          'type'                  => 3,
          'name'                  => '宁波市',
          'parent_id'             => 330000,
          'inner_code'            => '000001330000330200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 330300,
          'type'                  => 3,
          'name'                  => '温州市',
          'parent_id'             => 330000,
          'inner_code'            => '000001330000330300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 330400,
          'type'                  => 3,
          'name'                  => '嘉兴市',
          'parent_id'             => 330000,
          'inner_code'            => '000001330000330400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 330500,
          'type'                  => 3,
          'name'                  => '湖州市',
          'parent_id'             => 330000,
          'inner_code'            => '000001330000330500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 330600,
          'type'                  => 3,
          'name'                  => '绍兴市',
          'parent_id'             => 330000,
          'inner_code'            => '000001330000330600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 330700,
          'type'                  => 3,
          'name'                  => '金华市',
          'parent_id'             => 330000,
          'inner_code'            => '000001330000330700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 330800,
          'type'                  => 3,
          'name'                  => '衢州市',
          'parent_id'             => 330000,
          'inner_code'            => '000001330000330800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 330900,
          'type'                  => 3,
          'name'                  => '舟山市',
          'parent_id'             => 330000,
          'inner_code'            => '000001330000330900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 331000,
          'type'                  => 3,
          'name'                  => '台州市',
          'parent_id'             => 330000,
          'inner_code'            => '000001330000331000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 331100,
          'type'                  => 3,
          'name'                  => '丽水市',
          'parent_id'             => 330000,
          'inner_code'            => '000001330000331100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 340000,
          'type'                  => 2,
          'name'                  => '安徽省',
          'parent_id'             => 1,
          'inner_code'            => '000001340000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 340100,
          'type'                  => 3,
          'name'                  => '合肥市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000340100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 340200,
          'type'                  => 3,
          'name'                  => '芜湖市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000340200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 340300,
          'type'                  => 3,
          'name'                  => '蚌埠市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000340300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 340400,
          'type'                  => 3,
          'name'                  => '淮南市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000340400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 340500,
          'type'                  => 3,
          'name'                  => '马鞍山市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000340500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 340600,
          'type'                  => 3,
          'name'                  => '淮北市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000340600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 340700,
          'type'                  => 3,
          'name'                  => '铜陵市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000340700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 340800,
          'type'                  => 3,
          'name'                  => '安庆市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000340800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 341000,
          'type'                  => 3,
          'name'                  => '黄山市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000341000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 341100,
          'type'                  => 3,
          'name'                  => '滁州市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000341100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 341200,
          'type'                  => 3,
          'name'                  => '阜阳市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000341200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 341300,
          'type'                  => 3,
          'name'                  => '宿州市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000341300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 341400,
          'type'                  => 3,
          'name'                  => '巢湖市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000341400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 341500,
          'type'                  => 3,
          'name'                  => '六安市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000341500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 341600,
          'type'                  => 3,
          'name'                  => '亳州市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000341600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 341700,
          'type'                  => 3,
          'name'                  => '池州市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000341700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 341800,
          'type'                  => 3,
          'name'                  => '宣城市',
          'parent_id'             => 340000,
          'inner_code'            => '000001340000341800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 350000,
          'type'                  => 2,
          'name'                  => '福建省',
          'parent_id'             => 1,
          'inner_code'            => '000001350000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 350100,
          'type'                  => 3,
          'name'                  => '福州市',
          'parent_id'             => 350000,
          'inner_code'            => '000001350000350100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 350200,
          'type'                  => 3,
          'name'                  => '厦门市',
          'parent_id'             => 350000,
          'inner_code'            => '000001350000350200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 350300,
          'type'                  => 3,
          'name'                  => '莆田市',
          'parent_id'             => 350000,
          'inner_code'            => '000001350000350300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 350400,
          'type'                  => 3,
          'name'                  => '三明市',
          'parent_id'             => 350000,
          'inner_code'            => '000001350000350400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 350500,
          'type'                  => 3,
          'name'                  => '泉州市',
          'parent_id'             => 350000,
          'inner_code'            => '000001350000350500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 350600,
          'type'                  => 3,
          'name'                  => '漳州市',
          'parent_id'             => 350000,
          'inner_code'            => '000001350000350600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 350700,
          'type'                  => 3,
          'name'                  => '南平市',
          'parent_id'             => 350000,
          'inner_code'            => '000001350000350700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 350800,
          'type'                  => 3,
          'name'                  => '龙岩市',
          'parent_id'             => 350000,
          'inner_code'            => '000001350000350800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 350900,
          'type'                  => 3,
          'name'                  => '宁德市',
          'parent_id'             => 350000,
          'inner_code'            => '000001350000350900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 360000,
          'type'                  => 2,
          'name'                  => '江西省',
          'parent_id'             => 1,
          'inner_code'            => '000001360000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 360100,
          'type'                  => 3,
          'name'                  => '南昌市',
          'parent_id'             => 360000,
          'inner_code'            => '000001360000360100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 360200,
          'type'                  => 3,
          'name'                  => '景德镇市',
          'parent_id'             => 360000,
          'inner_code'            => '000001360000360200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 360300,
          'type'                  => 3,
          'name'                  => '萍乡市',
          'parent_id'             => 360000,
          'inner_code'            => '000001360000360300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 360400,
          'type'                  => 3,
          'name'                  => '九江市',
          'parent_id'             => 360000,
          'inner_code'            => '000001360000360400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 360500,
          'type'                  => 3,
          'name'                  => '新余市',
          'parent_id'             => 360000,
          'inner_code'            => '000001360000360500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 360600,
          'type'                  => 3,
          'name'                  => '鹰潭市',
          'parent_id'             => 360000,
          'inner_code'            => '000001360000360600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 360700,
          'type'                  => 3,
          'name'                  => '赣州市',
          'parent_id'             => 360000,
          'inner_code'            => '000001360000360700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 360800,
          'type'                  => 3,
          'name'                  => '吉安市',
          'parent_id'             => 360000,
          'inner_code'            => '000001360000360800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 360900,
          'type'                  => 3,
          'name'                  => '宜春市',
          'parent_id'             => 360000,
          'inner_code'            => '000001360000360900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 361000,
          'type'                  => 3,
          'name'                  => '抚州市',
          'parent_id'             => 360000,
          'inner_code'            => '000001360000361000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 361100,
          'type'                  => 3,
          'name'                  => '上饶市',
          'parent_id'             => 360000,
          'inner_code'            => '000001360000361100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 370000,
          'type'                  => 2,
          'name'                  => '山东省',
          'parent_id'             => 1,
          'inner_code'            => '000001370000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 370100,
          'type'                  => 3,
          'name'                  => '济南市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000370100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 370200,
          'type'                  => 3,
          'name'                  => '青岛市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000370200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 370300,
          'type'                  => 3,
          'name'                  => '淄博市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000370300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 370400,
          'type'                  => 3,
          'name'                  => '枣庄市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000370400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 370500,
          'type'                  => 3,
          'name'                  => '东营市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000370500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 370600,
          'type'                  => 3,
          'name'                  => '烟台市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000370600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 370700,
          'type'                  => 3,
          'name'                  => '潍坊市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000370700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 370800,
          'type'                  => 3,
          'name'                  => '济宁市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000370800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 370900,
          'type'                  => 3,
          'name'                  => '泰安市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000370900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 371000,
          'type'                  => 3,
          'name'                  => '威海市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000371000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 371100,
          'type'                  => 3,
          'name'                  => '日照市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000371100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 371200,
          'type'                  => 3,
          'name'                  => '莱芜市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000371200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 371300,
          'type'                  => 3,
          'name'                  => '临沂市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000371300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 371400,
          'type'                  => 3,
          'name'                  => '德州市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000371400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 371500,
          'type'                  => 3,
          'name'                  => '聊城市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000371500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 371600,
          'type'                  => 3,
          'name'                  => '滨州市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000371600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 371700,
          'type'                  => 3,
          'name'                  => '菏泽市',
          'parent_id'             => 370000,
          'inner_code'            => '000001370000371700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 410000,
          'type'                  => 2,
          'name'                  => '河南省',
          'parent_id'             => 1,
          'inner_code'            => '000001410000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 410100,
          'type'                  => 3,
          'name'                  => '郑州市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000410100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 410200,
          'type'                  => 3,
          'name'                  => '开封市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000410200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 410300,
          'type'                  => 3,
          'name'                  => '洛阳市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000410300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 410400,
          'type'                  => 3,
          'name'                  => '平顶山市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000410400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 410500,
          'type'                  => 3,
          'name'                  => '安阳市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000410500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 410600,
          'type'                  => 3,
          'name'                  => '鹤壁市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000410600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 410700,
          'type'                  => 3,
          'name'                  => '新乡市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000410700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 410800,
          'type'                  => 3,
          'name'                  => '焦作市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000410800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 410900,
          'type'                  => 3,
          'name'                  => '濮阳市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000410900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 411000,
          'type'                  => 3,
          'name'                  => '许昌市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000411000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 411100,
          'type'                  => 3,
          'name'                  => '漯河市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000411100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 411200,
          'type'                  => 3,
          'name'                  => '三门峡市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000411200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 411300,
          'type'                  => 3,
          'name'                  => '南阳市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000411300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 411400,
          'type'                  => 3,
          'name'                  => '商丘市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000411400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 411500,
          'type'                  => 3,
          'name'                  => '信阳市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000411500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 411600,
          'type'                  => 3,
          'name'                  => '周口市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000411600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 411700,
          'type'                  => 3,
          'name'                  => '驻马店市',
          'parent_id'             => 410000,
          'inner_code'            => '000001410000411700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 420000,
          'type'                  => 2,
          'name'                  => '湖北省',
          'parent_id'             => 1,
          'inner_code'            => '000001420000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 420100,
          'type'                  => 3,
          'name'                  => '武汉市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000420100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 420200,
          'type'                  => 3,
          'name'                  => '黄石市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000420200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 420300,
          'type'                  => 3,
          'name'                  => '十堰市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000420300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 420500,
          'type'                  => 3,
          'name'                  => '宜昌市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000420500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 420600,
          'type'                  => 3,
          'name'                  => '襄阳市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000420600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 420700,
          'type'                  => 3,
          'name'                  => '鄂州市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000420700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 420800,
          'type'                  => 3,
          'name'                  => '荆门市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000420800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 420900,
          'type'                  => 3,
          'name'                  => '孝感市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000420900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 421000,
          'type'                  => 3,
          'name'                  => '荆州市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000421000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 421100,
          'type'                  => 3,
          'name'                  => '黄冈市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000421100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 421200,
          'type'                  => 3,
          'name'                  => '咸宁市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000421200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 421300,
          'type'                  => 3,
          'name'                  => '随州市',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000421300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 422800,
          'type'                  => 3,
          'name'                  => '恩施土家族苗族自治州',
          'parent_id'             => 420000,
          'inner_code'            => '000001420000422800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 430000,
          'type'                  => 2,
          'name'                  => '湖南省',
          'parent_id'             => 1,
          'inner_code'            => '000001430000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 430100,
          'type'                  => 3,
          'name'                  => '长沙市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000430100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 430200,
          'type'                  => 3,
          'name'                  => '株洲市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000430200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 430300,
          'type'                  => 3,
          'name'                  => '湘潭市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000430300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 430400,
          'type'                  => 3,
          'name'                  => '衡阳市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000430400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 430500,
          'type'                  => 3,
          'name'                  => '邵阳市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000430500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 430600,
          'type'                  => 3,
          'name'                  => '岳阳市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000430600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 430700,
          'type'                  => 3,
          'name'                  => '常德市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000430700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 430800,
          'type'                  => 3,
          'name'                  => '张家界市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000430800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 430900,
          'type'                  => 3,
          'name'                  => '益阳市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000430900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 431000,
          'type'                  => 3,
          'name'                  => '郴州市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000431000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 431100,
          'type'                  => 3,
          'name'                  => '永州市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000431100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 431200,
          'type'                  => 3,
          'name'                  => '怀化市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000431200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 431300,
          'type'                  => 3,
          'name'                  => '娄底市',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000431300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 433100,
          'type'                  => 3,
          'name'                  => '湘西土家族苗族自治州',
          'parent_id'             => 430000,
          'inner_code'            => '000001430000433100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 440000,
          'type'                  => 2,
          'name'                  => '广东省',
          'parent_id'             => 1,
          'inner_code'            => '000001440000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 440100,
          'type'                  => 3,
          'name'                  => '广州市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000440100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 440200,
          'type'                  => 3,
          'name'                  => '韶关市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000440200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 440300,
          'type'                  => 3,
          'name'                  => '深圳市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000440300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 440400,
          'type'                  => 3,
          'name'                  => '珠海市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000440400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 440500,
          'type'                  => 3,
          'name'                  => '汕头市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000440500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 440600,
          'type'                  => 3,
          'name'                  => '佛山市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000440600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 440700,
          'type'                  => 3,
          'name'                  => '江门市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000440700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 440800,
          'type'                  => 3,
          'name'                  => '湛江市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000440800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 440900,
          'type'                  => 3,
          'name'                  => '茂名市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000440900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 441200,
          'type'                  => 3,
          'name'                  => '肇庆市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000441200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 441300,
          'type'                  => 3,
          'name'                  => '惠州市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000441300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 441400,
          'type'                  => 3,
          'name'                  => '梅州市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000441400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 441500,
          'type'                  => 3,
          'name'                  => '汕尾市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000441500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 441600,
          'type'                  => 3,
          'name'                  => '河源市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000441600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 441700,
          'type'                  => 3,
          'name'                  => '阳江市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000441700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 441800,
          'type'                  => 3,
          'name'                  => '清远市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000441800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 441900,
          'type'                  => 3,
          'name'                  => '东莞市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000441900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 442000,
          'type'                  => 3,
          'name'                  => '中山市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000442000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 445100,
          'type'                  => 3,
          'name'                  => '潮州市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000445100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 445200,
          'type'                  => 3,
          'name'                  => '揭阳市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000445200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 445300,
          'type'                  => 3,
          'name'                  => '云浮市',
          'parent_id'             => 440000,
          'inner_code'            => '000001440000445300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 450000,
          'type'                  => 2,
          'name'                  => '广西壮族自治区',
          'parent_id'             => 1,
          'inner_code'            => '000001450000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 450100,
          'type'                  => 3,
          'name'                  => '南宁市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000450100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 450200,
          'type'                  => 3,
          'name'                  => '柳州市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000450200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 450300,
          'type'                  => 3,
          'name'                  => '桂林市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000450300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 450400,
          'type'                  => 3,
          'name'                  => '梧州市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000450400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 450500,
          'type'                  => 3,
          'name'                  => '北海市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000450500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 450600,
          'type'                  => 3,
          'name'                  => '防城港市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000450600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 450700,
          'type'                  => 3,
          'name'                  => '钦州市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000450700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 450800,
          'type'                  => 3,
          'name'                  => '贵港市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000450800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 450900,
          'type'                  => 3,
          'name'                  => '玉林市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000450900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 451000,
          'type'                  => 3,
          'name'                  => '百色市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000451000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 451100,
          'type'                  => 3,
          'name'                  => '贺州市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000451100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 451200,
          'type'                  => 3,
          'name'                  => '河池市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000451200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 451300,
          'type'                  => 3,
          'name'                  => '来宾市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000451300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 451400,
          'type'                  => 3,
          'name'                  => '崇左市',
          'parent_id'             => 450000,
          'inner_code'            => '000001450000451400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 460000,
          'type'                  => 2,
          'name'                  => '海南省',
          'parent_id'             => 1,
          'inner_code'            => '000001460000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 460100,
          'type'                  => 3,
          'name'                  => '海口市',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000460100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 460200,
          'type'                  => 3,
          'name'                  => '三亚市',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000460200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469001,
          'type'                  => 3,
          'name'                  => '五指山市',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469001',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469002,
          'type'                  => 3,
          'name'                  => '琼海市',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469002',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469003,
          'type'                  => 3,
          'name'                  => '儋州市',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469003',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469005,
          'type'                  => 3,
          'name'                  => '文昌市',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469005',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469006,
          'type'                  => 3,
          'name'                  => '万宁市',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469006',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469007,
          'type'                  => 3,
          'name'                  => '东方市',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469007',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469025,
          'type'                  => 3,
          'name'                  => '定安县',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469025',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469026,
          'type'                  => 3,
          'name'                  => '屯昌县',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469026',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469027,
          'type'                  => 3,
          'name'                  => '澄迈县',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469027',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469028,
          'type'                  => 3,
          'name'                  => '临高县',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469028',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469030,
          'type'                  => 3,
          'name'                  => '白沙黎族自治县',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469030',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469031,
          'type'                  => 3,
          'name'                  => '昌江黎族自治县',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469031',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469033,
          'type'                  => 3,
          'name'                  => '乐东黎族自治县',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469033',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469034,
          'type'                  => 3,
          'name'                  => '陵水黎族自治县',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469034',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469035,
          'type'                  => 3,
          'name'                  => '保亭黎族苗族自治县',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469035',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 469036,
          'type'                  => 3,
          'name'                  => '琼中黎族苗族自治县',
          'parent_id'             => 460000,
          'inner_code'            => '000001460000469036',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 500000,
          'type'                  => 2,
          'name'                  => '重庆',
          'parent_id'             => 1,
          'inner_code'            => '000001500000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 500100,
          'type'                  => 3,
          'name'                  => '重庆市',
          'parent_id'             => 500000,
          'inner_code'            => '000001500000500100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 510000,
          'type'                  => 2,
          'name'                  => '四川省',
          'parent_id'             => 1,
          'inner_code'            => '000001510000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 510100,
          'type'                  => 3,
          'name'                  => '成都市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000510100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 510300,
          'type'                  => 3,
          'name'                  => '自贡市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000510300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 510400,
          'type'                  => 3,
          'name'                  => '攀枝花市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000510400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 510500,
          'type'                  => 3,
          'name'                  => '泸州市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000510500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 510600,
          'type'                  => 3,
          'name'                  => '德阳市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000510600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 510700,
          'type'                  => 3,
          'name'                  => '绵阳市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000510700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 510800,
          'type'                  => 3,
          'name'                  => '广元市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000510800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 510900,
          'type'                  => 3,
          'name'                  => '遂宁市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000510900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 511000,
          'type'                  => 3,
          'name'                  => '内江市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000511000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 511100,
          'type'                  => 3,
          'name'                  => '乐山市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000511100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 511300,
          'type'                  => 3,
          'name'                  => '南充市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000511300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 511400,
          'type'                  => 3,
          'name'                  => '眉山市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000511400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 511500,
          'type'                  => 3,
          'name'                  => '宜宾市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000511500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 511600,
          'type'                  => 3,
          'name'                  => '广安市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000511600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 511700,
          'type'                  => 3,
          'name'                  => '达州市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000511700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 511800,
          'type'                  => 3,
          'name'                  => '雅安市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000511800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 511900,
          'type'                  => 3,
          'name'                  => '巴中市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000511900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 512000,
          'type'                  => 3,
          'name'                  => '资阳市',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000512000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 513200,
          'type'                  => 3,
          'name'                  => '阿坝藏族羌族自治州',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000513200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 513300,
          'type'                  => 3,
          'name'                  => '甘孜藏族自治州',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000513300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 513400,
          'type'                  => 3,
          'name'                  => '凉山彝族自治州',
          'parent_id'             => 510000,
          'inner_code'            => '000001510000513400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 520000,
          'type'                  => 2,
          'name'                  => '贵州省',
          'parent_id'             => 1,
          'inner_code'            => '000001520000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 520100,
          'type'                  => 3,
          'name'                  => '贵阳市',
          'parent_id'             => 520000,
          'inner_code'            => '000001520000520100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 520200,
          'type'                  => 3,
          'name'                  => '六盘水市',
          'parent_id'             => 520000,
          'inner_code'            => '000001520000520200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 520300,
          'type'                  => 3,
          'name'                  => '遵义市',
          'parent_id'             => 520000,
          'inner_code'            => '000001520000520300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 520400,
          'type'                  => 3,
          'name'                  => '安顺市',
          'parent_id'             => 520000,
          'inner_code'            => '000001520000520400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 522200,
          'type'                  => 3,
          'name'                  => '铜仁地区',
          'parent_id'             => 520000,
          'inner_code'            => '000001520000522200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 522300,
          'type'                  => 3,
          'name'                  => '黔西南布依族苗族自治州',
          'parent_id'             => 520000,
          'inner_code'            => '000001520000522300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 522400,
          'type'                  => 3,
          'name'                  => '毕节地区',
          'parent_id'             => 520000,
          'inner_code'            => '000001520000522400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 522600,
          'type'                  => 3,
          'name'                  => '黔东南苗族侗族自治州',
          'parent_id'             => 520000,
          'inner_code'            => '000001520000522600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 522700,
          'type'                  => 3,
          'name'                  => '黔南布依族苗族自治州',
          'parent_id'             => 520000,
          'inner_code'            => '000001520000522700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 530000,
          'type'                  => 2,
          'name'                  => '云南省',
          'parent_id'             => 1,
          'inner_code'            => '000001530000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 530100,
          'type'                  => 3,
          'name'                  => '昆明市',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000530100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 530300,
          'type'                  => 3,
          'name'                  => '曲靖市',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000530300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 530400,
          'type'                  => 3,
          'name'                  => '玉溪市',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000530400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 530500,
          'type'                  => 3,
          'name'                  => '保山市',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000530500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 530600,
          'type'                  => 3,
          'name'                  => '昭通市',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000530600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 530700,
          'type'                  => 3,
          'name'                  => '丽江市',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000530700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 530800,
          'type'                  => 3,
          'name'                  => '普洱市',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000530800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 530900,
          'type'                  => 3,
          'name'                  => '临沧市',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000530900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 532300,
          'type'                  => 3,
          'name'                  => '楚雄彝族自治州',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000532300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 532500,
          'type'                  => 3,
          'name'                  => '红河哈尼族彝族自治州',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000532500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 532600,
          'type'                  => 3,
          'name'                  => '文山壮族苗族自治州',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000532600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 532800,
          'type'                  => 3,
          'name'                  => '西双版纳傣族自治州',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000532800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 532900,
          'type'                  => 3,
          'name'                  => '大理白族自治州',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000532900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 533100,
          'type'                  => 3,
          'name'                  => '德宏傣族景颇族自治州',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000533100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 533300,
          'type'                  => 3,
          'name'                  => '怒江傈僳族自治州',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000533300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 533400,
          'type'                  => 3,
          'name'                  => '迪庆藏族自治州',
          'parent_id'             => 530000,
          'inner_code'            => '000001530000533400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 540000,
          'type'                  => 2,
          'name'                  => '西藏自治区',
          'parent_id'             => 1,
          'inner_code'            => '000001540000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 540100,
          'type'                  => 3,
          'name'                  => '拉萨市',
          'parent_id'             => 540000,
          'inner_code'            => '000001540000540100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 542100,
          'type'                  => 3,
          'name'                  => '昌都地区',
          'parent_id'             => 540000,
          'inner_code'            => '000001540000542100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 542200,
          'type'                  => 3,
          'name'                  => '山南地区',
          'parent_id'             => 540000,
          'inner_code'            => '000001540000542200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 542300,
          'type'                  => 3,
          'name'                  => '日喀则地区',
          'parent_id'             => 540000,
          'inner_code'            => '000001540000542300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 542400,
          'type'                  => 3,
          'name'                  => '那曲地区',
          'parent_id'             => 540000,
          'inner_code'            => '000001540000542400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 542500,
          'type'                  => 3,
          'name'                  => '阿里地区',
          'parent_id'             => 540000,
          'inner_code'            => '000001540000542500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 542600,
          'type'                  => 3,
          'name'                  => '林芝地区',
          'parent_id'             => 540000,
          'inner_code'            => '000001540000542600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 610000,
          'type'                  => 2,
          'name'                  => '陕西省',
          'parent_id'             => 1,
          'inner_code'            => '000001610000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 610100,
          'type'                  => 3,
          'name'                  => '西安市',
          'parent_id'             => 610000,
          'inner_code'            => '000001610000610100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 610200,
          'type'                  => 3,
          'name'                  => '铜川市',
          'parent_id'             => 610000,
          'inner_code'            => '000001610000610200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 610300,
          'type'                  => 3,
          'name'                  => '宝鸡市',
          'parent_id'             => 610000,
          'inner_code'            => '000001610000610300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 610400,
          'type'                  => 3,
          'name'                  => '咸阳市',
          'parent_id'             => 610000,
          'inner_code'            => '000001610000610400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 610500,
          'type'                  => 3,
          'name'                  => '渭南市',
          'parent_id'             => 610000,
          'inner_code'            => '000001610000610500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 610600,
          'type'                  => 3,
          'name'                  => '延安市',
          'parent_id'             => 610000,
          'inner_code'            => '000001610000610600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 610700,
          'type'                  => 3,
          'name'                  => '汉中市',
          'parent_id'             => 610000,
          'inner_code'            => '000001610000610700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 610800,
          'type'                  => 3,
          'name'                  => '榆林市',
          'parent_id'             => 610000,
          'inner_code'            => '000001610000610800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 610900,
          'type'                  => 3,
          'name'                  => '安康市',
          'parent_id'             => 610000,
          'inner_code'            => '000001610000610900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 611000,
          'type'                  => 3,
          'name'                  => '商洛市',
          'parent_id'             => 610000,
          'inner_code'            => '000001610000611000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 620000,
          'type'                  => 2,
          'name'                  => '甘肃省',
          'parent_id'             => 1,
          'inner_code'            => '000001620000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 620100,
          'type'                  => 3,
          'name'                  => '兰州市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000620100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 620200,
          'type'                  => 3,
          'name'                  => '嘉峪关市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000620200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 620300,
          'type'                  => 3,
          'name'                  => '金昌市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000620300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 620400,
          'type'                  => 3,
          'name'                  => '白银市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000620400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 620500,
          'type'                  => 3,
          'name'                  => '天水市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000620500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 620600,
          'type'                  => 3,
          'name'                  => '武威市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000620600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 620700,
          'type'                  => 3,
          'name'                  => '张掖市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000620700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 620800,
          'type'                  => 3,
          'name'                  => '平凉市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000620800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 620900,
          'type'                  => 3,
          'name'                  => '酒泉市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000620900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 621000,
          'type'                  => 3,
          'name'                  => '庆阳市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000621000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 621100,
          'type'                  => 3,
          'name'                  => '定西市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000621100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 621200,
          'type'                  => 3,
          'name'                  => '陇南市',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000621200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 622900,
          'type'                  => 3,
          'name'                  => '临夏回族自治州',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000622900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 623000,
          'type'                  => 3,
          'name'                  => '甘南藏族自治州',
          'parent_id'             => 620000,
          'inner_code'            => '000001620000623000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 630000,
          'type'                  => 2,
          'name'                  => '青海省',
          'parent_id'             => 1,
          'inner_code'            => '000001630000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 630100,
          'type'                  => 3,
          'name'                  => '西宁市',
          'parent_id'             => 630000,
          'inner_code'            => '000001630000630100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 632100,
          'type'                  => 3,
          'name'                  => '海东地区',
          'parent_id'             => 630000,
          'inner_code'            => '000001630000632100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 632200,
          'type'                  => 3,
          'name'                  => '海北藏族自治州',
          'parent_id'             => 630000,
          'inner_code'            => '000001630000632200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 632300,
          'type'                  => 3,
          'name'                  => '黄南藏族自治州',
          'parent_id'             => 630000,
          'inner_code'            => '000001630000632300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 632500,
          'type'                  => 3,
          'name'                  => '海南藏族自治州',
          'parent_id'             => 630000,
          'inner_code'            => '000001630000632500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 632600,
          'type'                  => 3,
          'name'                  => '果洛藏族自治州',
          'parent_id'             => 630000,
          'inner_code'            => '000001630000632600',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 632700,
          'type'                  => 3,
          'name'                  => '玉树藏族自治州',
          'parent_id'             => 630000,
          'inner_code'            => '000001630000632700',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 632800,
          'type'                  => 3,
          'name'                  => '海西蒙古族藏族自治州',
          'parent_id'             => 630000,
          'inner_code'            => '000001630000632800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 640000,
          'type'                  => 2,
          'name'                  => '宁夏回族自治区',
          'parent_id'             => 1,
          'inner_code'            => '000001640000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 640100,
          'type'                  => 3,
          'name'                  => '银川市',
          'parent_id'             => 640000,
          'inner_code'            => '000001640000640100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 640200,
          'type'                  => 3,
          'name'                  => '石嘴山市',
          'parent_id'             => 640000,
          'inner_code'            => '000001640000640200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 640300,
          'type'                  => 3,
          'name'                  => '吴忠市',
          'parent_id'             => 640000,
          'inner_code'            => '000001640000640300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 640400,
          'type'                  => 3,
          'name'                  => '固原市',
          'parent_id'             => 640000,
          'inner_code'            => '000001640000640400',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 640500,
          'type'                  => 3,
          'name'                  => '中卫市',
          'parent_id'             => 640000,
          'inner_code'            => '000001640000640500',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 650000,
          'type'                  => 2,
          'name'                  => '新疆维吾尔自治区',
          'parent_id'             => 1,
          'inner_code'            => '000001650000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 650100,
          'type'                  => 3,
          'name'                  => '乌鲁木齐市',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000650100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 650200,
          'type'                  => 3,
          'name'                  => '克拉玛依市',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000650200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 652100,
          'type'                  => 3,
          'name'                  => '吐鲁番地区',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000652100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 652200,
          'type'                  => 3,
          'name'                  => '哈密地区',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000652200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 652300,
          'type'                  => 3,
          'name'                  => '昌吉回族自治州',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000652300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 652800,
          'type'                  => 3,
          'name'                  => '巴音郭楞蒙古自治州',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000652800',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 652900,
          'type'                  => 3,
          'name'                  => '阿克苏地区',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000652900',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 653000,
          'type'                  => 3,
          'name'                  => '克孜勒苏柯尔克孜自治州',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000653000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 653100,
          'type'                  => 3,
          'name'                  => '喀什地区',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000653100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 653200,
          'type'                  => 3,
          'name'                  => '和田地区',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000653200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 654000,
          'type'                  => 3,
          'name'                  => '伊犁哈萨克自治州',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000654000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 654200,
          'type'                  => 3,
          'name'                  => '塔城地区',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000654200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 654300,
          'type'                  => 3,
          'name'                  => '阿勒泰地区',
          'parent_id'             => 650000,
          'inner_code'            => '000001650000654300',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 710000,
          'type'                  => 2,
          'name'                  => '台湾省',
          'parent_id'             => 1,
          'inner_code'            => '000001710000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 710100,
          'type'                  => 3,
          'name'                  => '台湾',
          'parent_id'             => 710000,
          'inner_code'            => '000001710000710100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 810000,
          'type'                  => 2,
          'name'                  => '香港特别行政区',
          'parent_id'             => 1,
          'inner_code'            => '000001810000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 810100,
          'type'                  => 3,
          'name'                  => '香港岛',
          'parent_id'             => 810000,
          'inner_code'            => '000001810000810100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 820000,
          'type'                  => 2,
          'name'                  => '澳门特别行政区',
          'parent_id'             => 1,
          'inner_code'            => '000001820000',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 820100,
          'type'                  => 3,
          'name'                  => '澳门半岛',
          'parent_id'             => 820000,
          'inner_code'            => '000001820000820100',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        DB::table('inf_area')->insert([
          'id'                    => 820200,
          'type'                  => 3,
          'name'                  => '离岛',
          'parent_id'             => 820000,
          'inner_code'            => '000001820000820200',
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

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
          'id'                    => 4,
          'team_name'             => '财务',
          'is_administrator'      => 0,
          'status'                => 1,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('def_game_line')->insert([
          'id'                    => 1,
          'name'                  => '返奖率30%游戏组',
          'main_game_plat_code'   => 'cq97,pp7,jp7,habanero7,fc7,jdb7,jili7',
          'rate'                  => 30,
          'is_point_kill'         => 1,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('def_game_line')->insert([
          'id'                    => 2,
          'name'                  => '返奖率90%游戏组',
          'main_game_plat_code'   => 'cq95,pp5,jp5,habanero5,fc5,jdb5,jili5',
          'rate'                  => 90,
          'is_point_kill'         => 0,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('def_game_line')->insert([
          'id'                    => 3,
          'name'                  => '返奖率92%游戏组',
          'main_game_plat_code'   => 'cq98,pp8,jp8,habanero8,fc8,jdb8,jili8',
          'rate'                  => 92,
          'is_point_kill'         => 0,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('def_game_line')->insert([
          'id'                    => 4,
          'name'                  => '返奖率94%游戏组',
          'main_game_plat_code'   => 'cq99,pp9,jp9,habanero9,fc9,jdb9,jili9',
          'rate'                  => 94,
          'is_point_kill'         => 0,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);
        
        //文件操作
    /*    $file     = dirname(dirname(dirname(__FILE__))).'/config/lottery.php';
        $myfile   = fopen($file, "r+");
        $lotterys = ['SSC','11X5','LF','LHC','PK10','PCB','K3'];
        $str      = "<?php"."\n\n\n";
        $str     .= "return [\n\n";
        for($i=1800; $i<=1990; $i++){
            $str.="  '".$i."' => ["."\n";
            foreach ($lotterys as $value) {
                $str.='    ['."\n";
                $str.='      "game_group_code" => "'.$value.'",'."\n";
                $str.='      "prize_mode_id" => 1,'."\n";
                $str.='      "max_series" => '.$i.','."\n";
                $str.='      "max_bet_series" => '.$i.','."\n";
                $str.='      "min_series" => 1800,'."\n";
                $str.='      "default_series" => 1800'."\n";
                $str.='    ],'."\n";;
            }
            $str.='  ],'."\n";;
        }
        
        $str .='];';
       
        fwrite($myfile, $str);
        fclose($myfile);
        */
        //文件操作结束
    }

}
