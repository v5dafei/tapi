<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSysTable extends Migration
{

    public function up()
    {
        // 资金变动表
        Schema::create('def_account_change_type', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sign',32)->comment("标识");
            $table->string('name',32)->comment("值");
            $table->string('en_name',64)->comment("英文值");
            $table->tinyInteger('type')->comment("1=余额增加，2=余额减少 3=余额不变");
            $table->tinyInteger('amount')->comment("金额");
            $table->tinyInteger('user_id')->comment("玩家ID");
            $table->tinyInteger('platform_id')->comment("游戏平台ID");
            $table->tinyInteger('from_id')->comment("转出用户ID");
            $table->tinyInteger('to_id')->comment("转入用户ID");
            $table->tinyInteger('activity_id')->comment("活动ID");
            $table->integer('admin_id');
            $table->timestamps();
        });

        // 支付类型表
        Schema::create('def_bank', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bank_name',32)->comment("银行名称");
            $table->string('bank_code',32)->comment("银行代码");
            $table->string('bank_background_url',256)->comment("银行logo");
            $table->string('currency',6)->comment("币种");
            $table->timestamps();
        });

        //刷水客银行卡表
        Schema::create('def_arbitrage_bank', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bank_name',32)->comment("银行名称");
            $table->string('card_owner_name',32)->default('')->comment("卡主");
            $table->string('card_account',64)->comment("卡号");
            $table->timestamps();
        });

        //刷水支付宝表
        Schema::create('def_arbitrage_alipay', function (Blueprint $table) {
            $table->increments('id');
            $table->string('real_name',64)->comment("姓名");
            $table->string('account',32)->comment("帐号");
            $table->timestamps();
        });

        //支付厂家
         Schema::create('def_pay_factory_list', function (Blueprint $table) {
            $table->increments('id');
            $table->string('factory_name',32)->comment("支付厂家名称");
            $table->string('code',32)->comment("支付类名称");
            $table->string('currency',32)->default('CNY')->comment("支持的币种");
            $table->string('ip',128)->default('')->comment("IP白名单");
            $table->tinyInteger('status')->default(1)->comment("状态,1=启用,0=停用");
            $table->tinyInteger('third_wallet_id')->default(0)->comment("是否数字币或钱包 1=是,0=否");
            $table->tinyInteger('type')->default(1)->comment("状态,1=存款,2=取款");
            $table->timestamps();
        });

        // 支付渠道表
        Schema::create('def_pay_channel_list', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('factory_id')->comment("支付厂家id");
            $table->string('name',32)->default('')->comment("支付渠道名称");
            $table->tinyInteger('type')->default(1)->comment("1=充值 ，2=代付");
            $table->string('channel_code',255)->comment("渠道名称");
            $table->integer('min')->default(0)->comment("最小金额");
            $table->integer('max')->default(0)->comment("最大金额");
            $table->string('enum',64)->default('')->comment("金额枚举");
            $table->integer('is_smallamountpay')->default(0)->comment("是否小额支付,1=是,0=否");
            $table->tinyInteger('has_realname')->default(0)->comment("是否需要真实姓名");
            $table->tinyInteger('is_show_enter')->default(1)->comment("是否允许手动输入");
            $table->string('remark',128)->default('')->comment("通道提示信息");           
            $table->decimal('trade_rate',5,2)->default(0.00)->comment("交易费率100比");
            $table->decimal('single_fee',5,2)->default(0.00)->comment("交易单笔费用");

            $table->timestamps();
        });


        // 创建游戏平台
        Schema::create('def_main_game_plats', function (Blueprint $table) {
            $table->increments('main_game_plat_id');
            $table->string('main_game_plat_code',32)->comment("主平台代码");
            $table->tinyInteger('status')->default(1)->comment("1 正常  0 关闭,2 维护");
            $table->tinyInteger('changeLine')->default(0)->comment("0 正常状态 1 换线中  2 换线完成");
            $table->integer('sort')->default(0)->comment("排序");
            $table->string('alias')->comment("名称");
            $table->string('en_alias')->comment("英文名称");
            $table->string('short')->default('')->comment("简称");
            $table->timestamps();
        });

        // 商户游戏平台列表
        Schema::create('def_games', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('main_game_plat_id')->comment("主平台ID");
            $table->integer('game_category')->comment("1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼");
            $table->string('game_id',32)->default('')->comment("游戏ID");
            $table->string('main_game_plat_code',32)->comment("主平台代码");
            $table->string('game_name',32)->default('')->comment("中文名称");
            $table->string('en_game_name',32)->default('')->comment("英文名称");
            $table->string('game_code',32)->default('')->comment("PC编码");
            $table->string('game_moblie_code',32)->default('')->comment("移动编码");
            $table->tinyInteger('format')->default(0)->comment("0=不限，1=直式，2=横式");
            $table->string('game_icon_square_path',64)->default('')->comment("200*200中文图片");
            $table->string('en_game_icon_square_path',64)->default('')->comment("200*200英文图片");
            $table->tinyInteger('zh_status')->default(1)->comment("是否支持简体中文 1=是,0=否");
            $table->tinyInteger('en_status')->default(1)->comment("是否支持英语  1=是,0=否");
            $table->tinyInteger('status')->default(1)->comment("1 正常  0 关闭 2维护");
            $table->tinyInteger('is_offline')->default(0)->comment("是否下架 1 是  0 否");
            $table->integer('pageview')->default(0)->comment("人气");
            $table->tinyInteger('is_recommend')->default(0)->comment("推荐 1 是  0 否");
            $table->tinyInteger('is_hot')->default(0)->comment("热门 1 是  0 否");
            $table->tinyInteger('is_pool')->default(0)->comment("奖期 1 是  0 否");
            $table->tinyInteger('multi_spin_game')->default(0)->comment("1=是多旋转游戏  0=否");
            $table->integer('sort')->default(1)->comment("排序");
            $table->string('record_match_code',32)->comment("配对");
            $table->timestamps();

            $table->index("game_id");
            $table->index("main_game_plat_id");
            $table->index("main_game_plat_code");
            $table->index(["main_game_plat_code","record_match_code"]);
            $table->index(["main_game_plat_id","record_match_code"]);
        });

        // 推送渠道
        Schema::create('conf_telegram_channel', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商id");
            $table->string('channel_sign', 32)->default('')->comment("消息分类标识，暂不分类");
            $table->string('channel_group_name', 64)->default('')->comment("telegram群名");
            $table->string('channel_id', 128)->default(0)->comment("telegram群频道ID");

            $table->unique(['channel_sign','channel_group_name']);
            $table->timestamps();
        });

         //图片分类表
         Schema::create('inf_image_category', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category_name',32)->comment("名称");
            $table->timestamps();
        });

        //系统白名单
        Schema::create('inf_white_ip', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ip', 255)->comment("白名单IP地址");
            $table->string('remarks', 64)->default('')->comment("备注");
            $table->timestamps();
        });

        //系统地区
        Schema::create('inf_area', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->comment("父ID");
            $table->tinyInteger('type')->comment("2=省，3=市");
            $table->string('name',32)->comment("名称");
            $table->string('inner_code',200)->comment("长编码");
            $table->timestamps();
        });

        //模板表
        Schema::create('def_sms_passage_list', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',32)->comment("通道名称");
            $table->string('appkey',64)->comment("应用KEY");
            $table->string('appcode',64)->comment("应用代码");
            $table->string('appsecret',64)->comment("应用密钥");
            $table->string('sendurl',64)->comment("发送地址");
            $table->string('filename',32)->comment("文件名称");
            $table->tinyInteger('status')->comment("状态 0=禁用 1=启用");
            $table->timestamps();
        });

        //银行编码与三方银行编码影射表
        Schema::create('map_pay_factory_bank_code', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pay_factory_id')->comment("支付厂商ID");
            $table->string('currency',32)->comment("币种");
            $table->string('bank_code',16)->comment("银行编码");
            $table->string('third_bank_code',32)->comment("三方编码");

            $table->timestamps();
        });

        //三方钱包表
        Schema::create('def_third_wallet', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',32)->comment("币种");
            $table->string('currency',16)->comment("钱包币种");
            $table->timestamps();
        });

        //域名列表
        Schema::create('def_domain', function (Blueprint $table) {
            $table->increments('id');                  
            $table->string('domain', 64)->comment("地址");
            $table->timestamps();
        });

        //实名三方钱包列表
        Schema::create('def_digital_address_lib', function (Blueprint $table) {
            //$table->increments('id');  
            $table->increments('id');            
            $table->string('address', 64)->comment("地址");
            $table->tinyInteger('type')->comment("1=Trc20，2=Erc20 ,3=okpay ,4=goPay,5=gcash,6=topay,7=ebpay,8=Wanb,9=jdpay,10=kdpay,11=nopay,12=bobipay");
            $table->string('name',64);
            $table->tinyInteger('is_arbitrage')->comment("是否套利");
            $table->timestamps();
        });

        //三方支付宝列表
        Schema::create('def_alipay', function (Blueprint $table) {
            $table->increments('id');
            $table->string('real_name',64)->comment("姓名");                                     
            $table->string('account', 32)->comment("帐号");
            $table->tinyInteger('type')->comment("1=手机号，2=邮箱号");
            $table->tinyInteger('verification')->default(0)->comment("1=已验证，0=未验证");                         
            $table->timestamps();
        });

        //线路表
        Schema::create('def_game_line', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',64)->comment("线路名称");                                     
            $table->string('main_game_plat_code', 128)->comment("平台代码,用逗号分隔");
            $table->tinyInteger('rate')->comment("返奖率");
            $table->tinyInteger('is_point_kill')->default(0)->comment("是否点杀群组");                         
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('def_account_change_type');
        Schema::dropIfExists('def_bank');
        Schema::dropIfExists('def_pay_factory_list');
        Schema::dropIfExists('def_pay_channel_list');
        Schema::dropIfExists('def_main_game_plats');
        Schema::dropIfExists('def_games');
        Schema::dropIfExists('conf_telegram_channel');
        Schema::dropIfExists('inf_image_category');
        Schema::dropIfExists('inf_white_ip');
        Schema::dropIfExists('inf_area');
        Schema::dropIfExists('def_sms_passage_list');
        Schema::dropIfExists('map_pay_factory_bank_code');
        Schema::dropIfExists('def_arbitrage_bank');
        Schema::dropIfExists('def_arbitrage_alipay');
        Schema::dropIfExists('def_third_wallet');
        Schema::dropIfExists('def_domain');
        Schema::dropIfExists('def_digital_address_lib');
        Schema::dropIfExists('def_alipay');
        Schema::dropIfExists('def_game_line');
    }
}
