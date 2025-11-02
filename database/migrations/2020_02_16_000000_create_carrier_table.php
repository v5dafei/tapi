<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarrierTable extends Migration
{

    public function up()
    {
        // 总管理员表
        Schema::create('inf_carrier', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 32);
            $table->string('apiusername', 32)->default('')->commit("游戏集成平台帐号");
            $table->string('apipassword', 32)->default('')->commit("游戏集成平台密码");
            $table->string('apikey', 128)->default('')->commit("游戏集成平台key");
            $table->string('sign',4)->commit("运营商标识");
            $table->tinyInteger('is_forbidden')->default(0)->comment("是否禁用 1=是，0=否");
            $table->decimal('remain_quota',15,4)->comment("当前额度");
            $table->timestamps();
        });

        // 总管理员表
        Schema::create('inf_carrier_bankcard', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->commit("运营商ID");
            $table->integer('bank_id')->commit("银行ID");
            $table->string('bank_username', 32)->default('开户名');
            $table->string('bank_account', 32)->default('帐号');
            $table->tinyInteger('status')->default(0)->commit("银行卡状态1=正常，0=关闭");
            $table->integer('sort')->default(100)->commit("排序");
            $table->timestamps();
        });

        // 商户数字币收款表
        Schema::create('inf_carrier_digital_address', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->commit("运营商ID");
            $table->integer('type')->commit("1=USDT-Trc20，2=USDT-Erc20");
            $table->integer('adminId')->commit("管理员ID");
            $table->string('address', 64)->default('数字币地址');
            $table->integer('sort')->default(0)->commit("管理员ID");
            $table->tinyInteger('status')->default(0)->commit("虚拟币状态1=正常，0=关闭");
            $table->timestamps();
        });

        // 域名请求IP白名单
        Schema::create('conf_carrier_ips', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->commit("运营商ID");
            $table->string('login_ip',15)->commit("登录IP");
            $table->timestamps();
        });

        // 商户游戏平台列表
        Schema::create('map_carrier_game_plats', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->commit("运营商ID");
            $table->integer('game_plat_id')->commit("游戏平台ID");
            $table->decimal('point',3,1)->comment("游戏点位表");
            $table->integer('sort')->default(1)->commit("排序");
            $table->tinyInteger('status')->default(1)->comment("是否开启 1=开启，0=关闭 2=维护");
            $table->timestamps();
        });

        // 站点游戏平台点位
        Schema::create('map_carrier_prefix_game_plats', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->commit("运营商ID");
            $table->string('prefix', 4)->comment("站点");
            $table->integer('game_plat_id')->commit("游戏平台ID");
            $table->decimal('point',3,1)->comment("游戏点位表");
            $table->timestamps();
        });

        // 商户游戏列表
        Schema::create('map_carrier_games', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->commit("运营商ID");
            $table->integer('game_plat_id')->commit("游戏平台ID");
            $table->string('game_id',32)->commit("游戏ID");
            $table->string('display_name', 32)->comment("游戏名称");
            $table->string('en_display_name', 32)->comment("英文游戏名称");
            $table->integer('sort')->default(1)->comment("排序");
            $table->tinyInteger('status')->default(1)->comment("是否开启 1=开启，0=关闭 2=维护");
            $table->tinyInteger('en_status')->default(1)->comment("英文版本是否开启 1=开启，0=关闭 2=维护");
            $table->tinyInteger('is_recommend')->default(0)->comment("是否推荐 1=是，0=否");
            $table->tinyInteger('is_hot')->default(0)->comment("是否热门 1=是，0=否");
            $table->tinyInteger('game_category')->comment("1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票,7=捕鱼");
            $table->timestamps();

            $table->index("game_id");
            $table->index(['carrier_id','game_id']);
        });

        // 商户游戏列表
         Schema::create('conf_carrier_web_site', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('sign',64)->comment("键");
            $table->string('value',2000)->comment("值");
            $table->tinyInteger('type')->default(0)->comment("类型");
            $table->string('remark',64)->default('')->comment("备注");
            $table->timestamps();

            $table->index(['carrier_id','sign']);
        });

         // 币种相关批量设置列表
         Schema::create('conf_currency_web_site', function (Blueprint $table) {
            $table->increments('id');
            $table->string('currency',32)->comment("币种相关批量设置");
            $table->string('sign',64)->comment("键");
            $table->string('value',2000)->comment("值");
            $table->string('remark',64)->default('')->comment("备注");
            $table->timestamps();

            $table->index('sign');
        });

        // 多站点设置表
         Schema::create('conf_carrier_multiple_front', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix',4)->comment("站点");
            $table->string('sign',64)->comment("键");
            $table->string('value',5000)->comment("值");
            $table->tinyInteger('type')->default(0)->comment("类型");
            $table->string('remark',128)->default('')->comment("备注");

            $table->timestamps();

            $table->index("prefix");
            $table->index(['carrier_id','sign']);
        });

        // 会员等级配置
         Schema::create('inf_carrier_player_grade', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix',4)->default('A')->commit("用户前端前辍");
            $table->string('level_name',32)->default('')->comment("用户等级名称");
            $table->string('img',256)->default('')->comment("等级图片");
            $table->tinyInteger('is_default')->default(0)->comment("是否默认 1=是，0=否");
            $table->integer('withdrawcount')->default(1)->comment("每日提款次数");
            $table->integer('updategift')->default(0)->comment("升级礼金");
            $table->integer('birthgift')->default(0)->comment("生日礼金");
            $table->integer('weekly_salary')->default(0)->comment("周礼金");
            $table->integer('monthly_salary')->default(0)->comment("月礼金");
            $table->integer('turnover_multiple')->default(1)->comment("礼金流水倍数");
            $table->text('upgrade_rule')->comment("升级规则");
            $table->integer('sort')->default(1)->comment("排序");
            $table->timestamps();

        });

        // 团队
         Schema::create('inf_carrier_service_team', function (Blueprint $table) {
            $table->increments('id');
            $table->string('team_name',32)->comment("团队名称");
            $table->tinyInteger('is_administrator')->default(0)->comment("是否超管组 1=是，0=否");
            $table->string('remark',32)->nullable()->comment("备注");
            $table->tinyInteger('status')->default(1)->comment("是否启用 1=是，0=否");
            $table->tinyInteger('is_kefu')->default(0)->comment("是否客服 1=是，0=否");
            $table->timestamps();
        });

        // 商户管理员列表
         Schema::create('inf_carrier_user', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id')->comment("团队ID");
            $table->string('username',32)->comment("帐号");
            $table->string('password',128)->comment("密码");
            $table->string('nick_name',20)->comment("管理昵称");
            $table->rememberToken();
            $table->timestamp('login_at')->nullable()->comment("登录时间");
            $table->timestamp('deleted_at')->nullable()->comment("删除时间");
            $table->tinyInteger('status')->default(1)->comment("是否正常 1=是，0=否");
            $table->tinyInteger('is_super_admin')->default(0)->comment("是否超管 1=是，0=否");
            $table->string('google_img',255)->default('')->commit("");
            $table->tinyInteger('bind_google_status')->default(0)->comment("是否绑定google验证码");
            $table->timestamps();
        });

        // 商户管理员列表
         Schema::create('conf_carrier_third_part_pay', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('def_pay_channel_id')->comment("支付渠道");
            $table->string('merchant_number',64)->default('')->comment("三方支付商户号");
            $table->string('merchant_bind_domain',128)->default('')->comment("三方支付支付域名");
            $table->string('merchant_query_domain',128)->default('')->comment("三方支付查询域名");
            $table->string('private_key',128)->default('')->comment("三方支付密钥");
            $table->string('rsa_private_key',3000)->default('')->comment("商户rsa密钥");
            $table->string('rsa_public_key',3000)->default('')->comment("平台rsa公钥");
            $table->string('startTime',8)->default('00:00:00')->comment("开时时间");
            $table->string('endTime',8)->default('23:59:59')->comment("结束时间");
            $table->string('remark',1000)->default('')->comment("备注");
            $table->integer('total_order')->default(0)->comment("总订单数");
            $table->integer('success_order')->default(0)->comment("成功单数");
            $table->integer('is_anti_complaint')->default(1)->comment("是否抗投诉  1=抗投诉 0=不抗投诉");
            $table->tinyInteger('is_returnlink_hascode')->default(0)->comment("是否返链即有码 1=是，0=否");
            $table->tinyInteger('enabled_auto')->default(0)->comment("是否自动启用 1=是，0=否");
            $table->integer('auto_shutdown_number')->default(0)->comment("X次无码自动关停");
            $table->timestamps();
        });

        // 商户渠道列表
         Schema::create('inf_carrier_pay_channel', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix',4)->comment("站点");
            $table->string('show_name',32)->comment("前台显示名称");
            $table->string('img',168)->comment("图片地址");
            $table->string('video_url',255)->default('')->comment("视频地址");
            $table->integer('binded_third_part_pay_id')->nullable()->comment("三方支付ID");
            $table->tinyInteger('status')->comment("付款状态 1 启用  0=禁用");
            $table->tinyInteger('show')->default(3)->comment("1=PC 2=mobile 3=全部 4=IOS 5=安卓");
            $table->tinyInteger('gift_ratio')->default(0)->comment("赠送比例");
            $table->tinyInteger('is_recommend')->default(0)->comment("推荐");
            $table->integer('sort')->default(0)->comment("排序");
            $table->timestamps();

        });

         //收款银行卡
        Schema::create('inf_carrier_collection_card', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('account',16)->default('')->comment("银行卡卡号");
            $table->string('owner_name',20)->default('')->comment("转帐汇款时姓名");
            $table->integer('bank_id')->nullable()->comment("所属银行");
            $table->string('branch',32)->default('')->comment("所属支行");
            $table->timestamps();

        });


        //图片广告详情
         Schema::create('inf_carrier_img', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('image_category_id')->comment("图片分类ID");
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('image_path',64)->default('')->comment("图片地址");
            $table->string('language',64)->default('zh-cn')->comment("默认语言");
            $table->string('url',64)->default('')->comment("图片跳转地址");
            $table->integer('sort')->default(1)->comment("排序");
            $table->string('remark',64)->default('')->comment("备注");
            $table->string('prefix',4)->comment("站点");
            $table->integer('admin_id')->comment("操作人");
            $table->timestamps();

            $table->index(['carrier_id','image_category_id']);
        });

         //用户层级表
        Schema::create('inf_carrier_player_level', function (Blueprint $table) {             
            $table->increments('id');
            $table->integer('carrier_id')->default(0)->comment("运营商ID");
            $table->string('groupname',32)->comment("分组名称");
            $table->integer('is_system')->default(0)->comment("1=是系统分组,2=非系统系统分组");
            $table->string('remark',32)->default('')->comment("备注");
            $table->integer('rechargenumber')->default(0)->comment("充值次数");
            $table->integer('single_maximum_recharge')->default(0)->comment("单次最高充值");
            $table->integer('accumulation_recharge')->default(0)->comment("累积充值金额");
            $table->integer('sort')->default(1)->comment("排序");
            $table->tinyInteger('is_default')->default(0);
            $table->string('prefix',4)->comment("站点");
            $table->tinyInteger('game_line_id')->default(0)->comment("游戏线路ID");
            $table->timestamps();
        });

        //商户支付厂商表
        Schema::create('inf_carrier_pay_factory', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('factory_id')->comment("支付厂商ID");
            $table->timestamps();
        });

        //商户银行列表
        Schema::create('inf_carrier_bank_type', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('bank_name',32)->comment("银行名称");
            $table->string('bank_code',32)->comment("银行代码");
            $table->string('bank_background_url',256)->comment("银行logo");
            $table->string('currency',6)->comment("币种");
            $table->timestamps();
        });

        //彩种分组关联表
        Schema::create('inf_pay_channel_group', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix',4)->default('A')->comment("站点");
            $table->string('name',20)->default('')->comment("分组名称");
            $table->string('carrier_pay_channel_ids',1024)->default('')->comment("支付通道ID集合");                  
            $table->string('img',128)->default('')->comment("地址");
            $table->integer('sort')->default(0)->comment("排序");
            $table->integer('status')->default(0)->comment("状态");
            $table->string('currency',16)->default('CNY')->comment("币种");
            
            $table->timestamps();
        });

        //商户游戏分类列表
        Schema::create('inf_carrier_questions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->tinyInteger('type')->comment("1=存款问题，2=取款问题，3=帐号问题，4=优惠活动，5=代理加盟，6=虚拟货币，7=三方钱包，8=转帐问题,9其他问题");
            $table->string('title',32)->comment("标题");
            $table->string('content',255)->comment("内容");
            $table->integer('sort')->default(100)->comment("排序");
            $table->integer('admin_id')->default(0)->comment("添加人员");
            $table->timestamps();
        });

        //反馈列表
        Schema::create('inf_carrier_feedback', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->tinyInteger('type')->comment("1=存款问题，2=取款问题，3=帐号问题，4=优惠活动，5=代理加盟，6=虚拟货币，7=三方钱包，8=转帐问题,9其他问题");
            $table->string('title',32)->comment("标题");
            $table->string('content',255)->comment("内容");
            $table->bigInteger('player_id')->comment("反馈用户");
            $table->string('img_url',255)->comment("图片路径，多个用逗号分割");
            $table->timestamps();

        });

        //横版菜单列表
        Schema::create('inf_carrier_horizontal_menu', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('type',32)->comment("类型");
            $table->string('key',32)->comment("客服端类型");
            $table->string('api',128)->default('')->comment("api地址");
            $table->integer('sort')->default(0)->comment("排序");
            $table->tinyInteger('status')->default(0)->comment("1=开启，0=关闭");
            $table->string('prefix',4)->comment("前辍");
            $table->timestamps();

        });

        //横版保底列表
        Schema::create('inf_carrier_guaranteed', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->tinyInteger('game_category')->comment("游戏类型");
            $table->string('level',32)->comment("等级");
            $table->string('performance',32)->comment("业绩");
            $table->string('quota',32)->comment("返佣额度");
            $table->integer('sort')->comment("排序");
            $table->string('prefix',4)->comment("前辍");
            $table->timestamps();

        });

        // 总管理员表
        Schema::create('inf_carrier_prefix_domain', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->commit("运营商ID");
            $table->string('prefix',4)->comment("前辍");
            $table->string('domain', 255)->comment("商户域名");
            $table->string('name', 32)->comment("前端名称");
            $table->string('language',32)->comment("语言");
            $table->string('currency',32)->comment("币种");
            $table->string('sms_passage_id',4)->default(0)->comment("短信通道");
            $table->timestamps();
        });

        // 聊天管理行为记录表
        Schema::create('inf_carrier_capitation_fee_setting', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id');
            $table->string('prefix',4)->default('A')->commit("用户前端前辍");
            $table->integer('amount')->default(0)->comment('奖励金额');
            $table->tinyInteger('sort')->default(1)->comment("关卡");
            $table->tinyInteger('status')->default(0)->comment("1=已领取,0=未领取");
            $table->timestamps();

        });

        // 首页弹窗
        Schema::create('inf_carrier_pop', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix',4)->comment("网站");
            $table->string('language',64)->default('zh-cn')->comment("默认语言");
            $table->integer('type')->comment('1=PC，2=手机');
            $table->tinyInteger('status')->comment('1=开启，0=关闭');
            $table->string('title', 32)->comment("标题");
            $table->string('img_url', 255)->default('')->comment("图片地址");
            $table->string('url', 255)->default('')->comment("链接地址");
            $table->integer('sort')->default(1)->comment('排序');
            $table->integer('admin_id')->default(0)->comment('编辑人员');
            $table->timestamps();
        });

        //反馈列表
        Schema::create('inf_carrier_notice', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix',4)->comment("网站");
            $table->string('title',32)->comment("标题");
            $table->text('content')->default('')->comment("内容");
            $table->integer('sort')->default(100)->comment("排序");
            $table->timestamps();

        });
    }
    
    public function down()
    {
        Schema::dropIfExists('inf_carrier');
        Schema::dropIfExists('map_carrier_games');
        Schema::dropIfExists('map_carrier_game_plats');
        Schema::dropIfExists('map_carrier_prefix_game_plats');
        Schema::dropIfExists('conf_carrier_web_site');
        Schema::dropIfExists('conf_currency_web_site');
        Schema::dropIfExists('conf_carrier_multiple_front');
        Schema::dropIfExists('inf_carrier_player_grade');
        Schema::dropIfExists('inf_carrier_service_team');
        Schema::dropIfExists('inf_carrier_service_team_role');
        Schema::dropIfExists('inf_carrier_user');
        Schema::dropIfExists('conf_carrier_third_part_pay');
        Schema::dropIfExists('inf_carrier_pay_channel');
        Schema::dropIfExists('inf_carrier_collection_card');
        Schema::dropIfExists('inf_carrier_img');
        Schema::dropIfExists('conf_carrier_ips');  
        Schema::dropIfExists('inf_carrier_bankcard');
        Schema::dropIfExists('inf_carrier_digital_address');
        Schema::dropIfExists('inf_carrier_player_level');
        Schema::dropIfExists('inf_carrier_pay_factory');
        Schema::dropIfExists('inf_carrier_bank_type');
        Schema::dropIfExists('inf_pay_channel_group');
        Schema::dropIfExists('inf_carrier_menu');
        Schema::dropIfExists('inf_carrier_questions');
        Schema::dropIfExists('inf_carrier_feedback');
        Schema::dropIfExists('inf_carrier_horizontal_menu');
        Schema::dropIfExists('inf_carrier_guaranteed');
        Schema::dropIfExists('inf_carrier_prefix_domain');
        Schema::dropIfExists('inf_carrier_capitation_fee_setting');
        Schema::dropIfExists('inf_carrier_pop');
        Schema::dropIfExists('inf_carrier_notice');
    }
}
