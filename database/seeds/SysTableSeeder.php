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
        //语言
        DB::table('def_language')->insert([
            'id'                     => 1,
            'name'                   => 'zh',
            'zh_name'                => '汉语',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);
        DB::table('def_language')->insert([
            'id'                     => 2,
            'name'                   => 'en',
            'zh_name'                => '英语',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('def_language')->insert([
            'id'                     => 2,
            'name'                   => 'pt',
            'zh_name'                => '萄葡牙语',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('def_language')->insert([
            'id'                     => 3,
            'name'                   => 'es',
            'zh_name'                => '西班牙语',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('def_language')->insert([
            'id'                     => 4,
            'name'                   => 'vi',
            'zh_name'                => '越南语',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        //币种
        DB::table('def_currency')->insert([
            'id'                     => 1,
            'name'                   => 'CNY',
            'zh_name'                => '人民币',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);
        DB::table('def_currency')->insert([
            'id'                     => 2,
            'name'                   => 'BRL',
            'zh_name'                => '巴西雷亚尔',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('def_currency')->insert([
            'id'                     => 3,
            'name'                   => 'MNX',
            'zh_name'                => '墨西哥比索',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('def_currency')->insert([
            'id'                     => 4,
            'name'                   => 'VNDK',
            'zh_name'                => '越南盾',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('def_currency')->insert([
            'id'                     => 5,
            'name'                   => 'AUD',
            'zh_name'                => '澳大利亚元',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('def_currency')->insert([
            'id'                     => 6,
            'name'                   => 'PHP',
            'zh_name'                => '菲律宾比索',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('def_currency')->insert([
            'id'                     => 7,
            'name'                   => 'MYR',
            'zh_name'                => '马来西亚林吉特',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('def_currency')->insert([
            'id'                     => 8,
            'name'                   => 'ZAR',
            'zh_name'                => '南非兰特',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        //三方钱包
        DB::table('def_third_wallet')->insert([
            'id'                     => 1,
            'name'                   => 'Trc20',
            'currency'               => 'USDT',
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
            'currency'               => 'USDT',
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
            'currency'               => 'USDT',
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

        DB::table('inf_carrier_service_team')->insert([
          'id'                    => 1,
          'team_name'             => '超级管理员',
          'is_administrator'      => 1,
          'status'                => 1,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_carrier_service_team')->insert([
          'id'                    => 2,
          'team_name'             => '客服',
          'is_administrator'      => 0,
          'status'                => 1,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_carrier_service_team')->insert([
          'id'                    => 3,
          'team_name'             => '风控',
          'is_administrator'      => 0,
          'status'                => 1,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('inf_carrier_service_team')->insert([
          'id'                    => 4,
          'team_name'             => '财务',
          'is_administrator'      => 0,
          'created_at'            =>  date('Y-m-d H:i:s'),
          'updated_at'            =>  date('Y-m-d H:i:s'),
        ]);

        DB::table('def_game_line')->insert([
          'id'                    => 1,
          'name'                  => '返奖率50%游戏组',
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
    }

}
