<?php

use Illuminate\Database\Seeder;

class RabcTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('permission_group')->insert([
            'id'                     => 73,
            'group_name'             => '首页',
            'sort'                   => 0,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 74,
            'group_name'             => '首页',
            'sort'                   => 0,
            'parent_id'              => 73,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 1,
            'group_name'             => '用户管理',
            'sort'                   => 1,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 14,
            'group_name'             => '所有用户列表',
            'sort'                   => 14,
            'parent_id'              => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 15,
            'group_name'             => '用户等级设置',
            'sort'                   => 15,
            'parent_id'              => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 16,
            'group_name'             => '用户层级设置',
            'sort'                   => 16,
            'parent_id'              => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 17,
            'group_name'             => '推广域名设置',
            'sort'                   => 17,
            'parent_id'              => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 2,
            'group_name'             => '用户资金',
            'sort'                   => 2,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 18,
            'group_name'             => '用户存款审核',
            'sort'                   => 1,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 19,
            'group_name'             => '线上存款记录',
            'sort'                   => 2,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 70,
            'group_name'             => '用户取款审核',
            'sort'                   => 4,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 20,
            'group_name'             => '线上取款记录',
            'sort'                   => 5,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 77,
            'group_name'             => '活动礼金记录',
            'sort'                   => 7,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 21,
            'group_name'             => '用户帐变记录',
            'sort'                   => 8,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 22,
            'group_name'             => '流水限制汇总',
            'sort'                   => 9,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 23,
            'group_name'             => '转帐未知处理',
            'sort'                   => 10,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 25,
            'group_name'             => '用户分红记录',
            'sort'                   => 12,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 3,
            'group_name'             => '游戏管理',
            'sort'                   => 4,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 26,
            'group_name'             => '彩种列表',
            'sort'                   => 1,
            'parent_id'              => 3,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 27,
            'group_name'             => '彩种分组列表',
            'sort'                   => 2,
            'parent_id'              => 3,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 28,
            'group_name'             => '彩种码数限制',
            'sort'                   => 3,
            'parent_id'              => 3,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 29,
            'group_name'             => '彩种金额限制',
            'sort'                   => 4,
            'parent_id'              => 3,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 30,
            'group_name'             => '三方游戏设置',
            'sort'                   => 5,
            'parent_id'              => 3,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 31,
            'group_name'             => '三方投注明细',
            'sort'                   => 6,
            'parent_id'              => 3,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

         DB::table('permission_group')->insert([
            'id'                     => 32,
            'group_name'             => '彩票投注列表',
            'sort'                   => 7,
            'parent_id'              => 3,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 33,
            'group_name'             => '游戏设置',
            'sort'                   => 8,
            'parent_id'              => 3,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 4,
            'group_name'             => '聊天室管理',
            'sort'                   => 5,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 34,
            'group_name'             => '用户列表',
            'sort'                   => 1,
            'parent_id'              => 4,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 35,
            'group_name'             => '消息列表',
            'sort'                   => 2,
            'parent_id'              => 4,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 36,
            'group_name'             => '管理员列表',
            'sort'                   => 3,
            'parent_id'              => 4,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 37,
            'group_name'             => '房间列表',
            'sort'                   => 4,
            'parent_id'              => 4,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 38,
            'group_name'             => '公告列表',
            'sort'                   => 5,
            'parent_id'              => 4,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 39,
            'group_name'             => '聊天室设置',
            'sort'                   => 6,
            'parent_id'              => 4,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 45,
            'group_name'             => '投注机器人列表',
            'sort'                   => 7,
            'parent_id'              => 4,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 6,
            'group_name'             => '优惠活动',
            'sort'                   => 6,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 46,
            'group_name'             => '优惠活动管理',
            'sort'                   => 1,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 47,
            'group_name'             => '活动审核管理',
            'sort'                   => 2,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 48,
            'group_name'             => '活动资金统计',
            'sort'                   => 3,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 49,
            'group_name'             => '活动审核历史',
            'sort'                   => 4,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 50,
            'group_name'             => '轮盘活动管理',
            'sort'                   => 5,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 51,
            'group_name'             => '轮盘参与人员列表',
            'sort'                   => 6,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 79,
            'group_name'             => '注册赠送人员列表',
            'sort'                   => 8,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 81,
            'group_name'             => '签到活动人员列表',
            'sort'                   => 10,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 82,
            'group_name'             => '直属充值推广设置',
            'sort'                   => 11,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 83,
            'group_name'             => '直属充值推广人员列表',
            'sort'                   => 11,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 84,
            'group_name'             => '注册满送设置',
            'sort'                   => 13,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 85,
            'group_name'             => '注册满送人员列表',
            'sort'                   => 14,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 86,
            'group_name'             => '全民推广设置',
            'sort'                   => 15,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 87,
            'group_name'             => '全民推广人员列表',
            'sort'                   => 16,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 88,
            'group_name'             => '亏损金设置',
            'sort'                   => 17,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 89,
            'group_name'             => '亏损金人员列表',
            'sort'                   => 18,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 105,
            'group_name'             => '体验券设置',
            'sort'                   => 19,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 106,
            'group_name'             => '体验券领取人员列表',
            'sort'                   => 20,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 90,
            'group_name'             => '多次存款设置',
            'sort'                   => 21,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 91,
            'group_name'             => '多次存款人员列表',
            'sort'                   => 22,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 92,
            'group_name'             => '闯关人员列表',
            'sort'                   => 24,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 7,
            'group_name'             => '日志报表',
            'sort'                   => 9,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 52,
            'group_name'             => '个人日报表',
            'sort'                   => 1,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 53,
            'group_name'             => '个人总报表',
            'sort'                   => 2,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 93,
            'group_name'             => '团队日报表',
            'sort'                   => 3,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 94,
            'group_name'             => '团队总报表',
            'sort'                   => 4,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 54,
            'group_name'             => '公司盈亏报表',
            'sort'                   => 5,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 95,
            'group_name'             => '公司盈亏月表',
            'sort'                   => 10,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 55,
            'group_name'             => '游戏平台报表',
            'sort'                   => 7,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 96,
            'group_name'             => '彩票平台报表',
            'sort'                   => 11,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 97,
            'group_name'             => '用户彩票盈亏',
            'sort'                   => 12,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 107,
            'group_name'             => '额度变更列表',
            'sort'                   => 15,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 98,
            'group_name'             => '会员返水列表',
            'sort'                   => 13,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 99,
            'group_name'             => '用户操作日志',
            'sort'                   => 15,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 8,
            'group_name'             => '系统资金',
            'sort'                   => 10,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 56,
            'group_name'             => '代收绑定',
            'sort'                   => 1,
            'parent_id'              => 8,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 57,
            'group_name'             => '代收设置',
            'sort'                   => 2,
            'parent_id'              => 8,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 100,
            'group_name'             => '代付绑定',
            'sort'                   => 3,
            'parent_id'              => 8,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 101,
            'group_name'             => '代付设置',
            'sort'                   => 4,
            'parent_id'              => 8,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 102,
            'group_name'             => '银行类型列表',
            'sort'                   => 5,
            'parent_id'              => 8,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 58,
            'group_name'             => '银行卡列表',
            'sort'                   => 6,
            'parent_id'              => 8,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 59,
            'group_name'             => '虚拟币地址',
            'sort'                   => 7,
            'parent_id'              => 8,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 9,
            'group_name'             => '系统设置',
            'sort'                   => 11,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 60,
            'group_name'             => '系统参数设置',
            'sort'                   => 1,
            'parent_id'              => 9,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 61,
            'group_name'             => '热门链接',
            'sort'                   => 3,
            'parent_id'              => 9,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 103,
            'group_name'             => '联系我们',
            'sort'                   => 3,
            'parent_id'              => 9,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 10,
            'group_name'             => '系统消息',
            'sort'                   => 12,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 62,
            'group_name'             => '用户消息',
            'sort'                   => 1,
            'parent_id'              => 10,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 11,
            'group_name'             => '图文管理',
            'sort'                   => 13,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 63,
            'group_name'             => '广告图片管理',
            'sort'                   => 1,
            'parent_id'              => 11,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 64,
            'group_name'             => '文章列表',
            'sort'                   => 2,
            'parent_id'              => 11,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('permission_group')->insert([
            'id'                     => 13,
            'group_name'             => '角色管理',
            'sort'                   => 13,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 66,
            'group_name'             => '角色管理',
            'sort'                   => 1,
            'parent_id'              => 13,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 67,
            'group_name'             => '员工管理',
            'sort'                   => 2,
            'parent_id'              => 13,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 104,
            'group_name'             => '访问日志',
            'sort'                   => 5,
            'parent_id'              => 13,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 108,
            'group_name'             => '套利银行卡列表',
            'sort'                   => 15,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 109,
            'group_name'             => '特邀手机号查询',
            'sort'                   => 9,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 110,
            'group_name'             => '累存人员列表',
            'sort'                   => 25,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 112,
            'group_name'             => '代收分类列表',
            'sort'                   => 5,
            'parent_id'              => 8,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 113,
            'group_name'             => '管理员登录日志',
            'sort'                   => 5,
            'parent_id'              => 13,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 114,
            'group_name'             => '充值分红活动设置',
            'sort'                   => 5,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 115,
            'group_name'             => '提现分红活动设置',
            'sort'                   => 5,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 117,
            'group_name'             => '用户返佣记录',
            'sort'                   => 11,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 118,
            'group_name'             => '代理日报表',
            'sort'                   => 5,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('permission_group')->insert([
            'id'                     => 120,
            'group_name'             => '代理优惠活动',
            'sort'                   => 7,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('permission_group')->insert([
            'id'                     => 121,
            'group_name'             => '代理转介绍礼金设置',
            'sort'                   => 15,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 122,
            'group_name'             => '邀请首存奖励设置',
            'sort'                   => 15,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 123,
            'group_name'             => '邀请首存奖励列表',
            'sort'                   => 16,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 124,
            'group_name'             => '问题列表',
            'sort'                   => 3,
            'parent_id'              => 11,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 125,
            'group_name'             => '反馈列表',
            'sort'                   => 4,
            'parent_id'              => 11,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 126,
            'group_name'             => '等级变更日志',
            'sort'                   => 14,
            'parent_id'              => 7,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 127,
            'group_name'             => '福利数据列表',
            'sort'                   => 26,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 128,
            'group_name'             => '首页弹窗',
            'sort'                   => 4,
            'parent_id'              => 9,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 129,
            'group_name'             => '负盈利加码设置',
            'sort'                   => 17,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 130,
            'group_name'             => '负盈利加码人员列表',
            'sort'                   => 18,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 131,
            'group_name'             => '代理扶持设置',
            'sort'                   => 19,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 132,
            'group_name'             => '游戏帐号列表',
            'sort'                   => 18,
            'parent_id'              => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 133,
            'group_name'             => '代理转介绍人员列表',
            'sort'                   => 16,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 134,
            'group_name'             => '横版菜单列表',
            'sort'                   => 5,
            'parent_id'              => 9,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 135,
            'group_name'             => '代理取款风控',
            'sort'                   => 7,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 136,
            'group_name'             => '业绩排行榜',
            'sort'                   => 27,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 137,
            'group_name'             => '流水佣金记录',
            'sort'                   => 14,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        DB::table('permission_group')->insert([
            'id'                     => 138,
            'group_name'             => '流水实时佣金',
            'sort'                   => 15,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 140,
            'group_name'             => '会员返佣列表',
            'sort'                   => 21,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 141,
            'group_name'             => '会员银行卡列表',
            'sort'                   => 19,
            'parent_id'              => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 142,
            'group_name'             => '会员数字币列表',
            'sort'                   => 20,
            'parent_id'              => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 143,
            'group_name'             => '运营管理',
            'sort'                   => 3,
            'parent_id'              => 0,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 144,
            'group_name'             => '数据监控',
            'sort'                   => 1,
            'parent_id'              => 143,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 145,
            'group_name'             => '库存记录',
            'sort'                   => 2,
            'parent_id'              => 143,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 146,
            'group_name'             => '体验券监控',
            'sort'                   => 3,
            'parent_id'              => 143,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 147,
            'group_name'             => '关卡设置',
            'sort'                   => 23,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 148,
            'group_name'             => '人头关卡设置',
            'sort'                   => 28,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]); 

        DB::table('permission_group')->insert([
            'id'                     => 149,
            'group_name'             => '人头费列表',
            'sort'                   => 29,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 150,
            'group_name'             => '游戏输赢分红设置',
            'sort'                   => 22,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 151,
            'group_name'             => '业绩分红设置',
            'sort'                   => 23,
            'parent_id'              => 120,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 152,
            'group_name'             => '业绩监控',
            'sort'                   => 4,
            'parent_id'              => 143,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 153,
            'group_name'             => '域名列表',
            'sort'                   => 6,
            'parent_id'              => 9,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 158,
            'group_name'             => '代理实时分红',
            'sort'                   => 13,
            'parent_id'              => 2,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]); 

        DB::table('permission_group')->insert([
            'id'                     => 159,
            'group_name'             => '回归礼金设置',
            'sort'                   => 30,
            'parent_id'              => 6,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]); 

        DB::table('permission_group')->insert([
            'id'                     => 160,
            'group_name'             => '公告管理',
            'sort'                   => 5,
            'parent_id'              => 11,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        DB::table('permission_group')->insert([
            'id'                     => 161,
            'group_name'             => '支付宝地址',
            'sort'                   => 21,
            'parent_id'              => 1,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);  

        DB::table('permissions')->insert([
         'id'           =>1,
         'group_id'     =>14,
         'name'         =>'carrier/playerlist',
         'frontroute'   =>'/memberList',
         'description'  =>'显示会员列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>2,
         'group_id'     =>14,
         'name'         =>'carrier/playerinfo',
         'description'  =>'会员信息详情',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>3,
         'group_id'     =>14,
         'name'         =>'carrier/updateplayerinfo',
         'description'  =>'更新会员信息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>4,
         'group_id'     =>14,
         'name'         =>'carrier/playertransferlist',
         'description'  =>'会员交易信息列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>5,
         'group_id'     =>14,
         'name'         =>'carrier/playerbalanceinfo',
         'description'  =>'查询会员余额',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>6,
         'group_id'     =>14,
         'name'         =>'carrier/playergameplats',
         'description'  =>'查询会员三方余额',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>7,
         'group_id'     =>14,
         'name'         =>'carrier/odds',
         'description'  =>'查询会员赔率返水',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>8,
         'group_id'     =>14,
         'name'         =>'carrier/setplayersalary',
         'description'  =>'设置会员赔率返水',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>9,
         'group_id'     =>14,
         'name'         =>'carrier/scorelist',
         'description'  =>'查询会员积分',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>10,
         'group_id'     =>14,
         'name'         =>'carrier/playerlogininfo',
         'description'  =>'查询会员登录日志',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>11,
         'group_id'     =>14,
         'name'         =>'carrier/playerbanklist',
         'description'  =>'查询会员银行卡',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>12,
         'group_id'     =>14,
         'name'         =>'carrier/playerexchangelist',
         'description'  =>'会员套利查询',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>13,
         'group_id'     =>14,
         'name'         =>'carrier/directlyunder',
         'description'  =>'查询会员直属下级',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>14,
         'group_id'     =>14,
         'name'         =>'carrier/allunder',
         'description'  =>'查询会员所有下级',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>15,
         'group_id'     =>14,
         'name'         =>'carrier/changeplayerpassword',
         'description'  =>'修改会员登录密码',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>17,
         'group_id'     =>14,
         'name'         =>'carrier/changeplayerfrozenstatus',
         'description'  =>'变更会员冻结状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>18,
         'group_id'     =>14,
         'name'         =>'carrier/bindbankcard',
         'description'  =>'绑定商家银行卡',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>22,
         'group_id'     =>14,
         'name'         =>'carrier/playergameaccountclear',
         'description'  =>'清除会员三方游戏帐号',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>23,
         'group_id'     =>14,
         'name'         =>'carrier/playergameplatlimit',
         'description'  =>'设置会员平台限制',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>24,
         'group_id'     =>15,
         'name'         =>'carrier/playerlevellist',
         'frontroute'   =>'/MemberLevelSet',
         'description'  =>'会员等级列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>25,
         'group_id'     =>15,
         'name'         =>'carrier/playerleveladd',
         'description'  =>'会员等级更新',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>26,
         'group_id'     =>15,
         'name'         =>'carrier/playerleveldel',
         'description'  =>'会员等级删除',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>27,
         'group_id'     =>15,
         'name'         =>'carrier/playerlevelthirdpaylist',
         'description'  =>'三方渠道列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>28,
         'group_id'     =>15,
         'name'         =>'carrier/playerlevelthirdpayupdate',
         'description'  =>'会员等级关联三方渠道更新',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>29,
         'group_id'     =>16,
         'name'         =>'carrier/playergradelist',
         'frontroute'   =>'/memberGradeSet',
         'description'  =>'会员层级列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>30,
         'group_id'     =>16,
         'name'         =>'carrier/playergradeadd',
         'description'  =>'新增/编辑会员层级',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>31,
         'group_id'     =>17,
         'name'         =>'carrier/playerinvitecodelist',
         'frontroute'   =>'/agentDomainSet',
         'description'  =>'代理域名列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>32,
         'group_id'     =>17,
         'name'         =>'carrier/updateplayerinvitecode',
         'description'  =>'更新代理域名',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>33,
         'group_id'     =>18,
         'name'         =>'carrier/depositauditlist',
         'frontroute'   =>'/depositAudit',
         'description'  =>'会员存款审核列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>34,
         'group_id'     =>18,
         'name'         =>'carrier/depositaudit',
         'description'  =>'会员存款审核',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>35,
         'group_id'     =>19,
         'name'         =>'carrier/depositlist',
         'frontroute'   =>'/depositRecord',
         'description'  =>'线上存款记录',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>36,
         'group_id'     =>70,
         'name'         =>'carrier/withdrawauditlist',
         'frontroute'   =>'/drawAudit',
         'description'  =>'会员取款审核列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>37,
         'group_id'     =>70,
         'name'         =>'carrier/withdrawaudit',
         'description'  =>'会员取款审核',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>38,
         'group_id'     =>70,
         'name'         =>'carrier/paymentonbehalf',
         'description'  =>'会员取款代付',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>40,
         'group_id'     =>20,
         'name'         =>'carrier/withdrawlist',
         'frontroute'   =>'/drawRecord',
         'description'  =>'用户取款列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>41,
         'group_id'     =>21,
         'name'         =>'carrier/transfertypelist',
         'description'  =>'用户帐变记录',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>42,
         'group_id'     =>22,
         'name'         =>'carrier/withdrawslimitlist',
         'frontroute'   =>'/flowLimitSummary',
         'description'  =>'流水限制汇总',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>43,
         'group_id'     =>22,
         'name'         =>'carrier/withdrawslimitcomplete',
         'description'  =>'完成流水限制',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>44,
         'group_id'     =>23,
         'name'         =>'carrier/playercasinotransferlist',
         'frontroute'   =>'/transferDispose',
         'description'  =>'转帐未知处理列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>194,
         'group_id'     =>23,
         'name'         =>'carrier/playercasinotransfercheck',
         'description'  =>'未知订单查询',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>46,
         'group_id'     =>25,
         'name'         =>'report/earnlinglist',
         'description'  =>'用户分红记录',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>47,
         'group_id'     =>25,
         'name'         =>'report/sendearnling',
         'description'  =>'发放分红',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>48,
         'group_id'     =>26,
         'name'         =>'lottery/lotterylist',
         'frontroute'   =>'/lotteryList',
         'description'  =>'彩票列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>49,
         'group_id'     =>26,
         'name'         =>'lottery/changelotterysealstatus',
         'description'  =>'变更彩票封盘状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>50,
         'group_id'     =>26,
         'name'         =>'lottery/lotterychangehot',
         'description'  =>'变更彩票热门状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>51,
         'group_id'     =>26,
         'name'         =>'lottery/lotteryChangeRecommend',
         'description'  =>'变更彩票推荐状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>52,
         'group_id'     =>26,
         'name'         =>'lottery/getwinatearr',
         'description'  =>'获取返水比例键值对',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>53,
         'group_id'     =>26,
         'name'         =>'lottery/changelotterystatus',
         'description'  =>'变更彩票状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>54,
         'group_id'     =>26,
         'name'         =>'lottery/playedgrouplist',
         'description'  =>'彩种玩法赔率列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>55,
         'group_id'     =>26,
         'name'         =>'lottery/lotteryupdate',
         'description'  =>'彩种更新',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>56,
         'group_id'     =>26,
         'name'         =>'fileupload/lottery',
         'description'  =>'彩种图片上传',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>57,
         'group_id'     =>26,
         'name'         =>'lottery/updatebatchplayedodds',
         'description'  =>'一键更新所有赔率',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>58,
         'group_id'     =>26,
         'name'         =>'lottery/oddsTest',
         'description'  =>'彩票模拟测试',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>59,
         'group_id'     =>26,
         'name'         =>'lottery/addpublottery',
         'description'  =>'添加官彩',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>60,
         'group_id'     =>26,
         'name'         =>'lottery/addprilottery',
         'description'  =>'添加私彩',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>61,
         'group_id'     =>26,
         'name'         =>'lottery/opendatalist',
         'description'  =>'彩种奖期列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>62,
         'group_id'     =>26,
         'name'         =>'lottery/updateopendata',
         'description'  =>'设置彩种开奖号码',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>63,
         'group_id'     =>26,
         'name'         =>'lottery/lotterybetblack',
         'description'  =>'彩种投注黑名单列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>64,
         'group_id'     =>26,
         'name'         =>'lottery/lotterybetblackadd',
         'description'  =>'彩种投注黑名单更新',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>65,
         'group_id'     =>26,
         'name'         =>'lottery/lotteryissueadd',
         'description'  =>'彩票生成奖期',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>66,
         'group_id'     =>26,
         'name'         =>'lottery/updateopentime',
         'description'  =>'彩票开奖时间更新',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>67,
         'group_id'     =>26,
         'name'         =>'lottery/opentimelist',
         'description'  =>'彩票开奖时间列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>68,
         'group_id'     =>26,
         'name'         =>'lottery/opendatamodel',
         'description'  =>'彩票开奖模式信息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>70,
         'group_id'     =>27,
         'name'         =>'lottery/lotterygrouplist',
         'frontroute'   =>'/loteryGroup',
         'description'  =>'彩种分组列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>71,
         'group_id'     =>27,
         'name'         =>'lottery/alllottery',
         'description'  =>'所有彩种列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>72,
         'group_id'     =>27,
         'name'         =>'lottery/lotterygroupadd',
         'description'  =>'变更彩票分组信息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>74,
         'group_id'     =>27,
         'name'         =>'lottery/lotterygroupdel',
         'description'  =>'删除彩票分组',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>75,
         'group_id'     =>28,
         'name'         =>'lottery/playergradelist',
         'description'  =>'彩票码数限制列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>76,
         'group_id'     =>28,
         'name'         =>'lottery/betcodelimitadd',
         'description'  =>'添加/编辑彩票码数限制',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>77,
         'group_id'     =>28,
         'name'         =>'lottery/betcodelimitchangestatus',
         'description'  =>'变更彩票码数限制状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>78,
         'group_id'     =>28,
         'name'         =>'lottery/betcodelimitdel',
         'description'  =>'删除彩票码数限制',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>79,
         'group_id'     =>29,
         'name'         =>'lottery/betamountlimitlist',
         'frontroute'   =>'/LimitMoney',
         'description'  =>'彩票金额限制列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>80,
         'group_id'     =>29,
         'name'         =>'lottery/betamountlimitadd',
         'description'  =>'新增/编辑彩票金额限制',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>81,
         'group_id'     =>29,
         'name'         =>'lottery/playedgrouplist',
         'description'  =>'彩票玩法组列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>83,
         'group_id'     =>30,
         'name'         =>'system/platlist',
         'frontroute'   =>'/gamePlatformSet',
         'description'  =>'三方游戏平台列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>84,
         'group_id'     =>30,
         'name'         =>'system/platsave',
         'description'  =>'游戏平台状态更新',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>85,
         'group_id'     =>30,
         'name'         =>'system/gamelist',
         'description'  =>'三方游戏列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>86,
         'group_id'     =>30,
         'name'         =>'system/changestatus',
         'description'  =>'变更三方游戏状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>87,
         'group_id'     =>30,
         'name'         =>'system/changehot',
         'description'  =>'变更三方游戏热门状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>88,
         'group_id'     =>30,
         'name'         =>'system/changerecommend',
         'description'  =>'变更三方游戏推荐状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>89,
         'group_id'     =>30,
         'name'         =>'system/gamesave',
         'description'  =>'更新游戏信息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>90,
         'group_id'     =>31,
         'name'         =>'carrier/betflowlist',
         'frontroute'   =>'/betRecord',
         'description'  =>'用户投注明细',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>91,
         'group_id'     =>32,
         'name'         =>'carrier/lotterybetlist',
         'frontroute'   =>'/LottRecord',
         'description'  =>'彩票投注列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>92,
         'group_id'     =>33,
         'name'         =>'system/websitesave',
         'description'  =>'更新网站基本信息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>93,
         'group_id'     =>33,
         'name'         =>'system/websiteinfo',
         'frontroute'   =>'/gameSet',
         'description'  =>'获取网站的基本信息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>94,
         'group_id'     =>34,
         'name'         =>'chat/memberlist',
         'frontroute'   =>'/chat_menmber_index',
         'description'  =>'房间用户列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>95,
         'group_id'     =>35,
         'name'         =>'chat/messagelist',
         'frontroute'   =>'/chat_message_index',
         'description'  =>'房间消息列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>96,
         'group_id'     =>35,
         'name'         =>'chat/delmessage',
         'description'  =>'删除消息列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>97,
         'group_id'     =>36,
         'name'         =>'chat/managerlist',
         'frontroute'   =>'/chat_manage_index',
         'description'  =>'管理员列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>98,
         'group_id'     =>36,
         'name'         =>'chat/changemanager',
         'description'  =>'取消聊天室管理员状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>99,
         'group_id'     =>37,
         'name'         =>'chat/roomlist',
         'frontroute'   =>'/chat_room_index',
         'description'  =>'聊天室房间列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>100,
         'group_id'     =>37,
         'name'         =>'chat/addroom',
         'description'  =>'添加聊天室房间',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>101,
         'group_id'     =>37,
         'name'         =>'chat/roomchangestatus',
         'description'  =>'变更聊天室房间状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>103,
         'group_id'     =>37,
         'name'         =>'chat/delroom',
         'description'  =>'变更聊天室房间状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>104,
         'group_id'     =>38,
         'name'         =>'chat/addnotice',
         'description'  =>'新增聊天室公告',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>105,
         'group_id'     =>38,
         'name'         =>'chat/delnotice',
         'description'  =>'删除聊天室公告',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>106,
         'group_id'     =>39,
         'name'         =>'lottery/thirdlotterylist',
         'description'  =>'三方彩票列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>107,
         'group_id'     =>39,
         'name'         =>'chat/configure',
         'frontroute'   =>'/chat_set_index',
         'description'  =>'查询聊天室配置',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>108,
         'group_id'     =>39,
         'name'         =>'chat/setting',
         'description'  =>'更新聊天室配置',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>122,
         'group_id'     =>45,
         'name'         =>'chat/addBetBot',
         'description'  =>'新增投注机器人',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>123,
         'group_id'     =>45,
         'name'         =>'chat/updateBetBot',
         'description'  =>'变更投注机器人状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>124,
         'group_id'     =>45,
         'name'         =>'chat/chatBetBotList',
         'frontroute'   =>'/botlist_bet',
         'description'  =>'投注机器人列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>125,
         'group_id'     =>46,
         'name'         =>'carrier/activitieslist',
         'frontroute'   =>'/activityManage',
         'description'  =>'优惠活动列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>126,
         'group_id'     =>46,
         'name'         =>'carrier/changeactivitystatus',
         'description'  =>'变更优惠活动状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>127,
         'group_id'     =>46,
         'name'         =>'carrier/activitiesimglist',
         'description'  =>'优惠活动编辑',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>128,
         'group_id'     =>46,
         'name'         =>'carrier/activitysavetwo',
         'description'  =>'优惠活动保存2',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>129,
         'group_id'     =>46,
         'name'         =>'carrier/activitysaveone',
         'description'  =>'优惠活动保存1',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>130,
         'group_id'     =>47,
         'name'         =>'carrier/activitiesauthlist',
         'frontroute'   =>'/auditManage',
         'description'  =>'优惠活动审核列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>131,
         'group_id'     =>47,
         'name'         =>'carrier/activitiesauth',
         'description'  =>'优惠活动审核',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>132,
         'group_id'     =>48,
         'name'         =>'carrier/activitiesreport',
         'frontroute'   =>'/fundStatistic',
         'description'  =>'优惠活动资金统计',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>133,
         'group_id'     =>49,
         'name'         =>'carrier/activitiesauthhistory',
         'frontroute'   =>'/auditHistory',
         'description'  =>'优惠活动审核历史',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>134,
         'group_id'     =>50,
         'name'         =>'carrier/activitiesluckdrawlist',
         'frontroute'   =>'/luckyRouletteManage',
         'description'  =>'轮盘活动列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>136,
         'group_id'     =>50,
         'name'         =>'carrier/activitiesluckdrawadd',
         'description'  =>'新增/更新轮盘活动',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>137,
         'group_id'     =>50,
         'name'         =>'carrier/activitiesluckdrawstatus',
         'description'  =>'变更轮盘活动状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>138,
         'group_id'     =>51,
         'name'         =>'carrier/activityplayerluckdrawlist',
         'frontroute'   =>'/playerLuckdrawManage',
         'description'  =>'轮盘参与人员列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>139,
         'group_id'     =>52,
         'name'         =>'report/statdaylist',
         'frontroute'   =>'/statdaylist',
         'description'  =>'用户日报表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>140,
         'group_id'     =>53,
         'name'         =>'report/totalstatdaylist',
         'frontroute'   =>'/statdaylist2',
         'description'  =>'用户总报表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>141,
         'group_id'     =>54,
         'name'         =>'report/winandloselist',
         'frontroute'   =>'/winandloselist',
         'description'  =>'商户盈亏报表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>142,
         'group_id'     =>55,
         'name'         =>'report/gameplatlist',
         'frontroute'   =>'/gameplatlist',
         'description'  =>'游戏平台报表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>152,
         'group_id'     =>58,
         'name'         =>'carrier/changecashbankstatus',
         'description'  =>'变更银行卡状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>153,
         'group_id'     =>58,
         'name'         =>'carrier/banktypelist',
         'description'  =>'银行类型列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>154,
         'group_id'     =>58,
         'name'         =>'carrier/cashbankadd',
         'description'  =>'新增银行卡',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>155,
         'group_id'     =>58,
         'name'         =>'carrier/cashbanklist',
         'frontroute'   =>'/channelSet',
         'description'  =>'银行卡列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>156,
         'group_id'     =>59,
         'name'         =>'carrier/digitallist',
         'frontroute'   =>'/coinSet',
         'description'  =>'虚拟币列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>157,
         'group_id'     =>59,
         'name'         =>'carrier/digitaladd',
         'description'  =>'新增/编辑虚拟币',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>161,
         'group_id'     =>60,
         'name'         =>'system/playeripblackupdate',
         'description'  =>'游戏黑名单',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>163,
         'group_id'     =>60,
         'name'         =>'system/telegramchannel',
         'description'  =>'获取小飞机机频道信息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>164,
         'group_id'     =>60,
         'name'         =>'system/telegrambotsave',
         'description'  =>'保存机器人token',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>165,
         'group_id'     =>60,
         'name'         =>'system/telegramchannelsave',
         'description'  =>'保存小飞机频道',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>166,
         'group_id'     =>61,
         'name'         =>'link/list',
         'frontroute'   =>'/linkSet',
         'description'  =>'热点游戏列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>167,
         'group_id'     =>61,
         'name'         =>'link/add',
         'description'  =>'新增热点游戏列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>168,
         'group_id'     =>61,
         'name'         =>'link/del',
         'description'  =>'删除热点游戏',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>169,
         'group_id'     =>62,
         'name'         =>'message/messagelist',
         'frontroute'   =>'/systemNews',
         'description'  =>'消息列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>170,
         'group_id'     =>62,
         'name'         =>'mesage/messagesave',
         'description'  =>'发送消息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>171,
         'group_id'     =>63,
         'name'         =>'carrierimg/categorylist',
         'description'  =>'广告图片分类列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>172,
         'group_id'     =>63,
         'name'         =>'carrierimg/imglist',
         'frontroute'   =>'/pictureList',
         'description'  =>'广告图片列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>173,
         'group_id'     =>63,
         'name'         =>'fileupload/img',
         'description'  =>'图片上传',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>181,
         'group_id'     =>66,
         'name'         =>'system/serviceteamslist',
         'description'  =>'角色管理',
         'frontroute'   =>'/departSet',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>182,
         'group_id'     =>66,
         'name'         =>'system/serviceteamstatus',
         'description'  =>'变更角色状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>183,
         'group_id'     =>66,
         'name'         =>'system/serviceteamadd',
         'description'  =>'新增/编辑角色',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>184,
         'group_id'     =>66,
         'name'         =>'system/grouppermission',
         'description'  =>'角色权限',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>185,
         'group_id'     =>66,
         'name'         =>'system/serviceteampermissionsave',
         'description'  =>'更新角色权限',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>186,
         'group_id'     =>67,
         'name'         =>'system/carrieruserlist',
         'frontroute'   =>'/accountSet',
         'description'  =>'员工列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>187,
         'group_id'     =>67,
         'name'         =>'system/carrieruserstatus',
         'description'  =>'变更员工状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>188,
         'group_id'     =>67,
         'name'         =>'system/carrieredititem',
         'description'  =>'更新员工',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>189,
         'group_id'     =>67,
         'name'         =>'system/carrieruserresetpassword',
         'description'  =>'重置密码',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>190,
         'group_id'     =>67,
         'name'         =>'system/carrieruseradd',
         'description'  =>'新增员工',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>198,
         'group_id'     =>70,
         'name'         =>'carrier/paymentonbehalflist',
         'description'  =>'代付方式列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>199,
         'group_id'     =>70,
         'name'         =>'carrier/withdrawsuccess',
         'description'  =>'手动出款',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>206,
         'group_id'     =>14,
         'name'         =>'carrier/addreducelist',
         'description'  =>'理赔列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>207,
         'group_id'     =>14,
         'name'         =>'carrier/addreduce',
         'description'  =>'添加理赔',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>209,
         'group_id'     =>74,
         'name'         =>'home/noticelist',
         'description'  =>'平台公告',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>210,
         'group_id'     =>74,
         'name'         =>'home/toollist',
         'description'  =>'常用工具',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>211,
         'group_id'     =>74,
         'name'         =>'home/contactlist',
         'description'  =>'联系我们',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>212,
         'group_id'     =>74,
         'name'         =>'home/lotterywebsitelist',
         'description'  =>'开奖网址',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>213,
         'group_id'     =>74,
         'name'         =>'home/statreport',
         'frontroute'   =>'/platHome',
         'description'  =>'报表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>214,
         'group_id'     =>102,
         'name'         =>'carrier/banktypepagelist',
         'frontroute'   =>'/bankType',
         'description'  =>'银行卡管理',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>215,
         'group_id'     =>14,
         'name'         =>'carrier/playerbankedit',
         'description'  =>'银行卡更新',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>216,
         'group_id'     =>14,
         'name'         =>'carrier/addwithdrawslimit',
         'description'  =>'添加流水限制',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>217,
         'group_id'     =>14,
         'name'         =>'carrier/playerbankdelete',
         'description'  =>'删除银行卡',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>218,
         'group_id'     =>15,
         'name'         =>'carrier/playerlevelcarrierbanklist',
         'description'  =>'获取会员等级对应银行卡列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>219,
         'group_id'     =>15,
         'name'         =>'carrier/playerlevelcarrierbankupdate',
         'description'  =>'更新会员等级对应银行卡列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>220,
         'group_id'     =>14,
         'name'         =>'carrier/playerfinanceinfo',
         'description'  =>'获取会员财务信息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>221,
         'group_id'     =>14,
         'name'         =>'carrier/playerdigitaladdresslist',
         'description'  =>'获取会员数字币地址信息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>222,
         'group_id'     =>14,
         'name'         =>'carrier/playerdigitaladdressdelete',
         'description'  =>'删除会员数字币地址信息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>223,
         'group_id'     =>14,
         'name'         =>'carrier/playerdigitaladdressedit',
         'description'  =>'更新会员数字币地址信息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>226,
         'group_id'     =>77,
         'name'         =>'carrier/giftlist',
         'frontroute'   =>'/Cash_gifts',
         'description'  =>'活动礼金记录',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>227,
         'group_id'     =>22,
         'name'         =>'carrier/resetwithdrawslimit',
         'description'  =>'重启会员流水',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>228,
         'group_id'     =>25,
         'name'         =>'report/sendallearnling',
         'description'  =>'一键发放全部分红',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>229,
         'group_id'     =>25,
         'name'         =>'report/sendearnling',
         'description'  =>'发放会员分红',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>230,
         'group_id'     =>25,
         'name'         =>'lottery/playedlist',
         'description'  =>'彩票玩法列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>231,
         'group_id'     =>28,
         'name'         =>'lottery/betcodelimitlist',
         'frontroute'   =>'/LimitCode',
         'description'  =>'彩票码数限制列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>232,
         'group_id'     =>30,
         'name'         =>'system/gameList',
         'description'  =>'系统游戏列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>233,
         'group_id'     =>38,
         'name'         =>'chat/noticelist',
         'frontroute'   =>'/chat_notice_index',
         'description'  =>'公告列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>234,
         'group_id'     =>46,
         'name'         =>'carrier/activityinfo',
         'description'  =>'优惠活动详情',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>235,
         'group_id'     =>79,
         'name'         =>'carrier/activityplayerregistergiftlist',
         'frontroute'   =>'/rewardList',
         'description'  =>'优惠活动详情',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>236,
         'group_id'     =>81,
         'name'         =>'carrier/activitysigninlist',
         'frontroute'   =>'/signList',
         'description'  =>'签到活动人员列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>238,
         'group_id'     =>84,
         'name'         =>'carrier/activityregistergiftlist',
         'frontroute'   =>'/betStep',
         'description'  =>'注册满送设置列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>239,
         'group_id'     =>84,
         'name'         =>'carrier/activityregistergiftsave',
         'description'  =>'注册满送设置更新',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>240,
         'group_id'     =>84,
         'name'         =>'carrier/activityregistergiftdel',
         'description'  =>'注册满送设置删除',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>241,
         'group_id'     =>85,
         'name'         =>'carrier/activityregistergiftplayerlist',
         'frontroute'   =>'/regSendList',
         'description'  =>'注册满送人员列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>242,
         'group_id'     =>85,
         'name'         =>'carrier/activityregistergiftplayersendall',
         'description'  =>'一键发放满送奖金',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>243,
         'group_id'     =>85,
         'name'         =>'carrier/activityregistergiftplayersend',
         'description'  =>'发放满送奖金',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>244,
         'group_id'     =>85,
         'name'         =>'carrier/activityregistergiftplayercancel',
         'description'  =>'取消发放满送奖金',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>245,
         'group_id'     =>87,
         'name'         =>'carrier/activitynationalagencyplayerlist',
         'frontroute'   =>'/PromotionList',
         'description'  =>'全民推广人员列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>246,
         'group_id'     =>87,
         'name'         =>'carrier/activitynationalagencyplayercancel',
         'description'  =>'取消发放全民推广人员奖金',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>247,
         'group_id'     =>87,
         'name'         =>'carrier/activitynationalagencyplayersend',
         'description'  =>'发放全民推广人员奖金',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>248,
         'group_id'     =>87,
         'name'         =>'carrier/activitynationalagencyplayersendall',
         'description'  =>'一键发放全民推广人员奖金',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>253,
         'group_id'     =>105,
         'name'         =>'carrier/activitygiftcodelist',
         'frontroute'   =>'/giftSet',
         'description'  =>'体验券列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>254,
         'group_id'     =>105,
         'name'         =>'carrier/activitygiftcodesave',
         'description'  =>'新增/编辑体验券',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>255,
         'group_id'     =>105,
         'name'         =>'carrier/activitygiftcodechangestatus',
         'description'  =>'变更体验券状态',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>256,
         'group_id'     =>105,
         'name'         =>'carrier/activitygiftcodedel',
         'description'  =>'删除体验券',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>257,
         'group_id'     =>106,
         'name'         =>'carrier/activitygiftcodepersonlist',
         'frontroute'   =>'/giftSetList',
         'description'  =>'体验券领取人员列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>258,
         'group_id'     =>91,
         'name'         =>'carrier/activityrepeatedlydepositlist',
         'frontroute'   =>'/manyDepositList',
         'description'  =>'多次存款人员列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>259,
         'group_id'     =>92,
         'name'         =>'carrier/activitiesbreakthroughplayerlist',
         'frontroute'   =>'/rushList',
         'description'  =>'闯关人员列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>263,
         'group_id'     =>93,
         'name'         =>'player/agents',
         'description'  =>'查询上级',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>264,
         'group_id'     =>95,
         'name'         =>'report/carriermonthstatlist',
         'frontroute'   =>'/Winandloselist_month',
         'description'  =>'公司盈亏月报',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>265,
         'group_id'     =>96,
         'name'         =>'report/lotteryplatlist',
         'frontroute'   =>'/lotteryPlatformReport',
         'description'  =>'彩票平台报表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>266,
         'group_id'     =>107,
         'name'         =>'carrierremainquota/list',
         'frontroute'   =>'/limitUse',
         'description'  =>'额度变更列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>268,
         'group_id'     =>99,
         'name'         =>'player/playeroperatelog',
         'frontroute'   =>'/userLog',
         'description'  =>'用户操作日志',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>269,
         'group_id'     =>56,
         'name'         =>'carrier/allthirdpartpaylist',
         'description'  =>'所有三方通道列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>270,
         'group_id'     =>56,
         'name'         =>'carrier/paychannellist',
         'frontroute'   =>'/fundsDetail',
         'description'  =>'代收绑定',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>271,
         'group_id'     =>56,
         'name'         =>'carrier/paychannelbind',
         'description'  =>'商户代收通道绑定',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>272,
         'group_id'     =>56,
         'name'         =>'carrier/paychanneladd',
         'description'  =>'添加商户代收通道',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>273,
         'group_id'     =>57,
         'name'         =>'carrier/thirdpaylist',
         'frontroute'   =>'/interfaceSet',
         'description'  =>'代收设置',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>274,
         'group_id'     =>57,
         'name'         =>'carrier/payfactory',
         'description'  =>'支付厂商列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>275,
         'group_id'     =>57,
         'name'         =>'carrier/thirdpayadd',
         'description'  =>'支付通道绑定',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>276,
         'group_id'     =>102,
         'name'         =>'carrier/banktypepagelist',
         'description'  =>'银行卡类型列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>277,
         'group_id'     =>102,
         'name'         =>'carrier/banktypeadd',
         'description'  =>'添加银行卡类型',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>278,
         'group_id'     =>102,
         'name'         =>'carrier/banktypedel',
         'description'  =>'删除银行卡类型',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>279,
         'group_id'     =>62,
         'name'         =>'message/memberlist',
         'description'  =>'用户消息列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>280,
         'group_id'     =>62,
         'name'         =>'message/messagesave',
         'description'  =>'发送用户消息',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>281,
         'group_id'     =>63,
         'name'         =>'carrier/carrierimgcategorylist',
         'description'  =>'广告图片分类列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>282,
         'group_id'     =>63,
         'name'         =>'carrier/carrierimglist',
         'description'  =>'广告图片列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>283,
         'group_id'     =>63,
         'name'         =>'carrier/carrierimgsave',
         'description'  =>'广告图片更新',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>284,
         'group_id'     =>63,
         'name'         =>'carrier/carrierimgdel',
         'description'  =>'广告图片删除',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>285,
         'group_id'     =>67,
         'name'         =>'carrier/closeGoogle',
         'description'  =>'关闭google验证码',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>286,
         'group_id'     =>104,
         'name'         =>'carrier/closeGoogle',
         'description'  =>'访问日志',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>287,
         'group_id'     =>61,
         'name'         =>'link/list',
         'description'  =>'热门链接',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>288,
         'group_id'     =>21,
         'name'         =>'carrier/transferlist',
         'frontroute'   =>'/accountChangeRecord',
         'description'  =>'用户帐变记录',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>289,
         'group_id'     =>25,
         'name'         =>'report/cardearnlinglist',
         'frontroute'   =>'/dividendsRecord',
         'description'  =>'用户分红记录',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>290,
         'group_id'     =>108,
         'name'         =>'carrier/arbitragebankalist',
         'frontroute'   =>'/caseBank',
         'description'  =>'套利银行卡列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>292,
         'group_id'     =>109,
         'name'         =>'carrier/activityinvitedmobilelist',
         'frontroute'   =>'/mobileList',
         'description'  =>'特邀手机号查询',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>295,
         'group_id'     =>86,
         'name'         =>'system/websiteinfo',
         'frontroute'   =>'/Promotion',
         'description'  =>'全民推广设置',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>296,
         'group_id'     =>88,
         'name'         =>'system/websiteinfo',
         'frontroute'   =>'/winLose',
         'description'  =>'亏损金设置',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>299,
         'group_id'     =>93,
         'name'         =>'report/statdaylist',
         'frontroute'   =>'/totaTlstatdaylist',
         'description'  =>'团队日报表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>300,
         'group_id'     =>94,
         'name'         =>'report/totalstatdaylist',
         'frontroute'   =>'/totaTlstatdaylist2',
         'description'  =>'团队总报表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>301,
         'group_id'     =>97,
         'name'         =>'report/playerLottStatList',
         'frontroute'   =>'/userBetLottery',
         'description'  =>'用户彩票盈亏',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>303,
         'group_id'     =>100,
         'name'         =>'carrier/paychannellist',
         'frontroute'   =>'/fundsDetail2',
         'description'  =>'代付绑定',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>304,
         'group_id'     =>101,
         'name'         =>'carrier/thirdpaylist',
         'frontroute'   =>'/interfaceSet2',
         'description'  =>'代付设置',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>305,
         'group_id'     =>112,
         'name'         =>'carrier/paychannelgrouplist',
         'frontroute'   =>'/putWayList',
         'description'  =>'代收分类列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>306,
         'group_id'     =>60,
         'name'         =>'system/websiteinfo',
         'frontroute'   =>'/parameterSet',
         'description'  =>'系统参数设置',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>307,
         'group_id'     =>103,
         'name'         =>'system/contactWayList',
         'frontroute'   =>'/concatUs',
         'description'  =>'联系我们',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>308,
         'group_id'     =>113,
         'name'         =>'system/adminLoginList',
         'frontroute'   =>'/adminLoginList',
         'description'  =>'管理员登录日志',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>309,
         'group_id'     =>104,
         'name'         =>'log/list',
         'frontroute'   =>'/adminVisitList',
         'description'  =>'访问记录',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>310,
         'group_id'     =>82,
         'name'         =>'system/websiteinfo',
         'frontroute'   =>'/Promotion2',
         'description'  =>'直属充值推广设置',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>311,
         'group_id'     =>83,
         'name'         =>'carrier/activitydirectlyunderrechargeplayerlist',
         'frontroute'   =>'/PromotionList2',
         'description'  =>'直属充值推广人员列表',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>312,
         'group_id'     =>114,
         'name'         =>'system/websiteinfo',
         'description'  =>'充值分红活动设置',
         'frontroute'   =>'/earnRecharge',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>313,
         'group_id'     =>115,
         'name'         =>'system/websiteinfo',
         'description'  =>'提现分红活动设置',
         'frontroute'   =>'/earnWithdraw',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>315,
         'group_id'     =>70,
         'name'         =>'carrier/playergamestat',
         'description'  =>'投注简报',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>316,
         'group_id'     =>70,
         'name'         =>'carrier/specialgamestat',
         'description'  =>'风险投注简报',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>317,
         'group_id'     =>70,
         'name'         =>'carrier/withdrawcancel',
         'description'  =>'取消提现',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>318,
         'group_id'     =>117,
         'name'         =>'report/commissionlist',
         'description'  =>'佣金列表',
         'frontroute'   =>'/userRebate',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);  

        DB::table('permissions')->insert([
         'id'           =>319,
         'group_id'     =>117,
         'name'         =>'report/sendcommission',
         'description'  =>'发放佣金',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);  

        DB::table('permissions')->insert([
         'id'           =>320,
         'group_id'     =>117,
         'name'         =>'report/cancelcommission',
         'description'  =>'取消发放',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>321,
         'group_id'     =>118,
         'name'         =>'report/agentstatdaylist',
         'description'  =>'代理日报表',
         'frontroute'   =>'/agentReport',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>323,
         'group_id'     =>121,
         'name'         =>'system/websiteinfo',
         'description'  =>'代理转介绍礼金设置',
         'frontroute'   =>'/agentGift',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>324,
         'group_id'     =>124,
         'name'         =>'article/questionlists',
         'description'  =>'问题列表',
         'frontroute'   =>'/question',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>325,
         'group_id'     =>124,
         'name'         =>'article/questionadd',
         'description'  =>'添加问题',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>326,
         'group_id'     =>124,
         'name'         =>'article/questiondelete',
         'description'  =>'删除问题',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>327,
         'group_id'     =>125,
         'name'         =>'article/feedbacklist',
         'description'  =>'反馈列表',
         'frontroute'   =>'/feedback',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>328,
         'group_id'     =>123,
         'name'         =>'firstdepositawardlist',
         'description'  =>'邀请首存奖励列表',
         'frontroute'   =>'/firstRechargeList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>329,
         'group_id'     =>122,
         'name'         =>'system/websiteinfo',
         'description'  =>'邀请首存奖励设置',
         'frontroute'   =>'/firstRecharge',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>331,
         'group_id'     =>127,
         'name'         =>'activitiesreceivegiftcenter',
         'description'  =>'福利数据列表',
         'frontroute'   =>'/welfareList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>333,
         'group_id'     =>129,
         'name'         =>'system/websiteinfo',
         'description'  =>'负盈利加码设置',
         'frontroute'   =>'/minusProfit',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>335,
         'group_id'     =>131,
         'name'         =>'system/websiteinfo',
         'description'  =>'代理扶持设置',
         'frontroute'   =>'/agentSupport',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>336,
         'group_id'     =>132,
         'name'         =>'carrier/playergameaccountlist',
         'description'  =>'游戏帐号列表',
         'frontroute'   =>'/gameAccountList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>338,
         'group_id'     =>60,
         'name'         =>'carrier/allthirdwallet',
         'description'  =>'三方钱包列表',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>339,
         'group_id'     =>134,
         'name'         =>'carrier/horizontalmenuslist',
         'description'  =>'横版菜单列表',
         'frontroute'   =>'/horizontalMenu',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>340,
         'group_id'     =>134,
         'name'         =>'carrier/changehorizontalmenusstatus',
         'description'  =>'改变横版菜单状态',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>341,
         'group_id'     =>134,
         'name'         =>'carrier/updatehorizontalmenus',
         'description'  =>'新增或更新横版菜单',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>342,
         'group_id'     =>134,
         'name'         =>'carrier/horizontalmenutype',
         'description'  =>'菜单类型列表',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>343,
         'group_id'     =>135,
         'name'         =>'carrier/agentwithdrawlist',
         'description'  =>'代理取款风控',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>344,
         'group_id'     =>136,
         'name'         =>'carrier/ranklist',
         'description'  =>'业绩排行榜',
         'frontroute'   =>'/rankList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>345,
         'group_id'     =>137,
         'name'         =>'carrier/flowcommissionlist',
         'description'  =>'流水佣金记录',
         'frontroute'   =>'/flowcommissionlist',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>350,
         'group_id'     =>140,
         'name'         =>'carrier/guaranteedlist',
         'description'  =>'会员返佣列表',
         'frontroute'   =>'/memberCommissionList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>351,
         'group_id'     =>141,
         'name'         =>'carrier/memberbanklist',
         'description'  =>'会员银行卡列表',
         'frontroute'   =>'/memberBankList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>352,
         'group_id'     =>142,
         'name'         =>'carrier/memberdigitaladdresslist',
         'description'  =>'会员数字币列表',
         'frontroute'   =>'/memberDigitalAddressList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>353,
         'group_id'     =>144,
         'name'         =>'carrier/datamonitor',
         'description'  =>'数据监控列表',
         'frontroute'   =>'/dataMonitor',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>354,
         'group_id'     =>145,
         'name'         =>'carrier/stocklist',
         'description'  =>'库存列表',
         'frontroute'   =>'/stockList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>355,
         'group_id'     =>14,
         'name'         =>'carrier/performancestat',
         'description'  =>'直属业绩',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>356,
         'group_id'     =>146,
         'name'         =>'carrier/voucherconvertlist',
         'description'  =>'体验券监控',
         'frontroute'   =>'/voucherConvertList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>357,
         'group_id'     =>147,
         'name'         =>'carrier/tasklist',
         'description'  =>'关卡设置',
         'frontroute'   =>'/taskList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>358,
         'group_id'     =>147,
         'name'         =>'carrier/taskadd',
         'description'  =>'新增关卡',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]); 

        DB::table('permissions')->insert([
         'id'           =>359,
         'group_id'     =>147,
         'name'         =>'carrier/taskdel',
         'description'  =>'删除关卡',
         'frontroute'   =>'/taskList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>360,
         'group_id'     =>148,
         'name'         =>'carrier/capitationfeelevelslist',
         'description'  =>'人头关卡设置',
         'frontroute'   =>'/capitationFeeLevelsList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>361,
         'group_id'     =>148,
         'name'         =>'carrier/capitationfeelevelsadd',
         'description'  =>'人头关卡新增/编辑',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>362,
         'group_id'     =>148,
         'name'         =>'carrier/capitationfeelevelsdel',
         'description'  =>'人头关卡删除',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>363,
         'group_id'     =>149,
         'name'         =>'carrier/capitationfeelist',
         'description'  =>'人头费列表',
         'frontroute'   =>'/capitationFeeList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>364,
         'group_id'     =>149,
         'name'         =>'carrier/capitationfeelchangestatus',
         'description'  =>'发放或拒绝发放人头费',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]); 

        DB::table('permissions')->insert([
         'id'           =>365,
         'group_id'     =>150,
         'name'         =>'carrier/shareslist',
         'description'  =>'游戏输赢设置',
         'frontroute'   =>'/shareList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]); 

        DB::table('permissions')->insert([
         'id'           =>366,
         'group_id'     =>150,
         'name'         =>'carrier/sharesadd',
         'description'  =>'新增/编辑游戏输赢设置',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]); 

        DB::table('permissions')->insert([
         'id'           =>367,
         'group_id'     =>150,
         'name'         =>'carrier/sharesdel',
         'description'  =>'删除游戏输赢设置',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>368,
         'group_id'     =>60,
         'name'         =>'system/websitemultiplesave',
         'description'  =>'多前端设置',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]); 

        DB::table('permissions')->insert([
         'id'           =>369,
         'group_id'     =>151,
         'name'         =>'carrier/sharesperformancelist',
         'description'  =>'业绩分红设置',
         'frontroute'   =>'/sharePerformanceList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]); 

        DB::table('permissions')->insert([
         'id'           =>370,
         'group_id'     =>151,
         'name'         =>'carrier/sharesperformanceadd',
         'description'  =>'新增/编辑业绩分红设置',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]); 

        DB::table('permissions')->insert([
         'id'           =>371,
         'group_id'     =>151,
         'name'         =>'carrier/sharesperformancedel',
         'description'  =>'删除业绩分红设置',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>372,
         'group_id'     =>128,
         'name'         =>'carrier/poplist',
         'description'  =>'首页弹窗',
         'frontroute'   =>'/articleListPop',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
         'id'           =>373,
         'group_id'     =>128,
         'name'         =>'carrier/popsave',
         'description'  =>'保存首页弹窗',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>374,
         'group_id'     =>128,
         'name'         =>'carrier/popchangestatus',
         'description'  =>'变更首页弹窗状态',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>375,
         'group_id'     =>128,
         'name'         =>'carrier/activitiespopimglist',
         'description'  =>'首页弹窗图片列表',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>376,
         'group_id'     =>128,
         'name'         =>'carrier/popdelete',
         'description'  =>'首页弹窗删除',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>377,
         'group_id'     =>152,
         'name'         =>'carrier/waterquery',
         'description'  =>'业绩查询列表',
         'frontroute'   =>'/waterQuery',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>378,
         'group_id'     =>152,
         'name'         =>'carrier/gamemonitor',
         'description'  =>'游戏监控',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>379,
         'group_id'     =>152,
         'name'         =>'carrier/clearperformance',
         'description'  =>'业绩清空',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>380,
         'group_id'     =>153,
         'name'         =>'carrier/domainlist',
         'description'  =>'域名列表',
         'frontroute'   =>'/domainList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>381,
         'group_id'     =>153,
         'name'         =>'carrier/domainadd',
         'description'  =>'添加域名',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>382,
         'group_id'     =>153,
         'name'         =>'carrier/domaindel',
         'description'  =>'删除域名',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>383,
         'group_id'     =>153,
         'name'         =>'carrier/alldomain',
         'description'  =>'所有域名',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>391,
         'group_id'     =>158,
         'name'         =>'report/realearnlinglist',
         'description'  =>'代理实时分红',
         'frontroute'   =>'/realEarnlingList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>392,
         'group_id'     =>159,
         'name'         =>'carrier/regresslist',
         'description'  =>'回归礼金设置',
         'frontroute'   =>'/regressList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]); 

        DB::table('permissions')->insert([
         'id'           =>393,
         'group_id'     =>159,
         'name'         =>'carrier/sendregress',
         'description'  =>'发放回归礼金',
         'frontroute'   =>'',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]); 

        DB::table('permissions')->insert([
         'id'           =>394,
         'group_id'     =>138,
         'name'         =>'carrier/realflowcommissionlist',
         'description'  =>'流水实时佣金',
         'frontroute'   =>'/realFlowcommissionlist',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>395,
         'group_id'     =>160,
         'name'         =>'carrier/noticelist',
         'description'  =>'公告列表',
         'frontroute'   =>'/noticeList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);

        DB::table('permissions')->insert([
         'id'           =>396,
         'group_id'     =>161,
         'name'         =>'carrier/memberalipaylist',
         'description'  =>'支付宝列表',
         'frontroute'   =>'/alipayList',
         'created_at'   =>date('Y-m-d H:i:s'),
         'updated_at'   =>date('Y-m-d H:i:s'),
        ]);
    }
}
