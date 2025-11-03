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
        DB::table('def_language')->insert([
            'id'                     => 1,
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

        //巴西雷亚尔的兑换比例
        DB::table('conf_currency_web_site')->insert([
            'id'                     => 1,
            'currency'               => 'BRL',
            'sign'                   => 'digital_rate',
            'value'                  => '7',
            'remark'                 => '存款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 2,
            'currency'               => 'BRL',
            'sign'                   => 'withdraw_digital_rate',
            'value'                  => '7',
            'remark'                 => '取款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 3,
            'currency'               => 'BRL',
            'sign'                   => 'in_brl_out_u',
            'value'                  => '7',
            'remark'                 => '进雷亚尔出U',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

       //墨西哥比索的兑换比例
        DB::table('conf_currency_web_site')->insert([
            'id'                     => 4,
            'currency'               => 'MNX',
            'sign'                   => 'digital_rate',
            'value'                  => '7',
            'remark'                 => '存款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 5,
            'currency'               => 'MNX',
            'sign'                   => 'withdraw_digital_rate',
            'value'                  => '7',
            'remark'                 => '取款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 6,
            'currency'               => 'MNX',
            'sign'                   => 'in_mnx_out_u',
            'value'                  => '7',
            'remark'                 => '进比索出U',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);
        //越南盾的兑换比例
        DB::table('conf_currency_web_site')->insert([
            'id'                     => 7,
            'currency'               => 'VNDK',
            'sign'                   => 'digital_rate',
            'value'                  => '7',
            'remark'                 => '存款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 8,
            'currency'               => 'VNDK',
            'sign'                   => 'withdraw_digital_rate',
            'value'                  => '7',
            'remark'                 => '取款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 9,
            'currency'               => 'VNDK',
            'sign'                   => 'in_vndk_out_u',
            'value'                  => '7',
            'remark'                 => '进越南盾出U',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        //澳大利亚元的兑换比例
        DB::table('conf_currency_web_site')->insert([
            'id'                     => 10,
            'currency'               => 'AUD',
            'sign'                   => 'digital_rate',
            'value'                  => '7',
            'remark'                 => '存款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 11,
            'currency'               => 'AUD',
            'sign'                   => 'withdraw_digital_rate',
            'value'                  => '7',
            'remark'                 => '取款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 12,
            'currency'               => 'AUD',
            'sign'                   => 'in_vud_out_u',
            'value'                  => '7',
            'remark'                 => '进澳大利亚元出U',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        //菲律宾比索的兑换比例
        DB::table('conf_currency_web_site')->insert([
            'id'                     => 13,
            'currency'               => 'PHP',
            'sign'                   => 'digital_rate',
            'value'                  => '7',
            'remark'                 => '存款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 14,
            'currency'               => 'PHP',
            'sign'                   => 'withdraw_digital_rate',
            'value'                  => '7',
            'remark'                 => '取款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 15,
            'currency'               => 'PHP',
            'sign'                   => 'in_php_out_u',
            'value'                  => '7',
            'remark'                 => '进比索出U',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        //马来西亚林吉特的兑换比例
        DB::table('conf_currency_web_site')->insert([
            'id'                     => 16,
            'currency'               => 'MYR',
            'sign'                   => 'digital_rate',
            'value'                  => '7',
            'remark'                 => '存款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 17,
            'currency'               => 'MYR',
            'sign'                   => 'withdraw_digital_rate',
            'value'                  => '7',
            'remark'                 => '取款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 18,
            'currency'               => 'MYR',
            'sign'                   => 'in_myr_out_u',
            'value'                  => '7',
            'remark'                 => '进林吉特出U',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        //南非兰特的兑换比例
        DB::table('conf_currency_web_site')->insert([
            'id'                     => 19,
            'currency'               => 'ZAR',
            'sign'                   => 'digital_rate',
            'value'                  => '7',
            'remark'                 => '存款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 20,
            'currency'               => 'ZAR',
            'sign'                   => 'withdraw_digital_rate',
            'value'                  => '7',
            'remark'                 => '取款数字币汇率',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);

        DB::table('conf_currency_web_site')->insert([
            'id'                     => 21,
            'currency'               => 'ZAR',
            'sign'                   => 'in_myr_out_u',
            'value'                  => '7',
            'remark'                 => '进南非出U',
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
