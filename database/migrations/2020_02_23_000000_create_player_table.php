<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayerTable extends Migration
{

    public function up()
    {
        // 用户表
        Schema::create('inf_player', function (Blueprint $table) {
            $table->bigIncrements('player_id');
            $table->bigInteger('top_id')->default(0)->comment("直属ID");
            $table->bigInteger('parent_id')->comment("父级ID");                    
            $table->string('rid',255)->nullable();
            $table->string('user_name',64)->comment("帐号");                  
            $table->string('nick_name',32)->default('')->comment("昵称");
            $table->string('avatar',255)->default('0/avatar.jpg')->comment("头像");
            $table->string('mobile',16)->default('')->comment("手机号");        
            $table->string('real_name',32)->default('')->comment("真实姓名");
            $table->string('password',64)->comment("密码");
            $table->string('paypassword',64)->nullable()->comment("支付密码");
            $table->string('email',64)->default('')->comment("邮箱"); 
            $table->string('wechat',32)->default('')->comment("微信号"); 
            $table->tinyInteger('sex')->nullable()->comment("性别 1=男，2=女");
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('soncount')->default(0)->comment("直属下级数量");
            $table->integer('descendantscount')->default(0)->comment("下级数量");
            $table->integer('carrier_bankcard_id')->default(0)->comment("绑定入款银行卡");
            $table->string('login_ip',15)->default('')->comment("登录ip");
            $table->integer('player_level_id')->comment("等级");
            $table->tinyInteger('is_online')->default(0)->comment("在线");
            $table->tinyInteger('status')->default(1)->comment("状态 1=正常，0=关闭");
            $table->string('login_domain',32)->default('')->comment("登录域名");
            $table->string('domain',32)->default('')->comment("自已的二级域名");
            $table->string('qq_account',32)->default('')->comment("qq帐号");
            $table->date('birthday')->nullable()->comment("生日");
            $table->integer('province')->default(0)->comment("省份");
            $table->integer('area')->default(0)->comment("城市");
            $table->integer('is_auto_register')->default(0)->comment("是否自动注册，1=是，0=否");
            $table->string('register_ip',15)->default('')->comment("注册ip");
            $table->string('register_domain',64)->default('')->comment("注册域名");
            $table->timestamp('login_at')->nullable()->comment("登录时间");
            $table->integer('requesttime')->default(0)->comment("最近请求时间"); 
            $table->string('bankcardname',32)->default('')->comment("绑卡姓名"); 
            $table->string('remark',64)->default('')->comment("备注");
            $table->integer('inviteplayerid')->default(0)->comment("转介绍代理ID");
            $table->integer('first_deposit_recommender')->default(0)->comment("首存推荐人");
            $table->tinyInteger('app_model')->default(1)->comment("1=BC模式，2=H站模式");
            $table->tinyInteger('delayorder')->default(0)->comment("卡奖 1=是，0=否");
            $table->tinyInteger('type')->comment("1=直属，2=代理");
            $table->tinyInteger('chatManager')->default(0)->comment("是否聊天室管理员 1=是，0=否");
            $table->tinyInteger('is_tester')->default(0)->comment("1,试玩用户，0=正常用户，2=带玩用户");
            $table->tinyInteger('frozen_status')->default(0)->comment("0=正常，1=不能转帐，2=不能存取，3=能存不能取,4=不能登录");
            $table->tinyInteger('is_official')->default(0)->comment("0=非官方，1=官方");
            $table->integer('attention')->default(0)->comment("关注数");
            $table->integer('fan')->default(0)->comment("粉丝");
            $table->integer('dynamic')->default(0)->comment("动态");
            $table->integer('reward')->default(0)->comment("打赏");
            $table->integer('getreward')->default(0)->comment("收到打赏");
            $table->integer('level')->comment("深度，从1开始");
            $table->integer('player_group_id')->default(0)->comment("分组ID");
            $table->integer('day')->nullable()->comment("注册日期");
            $table->integer('style')->default(0)->default(0)->comment("0=移动素皮肤，1=pc皮服");
            $table->string('limitgameplat',128)->default('')->comment("限制游戏平台存放json数据");
            $table->tinyInteger('is_import')->default(0)->comment("是否导入用户");
            $table->string('gift_code')->default('')->comment('推广码');
            $table->integer('overweight')->default(0)->comment('升级加码流水');
            $table->string('google_img',255)->default('')->commit("");
            $table->tinyInteger('bind_google_status')->default(0)->comment("是否绑定google验证码");
            $table->tinyInteger('is_notransfer')->default(1)->comment("是否开启免转钱包");
            $table->tinyInteger('win_lose_agent')->default(0)->comment("是否负盈利代理");
            $table->tinyInteger('enable_wind_control')->default(0)->comment("是否启用自助出款风控");
            $table->bigInteger('wind_control_amount')->default(0)->comment("自助出款风控金额");
            $table->tinyInteger('has_software_login')->default(0)->comment("是否用软件登录");
            $table->tinyInteger('is_live_streaming_account')->default(0)->comment("是否直播号");
            $table->tinyInteger('is_hedging_account')->default(0)->comment("是否对冲号");
            $table->tinyInteger('is_auto_dividend')->default(1)->comment("是否自动派发分红");
            $table->tinyInteger('is_withdraw_mobile')->default(0)->comment("提现是否需要手机号");
            $table->string('prefix',4)->default('A')->commit("用户前端前辍");
            $table->string('othercontact',255)->default('')->commit("其它联系方式");
            $table->tinyInteger('is_supplementary_data')->default(0)->commit("是否需要补数据");
            $table->integer('extend_id')->default(0)->comment('扩展ID');
            $table->bigInteger('parent_extend_id')->default(0)->comment("父级扩展ID");
            $table->bigInteger('top_extend_id')->default(0)->comment("直属扩展ID");
            $table->bigInteger('is_forum_user')->default(0)->comment("1=论坛1扩展用户");
            $table->string('forum_username',64)->default('')->comment("论坛帐号");
            $table->tinyInteger('deductions_method')->default(0)->commit("0=根随系统扣费方式,1=前台扣费方式，2=后台扣费方式");
            $table->tinyInteger('self_deductions_method')->default(0)->commit("0=自已根随系统扣费方式,1=自已前台扣费方式，2=自已后台扣费方式");

            $table->rememberToken();
            $table->timestamps();

            $table->index("user_name");
            $table->index("parent_id");
            $table->index("inviteplayerid");
            $table->index("mobile");
            $table->index("rid");
        });

        // 用户资金表
        Schema::create('inf_player_account', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->string('user_name',64)->comment("帐号");       
            $table->integer('carrier_id')->comment("运营商ID");   
            $table->string('prefix',4)->commit("用户前端前辍");     
            $table->bigInteger('top_id')->default(0)->comment("直属ID");
            $table->bigInteger('parent_id')->comment("父级ID"); 
            $table->integer('level')->comment("深度，从1开始"); 
            $table->string('rid',255);
            $table->tinyInteger('is_tester')->default(0)->comment("1,试玩用户，0=正常用户，2=带玩用户");
            $table->bigInteger('balance')->comment("帐户余额");
            $table->bigInteger('frozen')->comment("帐户冻结金额");
            $table->bigInteger('agentbalance')->comment("代理帐户余额");
            $table->bigInteger('agentfrozen')->comment("代理帐户冻结金额");
            $table->bigInteger('matchbalance')->default(0)->comment("比赛金额");
            $table->timestamps();

            $table->index("user_name");
            $table->index("parent_id");
            $table->index("player_id");
            $table->index("rid");
        });

        // 用户设置表
        Schema::create('conf_player_setting', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('player_id')->comment("玩家ID");                   
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix',4)->commit("用户前端前辍");
            $table->bigInteger('top_id')->default(0)->comment("直属ID");
            $table->bigInteger('parent_id')->comment("父级ID");
            $table->string('rid',255);
            $table->tinyInteger('is_tester')->default(0)->comment("1,试玩用户，0=正常用户，2=带玩用户");
            $table->integer('level')->comment("深度，从1开始");
            $table->string('user_name',64)->comment("帐号");
            $table->Integer('lottoadds')->comment("彩票赔率");         
            $table->decimal('earnings',15,2)->default(0.00)->comment("分红方式1游戏分类分红比例");
            $table->Integer('guaranteed')->default(40)->comment("默认保底金额");
            
            $table->timestamps();

            $table->index("user_name");
            $table->index("parent_id");
            $table->index("player_id");
            $table->index("rid");
        });

        // 会员帐变表
        Schema::create('inf_player_transfer', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix',4)->commit("站点");
            $table->string('rid',255)->nullable();
            $table->bigInteger('top_id')->default(0)->comment("直属ID");                             
            $table->bigInteger('parent_id')->comment("父级ID");                                      
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->tinyInteger('is_tester')->default(0)->comment("1,试玩用户，0=正常用户，2=带玩用户");    
            $table->integer('level')->comment("深度，从1开始");
            $table->string('user_name',64)->comment("帐号");
            $table->bigInteger('from_id')->default(0)->comment("来源用户ID");       
            $table->bigInteger('to_id')->default(0)->comment("接收用户ID");
            $table->string('from_user_name',64)->default('')->comment("来源用户帐号");       
            $table->string('to_user_name',64)->default('')->comment("接收用户帐号");                          
            $table->integer('platform_id')->default(0)->comment("平台ID");              
            $table->tinyInteger('mode')->comment("模式 1,增加，2,减少. 3,不变"); 
            $table->string('type',32)->comment("帐变类型");                                        
            $table->string('type_name',32)->comment("帐变名称");
            $table->string('en_type_name',32)->comment("英文帐变名称");
            $table->string('project_id',64)->default('')->comment("订单ID");
            $table->bigInteger('day_m')->comment("处理月份");                                        
            $table->bigInteger('day')->comment("处理日期");                                         
            $table->bigInteger('amount')->comment("处理金额");                                      
            $table->bigInteger('before_balance')->comment("之前余额");                               
            $table->bigInteger('balance')->comment("之后余额");                                     
            $table->bigInteger('before_frozen_balance')->comment("之前冻结金额");                     
            $table->bigInteger('frozen_balance')->comment("之后冻结金额");
            $table->bigInteger('before_agent_balance')->comment("之前余额");                               
            $table->bigInteger('agent_balance')->comment("之后余额");                                     
            $table->bigInteger('before_agent_frozen_balance')->comment("之前冻结金额");                     
            $table->bigInteger('agent_frozen_balance')->comment("之后冻结金额");  
            $table->integer('admin_id')->default(0)->comment("操作人员");                          
            $table->integer('activity_id')->default(0)->comment("活动ID"); 
            $table->tinyInteger('game_category')->default(0)->comment("游戏分类，1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼");
            $table->tinyInteger('is_statistical')->default(1)->comment("是否统计");                       
            $table->integer('stat_time')->default(0)->comment("统计时间");
            $table->string('remark',64)->default('')->comment("备注");
            $table->string('remark1',64)->default('')->comment("备注1");
            $table->string('remark2',64)->default('')->comment("三方游戏余额");                          
            $table->timestamps();

            $table->index("prefix");
            $table->index("from_user_name");
            $table->index("to_user_name");
            $table->index("user_name");
            $table->index("parent_id");
            $table->index("player_id");
            $table->index("rid");
        });

        //游戏帐号表
        Schema::create('inf_player_game_account', function (Blueprint $table) {
            $table->increments('account_id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix',4)->commit("站点");
            $table->integer('main_game_plat_id')->comment("平台ID");   
            $table->string('main_game_plat_code',32)->comment("平台编码");
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->string('rid',255)->nullable();
            $table->string('account_user_name',64)->comment("三方游戏帐号");
            $table->string('password',64)->nullable()->comment("三方游戏密码");                          
            $table->decimal('balance',15,2)->default(0.00)->comment("三方游戏余额 非实时");
            $table->tinyInteger('exist_transfer')->default(0)->comment("是否有转帐记录1=有，0=没有");                  
            $table->tinyInteger('is_locked')->default(0)->comment("是否转帐锁定，1=是，0=否");                        
            $table->tinyInteger('is_need_repair')->default(0)->comment("是否维护，1=是，0=否");                  
            $table->timestamps();

            $table->index("account_user_name");
            $table->index("player_id");
            $table->index(["main_game_plat_id","account_user_name"],'m_id_a');
            $table->index(["main_game_plat_code","account_user_name"],'m_code_a');
        });

        // 用户邀请链接
        Schema::create('inf_player_invite_code', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->string('username', 32)->comment("用户名");
            $table->string('prefix',4)->default('A')->commit("用户前端前辍");
            $table->string('rid', 255)->default('');
            $table->tinyInteger('type')->default(3)->comment("3,会员，2,代理");
            $table->Integer('lottoadds')->default(1800)->comment("彩票赔率");              
            $table->decimal('earnings',15,2)->default(0.00)->comment("分红比例"); 
            $table->string('code', 32)->default('')->comment("邀请码");
            $table->string('domain', 64)->default('')->comment("域名");                                   
            $table->integer('total_register')->default(0)->comment("总注册");                              
            $table->tinyInteger('is_tester')->default(0)->comment('1,试玩用户，0=正常用户，2=带玩用户');
            $table->tinyInteger('status')->default(1)->comment('状态 1=未过期，0=已过期');
            $table->integer('expired_at')->default(0)->comment('过期时间'); 
            $table->timestamps();

            $table->index("player_id");
            $table->index("rid");
            $table->index("code");
        });

        //用户绑定的银行卡
        Schema::create('inf_player_bank_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->string('user_name',64)->comment("用户名");
            $table->string('card_owner_name', 64)->comment("姓名");                     
            $table->integer('bank_Id')->comment("银行卡类型");                         
            $table->string('card_account', 32)->comment("卡号");                        
            $table->integer('status')->default(1)->comment("1=有效，0=无效");
            $table->string('prefix', 4)->comment("前辍");            
            $table->integer('is_default')->default(0)->comment("1=默认出款银行卡 0=非默认");            
            $table->timestamps();

            $table->index("player_id");
        });

        //用户绑定的支付宝
        Schema::create('inf_player_alipay', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->string('prefix', 4)->comment("前辍"); 
            $table->string('user_name',64)->comment("用户名");
            $table->string('real_name',64)->comment("姓名");                                     
            $table->string('account', 32)->comment("帐号");                        
            $table->integer('status')->default(1)->comment("1=有效，0=无效");    
            $table->timestamps();

            $table->index("player_id");
        });

        //用户绑定的公链地址
        Schema::create('inf_player_digital_address', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->bigInteger('player_id')->comment("玩家ID");                      
            $table->string('address', 64)->comment("地址");                    
            $table->integer('status')->default(1)->comment("1=有效，0=无效");
            $table->integer('sort')->default(0)->comment("排序");             
            $table->integer('is_default')->default(0)->comment("1=默认出款地址 0=非默认"); 
            $table->tinyInteger('win_lose_agent')->default(0)->comment("是否负盈利代理");
            $table->tinyInteger('type')->default(0)->comment("1=Trc20，2=Erc20 ,3=okpay ,4=goPay,5=gcash");
            $table->string('prefix', 4)->comment("前辍");
            $table->timestamps();

            $table->index("player_id");
        });

        Schema::create('map_carrier_player_level_pay_channel', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('carrier_channle_id')->comment("商户支付渠道ID");
            $table->integer('player_level_id')->comment("会员等级");                     
            $table->timestamps();
        });

        Schema::create('map_carrier_player_level_bank', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('carrier_bank_id')->comment("商户银行卡ID");
            $table->integer('player_level_id')->comment("会员等级");                     
            $table->timestamps();
        });

        Schema::create('map_carrier_player_collection_card', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('collection_card_id')->comment("运营商银行卡ID");
            $table->bigInteger('player_id')->comment("玩家ID");                     
            $table->timestamps();
        });

        Schema::create('inf_player_message', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->tinyInteger('type')->default(1)->comment("1=会员，2=代理");
            $table->string('title',64)->comment("标题"); 
            $table->string('content',255)->comment("内容");                      
            $table->tinyInteger('is_read')->comment("1=已读，0=未读"); 
            $table->integer('admin_id')->comment("发送人"); 
            $table->timestamps();

            $table->index("player_id");
        });

        Schema::create('inf_player_recent', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->string('game_id',32)->comment("游戏ID");
            $table->integer('main_game_plat_id')->comment("游戏平台ID");
            $table->string('game_code',32)->comment("游戏编码");
            $table->string('game_moblie_code',32)->comment("移动游戏编码");
            $table->integer('game_category')->comment("1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼");
            $table->timestamps();

            $table->index("player_id");
        });

        //用户层级表
        Schema::create('inf_player_ip_black', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('ips',255)->default('')->comment("ip地址");
            $table->timestamps();
        });

        Schema::create('inf_player_betflow_calculate', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->integer('game_category')->comment("1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼");
            $table->integer('betflow_calculate_rate')->comment("投注计算百分比");
            $table->timestamps();

            $table->index("player_id");
        });

        //用户游戏收藏记录表
        Schema::create('inf_player_game_collect', function (Blueprint $table) {
            $table->increments('id');   
            $table->bigInteger('player_id')->comment("用户ID");
            $table->string('user_name',64)->comment("帐号");
            $table->integer('top_id')->default(0)->comment("直属ID");
            $table->integer('parent_id')->comment("父级ID");
            $table->string('rid',255)->nullable();
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('game_category')->comment("游戏分类");
            $table->integer('is_self')->default(0)->comment("1=自开，0=三方");  
            $table->string('game_id',32)->comment("游戏ID");  
            $table->timestamps();

            $table->index("player_id");
        });

        //用户彩金领取中心
        Schema::create('inf_player_receive_gift_center', function (Blueprint $table) {
            $table->increments('id');
            $table->string('orderid',64)->default('')->comment("订单号");
            $table->integer('carrier_id')->comment("运营商ID");   
            $table->bigInteger('player_id')->comment("用户ID");
            $table->string('user_name',64)->comment("帐号");
            $table->integer('top_id')->default(0)->comment("直属ID");
            $table->integer('parent_id')->comment("父级ID");
            $table->string('rid',255)->nullable();
            $table->integer('type')->comment("彩金类型");
            $table->bigInteger('amount')->default(0)->comment("金额");
            $table->bigInteger('limitbetflow')->comment("流水限制金额");
            $table->string('betflow_limit_category',255)->default('')->comment("1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼,多个用逗号分隔");
            $table->string('betflow_limit_main_game_plat_id',64)->default('')->comment("多个用逗号隔开");
            $table->integer('invalidtime')->comment("到期时间");
            $table->integer('receivetime')->default(0)->comment("领取时间");
            $table->tinyInteger('status')->default(0)->comment("0=未领取,1=已领取,2=已失败"); 
            $table->string('remark',255)->default('')->comment('备注详情'); 
            $table->timestamps();

            $table->index("player_id");
        });
    }

    public function down()
    {
        Schema::dropIfExists('inf_player');
        Schema::dropIfExists('inf_player_account');
        Schema::dropIfExists('conf_player_setting');
        Schema::dropIfExists('inf_player_transfer');
        Schema::dropIfExists('inf_player_game_account');
        Schema::dropIfExists('inf_player_invite_code');
        Schema::dropIfExists('inf_player_bank_cards');
        Schema::dropIfExists('map_carrier_player_level_pay_channel');
        Schema::dropIfExists('map_carrier_player_level_bank');
        Schema::dropIfExists('map_carrier_player_collection_card');
        Schema::dropIfExists('inf_player_message');
        Schema::dropIfExists('inf_player_recent');
        Schema::dropIfExists('inf_player_ip_black');
        Schema::dropIfExists('inf_player_digital_address');
        Schema::dropIfExists('inf_player_betflow_calculate');
        Schema::dropIfExists('inf_player_game_collect');
        Schema::dropIfExists('inf_player_receive_gift_center');
        Schema::dropIfExists('inf_player_alipay');
    }
}