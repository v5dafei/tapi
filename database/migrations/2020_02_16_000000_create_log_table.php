<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
//use Brokenice\LaravelMysqlPartition\Models\Partition;
//use Brokenice\LaravelMysqlPartition\Schema\Schema;

class CreateLogTable extends Migration
{
    public function up()
    {
        // 额度变动表
        Schema::create('log_carrier_remainquota', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->decimal('amount',15,4)->comment("变化额度");
            $table->string('game_account',64)->default('')->comment("游戏帐号");
            $table->string('before_remainquota',32)->default('')->comment("变化前额度");
            $table->string('remainquota',32)->default('')->comment("变化后额度");
            $table->tinyInteger('direction')->comment("1=创建商户时增加,3=管理员调整额度增加,4=管理员调整额度减少,5=转入中心钱包,6=转出中心钱包,8=转入中心钱包失败,9=转出中心钱包失败");
            $table->string('mark',255)->comment("备注");
            $table->timestamps();

        });

        // 投注记录表
        Schema::create('log_player_bet_flow', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('player_id')->comment("用户ID");
            $table->tinyInteger('is_tester')->comment("1,试玩用户，0=正常用户，2=带玩用户");                     
            $table->string('user_name',64)->comment("帐号");
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix', 4)->comment("前辍");
            $table->string('game_id',32)->default('')->comment("三方游戏ID");
            $table->integer('lott_id')->default(0)->comment("彩票ID");
            $table->string('game_name',32)->comment("名称");
            $table->integer('main_game_plat_id')->comment("游戏平台ID");
            $table->string('main_game_plat_code',32)->comment("主平台代码");
            $table->tinyInteger('game_category')->comment("1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼");
            $table->string('game_flow_code',64)->comment("注单号");
            $table->integer('game_status')->comment("0=未结算, 1=已结算，2=注销");
            $table->decimal('bet_amount',15,4)->default(0.0000)->comment("投注金额");
            $table->decimal('available_bet_amount',15,4)->default(0.0000)->comment("有效投注额");
            $table->decimal('company_win_amount',15,4)->default(0.0000)->comment("公司输赢");
            $table->text('bet_info')->comment("投注详情");
            $table->string('issue',32)->comment("彩票期号");
            $table->string('opendata',32)->comment("开奖号码");
            $table->text('api_data')->comment("三方游戏拉单原生");
            $table->tinyInteger('bet_flow_available')->comment("0=未知，1=有效，2=无效");
            $table->tinyInteger('isFeatureBuy')->default(0)->comment("1=购买的免费旋转,0=否");
            $table->tinyInteger('multi_spin_game')->default(0)->comment("1=是多旋转游戏,0=否");
            $table->integer('day')->comment("日期");
            $table->integer('bet_time')->comment("投注时间");
            $table->Integer('stat_time')->default(0)->comment("统计时间");
            $table->tinyInteger('is_loss')->default(0)->comment("1=玩家输,2=玩家赢，0=无输赢");
            $table->tinyInteger('whether_recharge')->default(0)->comment("1=计算有效流水");
            $table->tinyInteger('is_material')->default(0)->comment("1=素材线路,0=正常线路");
            $table->tinyInteger('is_trygame')->default(0)->comment("1=是试玩游戏,0=非试玩游");
            $table->string('account_user_name',64)->default('')->comment("抓单时的游戏帐号");
            $table->timestamps();

            $table->index("player_id");
            $table->index("prefix");
            $table->index("created_at");
            $table->index("bet_time");
            $table->index("game_flow_code");
            $table->index(['user_name','bet_time'],'u_b');
            $table->index(['player_id','main_game_plat_id','bet_time']);
            $table->index(['carrier_id','bet_time'],'c_b');
            $table->index(['carrier_id','bet_flow_available','game_status','is_tester','stat_time','main_game_plat_code','id'],'cbgismi');
            $table->index(['carrier_id','game_status','bet_time','game_name','player_id'],'cgbgp');
            $table->index(['carrier_id','game_status','is_tester','bet_time','main_game_plat_code'],'cgibm');
            $table->index(['player_id','isFeatureBuy'],'pi');
            $table->index(['day','player_id','game_category','main_game_plat_id','is_loss','game_id'],'d_p_g_m_i_g');
        });

       /* $time      = strtotime(date('Y-m-d 00:00:00'));
        $partition = [];

        for($i=1;$i<121;$i++){
            $currtime    = $time+$i*86400;
            $partition[] = new Partition('log_player_bet_flow_'.date('Ymd',$currtime), Partition::RANGE_TYPE, $currtime);
        }

        Schema::partitionByRange('log_player_bet_flow', 'bet_time',$partition , true);
        */


        //中间表
        Schema::create('log_player_bet_flow_middle', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('player_id')->comment("用户ID");
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('rid',255)->nullable();
            $table->bigInteger('parent_id')->comment("父级ID");  
            $table->tinyInteger('game_category')->comment("1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼");
            $table->tinyInteger('win_lose_agent')->comment("0=是否负盈利代理 1=负盈利代理");
            $table->decimal('bet_amount',15,4)->default(0.0000)->comment("投注金额");
            $table->decimal('available_bet_amount',15,4)->default(0.0000)->comment("有效投注额");
            $table->decimal('process_available_bet_amount',15,4)->default(0.0000)->comment("处理过后的有效投注");
            $table->decimal('agent_process_available_bet_amount',15,4)->default(0.0000)->comment("代理处理过后的有效投注");
            $table->integer('main_game_plat_id')->comment("游戏平台ID");
            $table->decimal('company_win_amount',15,4)->default(0.0000)->comment("公司输赢");
            $table->tinyInteger('whether_recharge')->default(0)->comment("是否充值 0=未充值 1=已充值");
            $table->tinyInteger('is_live_streaming_account')->default(0)->comment("是否直播号 0=否 1=是");
            $table->integer('number')->default(0)->comment("纪录条数");
            $table->string('prefix', 4)->comment("前辍");
            $table->integer('day')->comment("日期");
            $table->integer('bet_time')->default(0)->comment("最后投注时间");
            $table->Integer('stat_time')->default(0)->comment("统计时间");
            $table->text('bet_flow_ids')->comment("投注详情");
            $table->timestamps();

            $table->index(['player_id','parent_id','game_category','day','stat_time','main_game_plat_id'],'pgds');
            $table->index(['rid','day'],'r_d');
            $table->index(['prefix','player_id'],'p_p');
        });

        //存款记录
        Schema::create('log_player_deposit_pay', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('player_id')->comment("用户ID");
            $table->string('user_name',64)->comment("帐号");
            $table->string('rid',255)->nullable();
            $table->bigInteger('top_id')->default(0)->comment("直属ID");                              
            $table->bigInteger('parent_id')->comment("父级ID");                                       
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('pay_order_number',64)->comment("平台单号");
            $table->string('pay_order_channel_trade_number',64)->default('')->comment("第三方平台单号");
            $table->string('collection',64)->default('')->comment("收款信息");
            $table->string('pay',32)->default('')->comment("付款信息");
            $table->integer('carrier_pay_channel')->default(0)->comment("支付通道ID");
            $table->bigInteger('carrier_bankcard_id')->default(0)->comment("收款银行卡");
            $table->string('deposit_account',32)->default('')->comment("存款人帐号");
            $table->string('deposit_username',8)->default('')->comment("存款人名称");
            $table->string('carrier_digital_address',64)->default('')->comment("数字币收款地址");
            $table->integer('digital_type')->default(0)->comment("1=USDT-Trc20，2=USDT-Erc20");
            $table->integer('bank_id')->default(0)->comment("存款人银行ID");
            $table->string('credential',6)->default('')->comment("凭证");
            $table->bigInteger('fee')->default(0)->comment("存款手续费");
            $table->bigInteger('amount')->comment("存款金额");
            $table->bigInteger('arrivedamount')->comment("到帐金额");
            $table->bigInteger('postamount')->default(0)->comment("后置通道费");
            $table->bigInteger('agent_deduction_amount')->default(0)->comment("代理扣减金额");
            $table->bigInteger('third_fee')->default(0)->comment("三方手续费");
            $table->tinyInteger('status')->default(0)->comment("订单状态 0 订单创建  1 订单支付成功  -1 订单支付失败 -2审核未通过 2订单待审核");
            $table->integer('review_user_id')->default(0)->comment("审核人员ID");
            $table->integer('review_time')->default(0)->comment("审核时间");
            $table->string('desc',255)->default('')->comment("备注信息");
            $table->string('currency',32)->default('')->comment("币种");
            $table->string('txid',128)->default('')->comment("hash值");
            $table->string('activityids')->default('')->comment("申请的活动ID");
            $table->string('depositimg',255)->default('')->comment("充值凭证");
            $table->tinyInteger('is_wallet_recharge')->default(0)->comment("是否钱包充值 1=是 0=否");
            $table->tinyInteger('is_agent')->default(0)->comment("是否代理 1=是 0=否");
            $table->tinyInteger('is_refill')->default(0)->comment("是否代充 1=是 0=否");
            $table->tinyInteger('is_first_recharge')->default(0)->comment("是否首充 1=是 0=否");
            $table->tinyInteger('is_hedging_account')->default(0)->comment("是否对冲号");
            $table->string('prefix', 4)->comment("前辍");
            $table->integer('day')->comment("日期");

            $table->timestamps();

            $table->index("player_id");
            $table->index("pay_order_number");
            $table->index('created_at');
        });

        //提现记录
        Schema::create('log_player_withdraw', function (Blueprint $table) {
            $table->increments('id');
            $table->string('rid',255)->nullable();
            $table->bigInteger('top_id')->default(0)->comment("直属ID");                             
            $table->bigInteger('parent_id')->comment("父级ID");                                       
            $table->string('user_name',64)->comment("帐号"); 
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->integer('level')->comment("用户层级");; 
            $table->integer('carrier_id')->comment("运营商ID"); 
            $table->string('pay_order_number',64)->comment("平台单号");
            $table->string('pay_order_channel_trade_number',64)->default('')->comment("第三方平台单号");      //代付
            $table->integer('carrier_pay_channel')->comment("提现通道ID");
            $table->string('collection',64)->default('')->comment("收款信息");
            $table->string('player_digital_address',64)->default('')->comment("数字币收款地址");
            $table->string('pay',32)->default('')->comment("付款信息");
            $table->string('payment_channel',32)->default('')->comment("付款通道");
            $table->bigInteger('amount')->comment("提现金额");
            $table->bigInteger('real_amount')->comment("真实金额");
            $table->bigInteger('third_fee')->default(0)->comment("三方手续费");
            $table->bigInteger('withdraw_fee')->default(0)->comment("提现手续费");
            $table->integer('review_one_user_id')->comment("审核人员ID");
            $table->integer('review_one_time')->default(0)->comment("审核时间");
            $table->integer('review_two_user_id')->comment("审核人员ID");
            $table->integer('review_two_time')->default(0)->comment("审核时间");
            $table->integer('third_part_pay_id')->default(0)->comment("三方通道ID");
            $table->tinyInteger('status')->comment("付款状态 0 等待审核  1 代付成功  -1代付失败 2=人工成功,3=审核失败，4=审核成功,5=代付中,6=代付处理中,7=取消");
            $table->tinyInteger('type')->default(0)->comment("0=未使用，1=Trc20，2=Erc20 ,3=okpay ,4=goPay,5=gcash,6=topay,7=ebpay,8=wanbpay,9=jdpay,10=kdpay,11=nopay,12=bobipay");
            $table->string('currency',32)->default('CNY')->comment("提现的币种");
            $table->string('player_bank_id',32)->comment("收款银行");
            $table->string('player_alipay_id',32)->default('')->comment("支付宝");
            $table->tinyInteger('review_status')->default(-1)->comment("代理审核状态 -1=无需审核 ，0=待审核 ,1=审核通过，2=取消");
            $table->string('review_agent_user_name')->default('')->comment("审核代理帐号");
            $table->string('remark')->comment("后台备注");
            $table->string('frontremark')->default('')->comment("前台备注");
            $table->tinyInteger('is_agent')->default(0)->comment("是否代理 1=是 0=否");
            $table->string('prefix', 4)->comment("前辍");
            $table->tinyInteger('is_suspend')->default(0)->comment("1=挂起,0=未挂起");
            $table->integer('arrival_time')->default(0)->comment("到帐时间");
            $table->tinyInteger('is_hedging_account')->default(0)->comment("是否对冲号");
            $table->tinyInteger('is_oneandone_withdrawal')->default(0)->comment("是否1+1活动提现");
            $table->tinyInteger('is_fraud_recharge')->default(0)->comment("是否P图");
            $table->tinyInteger('is_auto_pay')->default(0)->comment("是否自动出款 1=是 0=否");

            
            $table->timestamps();

            $table->index("player_id");
            $table->index("pay_order_number");
            $table->index('created_at');
        });

        //登录记录
        Schema::create('log_player_login', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->string('prefix', 4)->comment("前辍"); 
            $table->string('user_name',64)->comment("帐号"); 
            $table->integer('carrier_id')->comment("运营商ID"); 
            $table->string('login_ip',15)->comment("登录IP");
            $table->string('login_domain',64)->comment("登录域名");
            $table->integer('login_time')->comment("登录时间");
            $table->string('login_location',64)->comment("登录地点");
            $table->string('osName',64)->comment("登录设备");
            $table->string('fingerprint',128)->comment("指纹或设备号");
            $table->timestamps();

            $table->index("player_id");
        });

        //软件登录记录
        Schema::create('log_player_software_login', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID"); 
            $table->string('prefix',4)->comment("站点");
            $table->bigInteger('player_id')->comment("玩家ID"); 
            $table->string('user_name',64)->comment("帐号"); 
            $table->integer('difftime')->comment("相差时间");
            $table->timestamps();

            $table->index("player_id");
        });

        //游戏平台转帐记录
        Schema::create('log_player_transfer_casino', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('player_id')->comment("玩家ID");
            $table->string('account_user_name',255)->comment("玩家帐号");
            $table->integer('carrier_id')->comment("运营商ID"); 
            $table->string('user_name',64)->comment("帐号"); 
            $table->integer('main_game_plat_id')->comment("游戏平台ID"); 
            $table->string('main_game_plat_code',32)->comment("游戏平台编码"); 
            $table->tinyInteger('type')->comment("1=转入游戏平台 2=转出游戏平台");     
            $table->tinyInteger('status')->comment("1=成功 2=失败,0=未知");
            $table->integer('price')->comment("转帐金额");
            $table->string('transferid',64)->comment("转帐单号");
            $table->integer('admin_id')->default(0)->comment("操作人");
            $table->timestamps();

            $table->index("player_id");
        });

        //流水限制列表明
        Schema::create('log_player_withdraw_flow_limit', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->default(0)->comment("运营商ID");
            $table->integer('act_id')->default(0)->comment("活动ID");
            $table->bigInteger('top_id')->default(0)->comment("直属ID");           
            $table->bigInteger('parent_id')->comment("父级ID");                   
            $table->string('rid',255)->nullable();
            $table->bigInteger('player_id')->default(0)->comment("玩家ID");
            $table->string('user_name',64)->comment("帐号");
            $table->string('betflow_limit_category',255)->default('')->comment("0 =不限,1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼,多个用逗号分隔");
            $table->string('betflow_limit_main_game_plat_id',64)->default('')->comment("多个用逗号隔开");
            $table->bigInteger('limit_amount')->default(0)->comment("限制金额");  
            $table->integer('limit_type')->comment("限制类型，1=存款，2=活动，3=充值理赔, 4=提现理赔,5=手动礼金,6=普通理赔,7=收到打赏,8=幸运轮盘,9=升级礼金,10=生日礼金,11=代充,12=后台添加,13=周礼金，14=月礼金,15=代理发放礼金,16=注册礼金,17=兑换码");
            $table->bigInteger('complete_limit_amount')->default(0)->comment("完成金额");
            $table->tinyInteger('is_finished')->default(0)->comment("是否完成，1=是，0=否");
            $table->string('remark',64)->default('')->comment('备注');
            $table->integer('operator_id')->comment("操作人员id");
            $table->timestamps();

            $table->index("player_id");
        });

        //数字币回调信息表
        Schema::create('log_digital_callback', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('tokenAddress', 64)->comment("token地址");
            $table->string('address', 64)->comment("收款地址");
            $table->string('tokenSymbol', 8);
            $table->string('txid', 128);
            $table->integer('time');
            $table->tinyInteger('confirmations');
            $table->string('tokenValue', 64);
            $table->string('value', 64);
            $table->string('coin', 16);
            $table->integer('height');
            $table->integer('status')->default(0)->comment("1=已处理，0=未处理");
            $table->timestamps();

            $table->index(["txid","address"]);
        });

        //赠送视频表
        Schema::create('log_carrier_admin_log', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->bigInteger('carrieruser_id')->comment("管理员ID");
            $table->string('user_name',64)->comment("用户帐号");
            $table->integer('group_id')->comment("分组id");
            $table->integer('actionTime')->comment("操作时间");
            $table->bigInteger('actionIP')->comment("操作ip");
            $table->string('action',32)->comment("操作描述");
            $table->text('params')->comment("参数");
            $table->integer('day')->comment("日期");
            $table->integer('permissionsid')->comment("权限ID");
            $table->string('routename',64)->comment("路由");
            $table->timestamps();

            $table->index(['carrier_id','carrieruser_id','actionTime','actionIP'],'c_p');
        });

        //赠送视频表
        Schema::create('log_admin_session', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('uid')->comment("用户ID");
            $table->string('usr',16)->comment("用户帐号");
            $table->tinyInteger('is_online')->default(1)->comment("是否在线，判断是否在线除判断这个值外，还应该判断最后访问时间");
            $table->string('session_key',32)->nullable()->comment("用户帐号");
            $table->bigInteger('login_ip')->default(0)->comment("登录IP");
            $table->string('auth_key',32)->default('')->comment("");
            $table->string('browser',32)->default('')->comment("浏览器类型");
            $table->string('os',32)->default('')->comment("操作系统类型");
            $table->tinyInteger('is_mobile')->default(1)->comment("是否移动设备");
            $table->string('domain',64)->default('')->comment("登录域名");
            $table->string('location',32)->default('')->comment("登录地址");
            $table->integer('login_time')->default(0)->comment("登录时间");
            $table->integer('access_time')->default(0)->comment("最后访问时间");

            $table->timestamps();

            $table->index('uid');
            $table->index('usr');
            $table->index('session_key');
            $table->index('login_time');
            $table->index('access_time');
            $table->index('login_ip');
            $table->index('is_mobile');
            $table->index('domain');
        });

        //会员操作日志表
        Schema::create('log_player_operate', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->bigInteger('player_id')->comment("用户ID");
            $table->string('user_name',64)->comment("用户帐号");
            $table->tinyInteger('type')->comment("0 全部 1 申请提现 2 绑定收款信息 3 登录密码修改 4=支付密码修改 5 修改下级赔率返水修息");
            $table->bigInteger('ip')->comment("ip地址");
            $table->string('desc',128)->default('')->comment("详情");
            $table->timestamps();

            $table->index("player_id");
            $table->index("type");
        });

        //短信发送日志
        Schema::create('log_carrier_sms', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix', 4)->comment("前辍");
            $table->integer('sms_passage_id')->comment("通道ID");
            $table->string('mobile',16)->default('')->comment("手机号");
            $table->string('ip',15)->default('')->comment("ip地址"); 
            $table->tinyInteger('status')->default(0)->comment("0=提交失败，1=提交成功，2=发送成功 3=发送失败");
            $table->string('uniquire_id',64)->comment("唯一识别码");
            $table->timestamps();

            $table->index("mobile");
            $table->index("status");
            $table->index("ip");
            $table->index("uniquire_id");
        });

        //三方支付异步通知日志
        Schema::create('log_third_part_pay_callback', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('third_part_pay_id')->comment("三方支付通道ID");
            $table->string('ip',15)->default('')->comment("ip地址"); 
            $table->tinyInteger('type')->comment("1=代收，2=代付");
            $table->string('url',500)->default('')->comment("异步通知完整路径"); 
            $table->string('orderid',128)->default('')->comment("订单号");
            $table->timestamps();

            $table->index("third_part_pay_id");
            $table->index("orderid");
            $table->index("ip");
            $table->index("type");
        });

        //浏览器指纹
        Schema::create('log_player_fingerprint', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('prefix')->comment("站点");
            $table->integer('player_id')->comment("用户ID");
            $table->string('fingerprint',64)->comment("指纹"); 
            $table->timestamps();

            $table->index("prefix");
            $table->index("player_id");
            $table->index("fingerprint");
        });

        //签倒日志
        Schema::create('log_sign_in', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('prefix')->comment("站点");
            $table->integer('player_id')->comment("用户ID");
            $table->string('user_name',64)->comment("用户帐号");
            $table->integer('day')->comment("签到日期"); 
            $table->tinyInteger('is_continuous')->comment("1=连续，0=非连续");
            $table->timestamps();

            $table->index("prefix");
            $table->index("player_id");
            $table->index("user_name");
            $table->index("day");
        });

        //签倒日志
        Schema::create('log_sign_in_receive', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->integer('player_id')->comment("用户ID");
            $table->integer('number')->comment("签到天数"); 
            $table->integer('amount')->comment("领取金额");
            $table->integer('receiveday')->default(0)->comment("连续签倒领取的天数");
            $table->integer('day')->comment("领取日期"); 
            $table->timestamps();

            $table->index("player_id");
            $table->index("number");
            $table->index("day");
        });

        //排行榜
        Schema::create('log_ranking_list', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->text('content')->comment("排行内容");
            $table->integer('day')->comment("排行日期");
            $table->integer('endday')->comment("排行结束日期");  
            $table->tinyInteger('status')->default(0)->comment("状态 1=启用，0=禁用");
            $table->string('prefix',4)->comment("站点");
            
            $table->timestamps();
        });

        //闯关记录表
        Schema::create('log_player_break_through', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->default(0)->comment("运营商ID");
            $table->string('prefix',4)->default('A')->commit("站点");
            $table->bigInteger('top_id')->default(0)->comment("直属ID");           
            $table->bigInteger('parent_id')->comment("父级ID");                   
            $table->string('rid',255)->nullable();
            $table->bigInteger('player_id')->default(0)->comment("玩家ID");
            $table->string('user_name',64)->comment("帐号");
            $table->integer('game_category')->default(0)->comment("限制类型 1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼");
            $table->integer('day')->comment("领取日期"); 
            $table->bigInteger('limit_amount')->default(0)->comment("流水限制");
            $table->bigInteger('amount')->default(0)->comment("金额");
            $table->integer('act_id')->default(0)->comment("活动ID");
            $table->integer('sort')->default(0)->comment("排序");
            
            $table->timestamps();

            $table->index("player_id");
            $table->index("day");
        });

        //体验券推广列表
        Schema::create('log_player_gift_code', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->default(0)->comment("运营商ID");
            $table->bigInteger('top_id')->default(0)->comment("直属ID");           
            $table->bigInteger('parent_id')->comment("父级ID");                   
            $table->string('rid',255)->nullable();
            $table->bigInteger('player_id')->default(0)->comment("玩家ID");
            $table->integer('day')->comment("注册日期");
            $table->string('user_name',64)->comment("帐号");
            $table->bigInteger('limit_amount')->default(0)->comment("流水限制");
            $table->bigInteger('amount')->default(0)->comment("金额");
            $table->string('giftcode',16)->comment("体验券");
            $table->string('betflow_limit_category',255)->default('')->comment("0 =不限,1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼,多个用逗号分隔");
            $table->string('betflow_limit_main_game_plat_id',64)->default('')->comment("多个用逗号隔开");
            $table->tinyInteger('type')->comment("状态 1=体验券，2=兑换券");
            $table->tinyInteger('is_recharge')->default(0)->comment("状态 0=未充值，1=已充值"); 
            $table->string('prefix',4)->comment("网站");
            $table->timestamps();

            $table->index("player_id");
            $table->index("day");
        });

        //人头费列表
        Schema::create('log_player_capitation_fee', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->default(0)->comment("运营商ID");
            $table->string('prefix',4)->default('A')->commit("站点");
            $table->bigInteger('top_id')->default(0)->comment("直属ID");           
            $table->bigInteger('parent_id')->comment("父级ID");                  
            $table->string('rid',255)->nullable();
            $table->bigInteger('player_id')->default(0)->comment("玩家ID");
            $table->string('user_name',64)->comment("帐号");
            $table->integer('day')->comment("领取日期");
            $table->bigInteger('amount')->default(0)->comment("领取金额");
            $table->tinyInteger('status')->default(0)->comment("状态 0=待审核  1=已审核，2=已发放,-1=已拒绝");
            $table->integer('admin_id')->default(0)->comment("操作人员id");
            $table->string('remark',64)->default('备注')->comment("玩家ID");
            $table->timestamps();

            $table->index("parent_id");
            $table->index("player_id");
            $table->index("day");
        });

        //登录TOKEN表
        Schema::create('log_player_token', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('player_id')->comment("用户ID");
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('user_name',64)->comment("帐号");
            $table->string('token',500)->comment("token");
            $table->integer('effectiveTime')->comment("有效截止时间");
            $table->timestamps();

            $table->index("user_name");
            $table->index("token");
            $table->index("player_id");
        });

        //保底通宝记录
        Schema::create('log_player_commission_tongbao', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix', 4)->comment("前辍");
            $table->bigInteger('player_id')->comment("用户ID");
            $table->string('rid',255)->nullable();
            $table->bigInteger('parent_id')->comment("父级ID");
            $table->bigInteger('performance')->comment("业绩");  
            $table->decimal('scale',15,4)->default(0.0000)->comment("比例");
            $table->bigInteger('receive_player_id')->comment("获取玩家ID");
            $table->bigInteger('amount')->comment("获取的金额");
            $table->integer('day')->comment("日期");
            $table->timestamps();

            $table->index('receive_player_id');
            $table->index('rid');
        });

        //保底通宝记录
        Schema::create('log_player_real_commission_tongbao', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix', 4)->comment("前辍");
            $table->bigInteger('player_id')->comment("用户ID");
            $table->string('rid',255)->nullable();
            $table->bigInteger('parent_id')->comment("父级ID");
            $table->bigInteger('performance')->comment("业绩");  
            $table->decimal('scale',15,4)->default(0.0000)->comment("比例");
            $table->bigInteger('receive_player_id')->comment("获取玩家ID");
            $table->bigInteger('amount')->comment("获取的金额");
            $table->integer('day')->comment("日期");
            $table->timestamps();

            $table->index('receive_player_id');
            $table->index('rid');
        });

        //分红通宝记录
        Schema::create('log_player_dividends_tongbao', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix', 4)->comment("前辍");
            $table->bigInteger('player_id')->comment("用户ID");
            $table->string('rid',255)->nullable();
            $table->bigInteger('parent_id')->comment("父级ID");  
            $table->bigInteger('performance')->comment("业绩");
            $table->decimal('scale',15,4)->default(0.0000)->comment("比例");
            $table->bigInteger('receive_player_id')->comment("获取玩家ID");
            $table->bigInteger('amount')->comment("获取的金额");
            $table->integer('day')->comment("日期");
            $table->timestamps();

            $table->index('receive_player_id');
            $table->index('rid');
        });

        //分红通宝记录
        Schema::create('log_player_real_dividends_tongbao', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix', 4)->comment("前辍");
            $table->bigInteger('player_id')->comment("用户ID");
            $table->string('rid',255)->nullable();
            $table->bigInteger('parent_id')->comment("父级ID");  
            $table->bigInteger('performance')->comment("业绩");
            $table->decimal('scale',15,4)->default(0.0000)->comment("比例");
            $table->bigInteger('receive_player_id')->comment("获取玩家ID");
            $table->bigInteger('amount')->comment("获取的金额");
            $table->integer('day')->comment("日期");
            $table->timestamps();

            $table->index('receive_player_id');
            $table->index('rid');
        });

        //中间表
        Schema::create('log_player_middle_returnwater', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->string('rid',255)->nullable();
            $table->bigInteger('amount')->comment("金额");
            $table->tinyInteger('status')->default(0)->comment("1=已领取，0=未领取");
            $table->tinyInteger('game_category')->default(0)->comment("游戏类型 1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼");
            $table->timestamps();

            $table->index("player_id");
            $table->index("status");
        });

        // 游戏点击列表
        Schema::create('log_games_hot', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商id");
            $table->string('main_game_plat_code',32)->default('')->comment("主平台代码");
            $table->string('prefix',4)->commit("用户前端前辍");
            $table->string('game_id',32)->default('')->comment("游戏ID");
            $table->integer('sort')->default(0)->comment("排序");
            $table->timestamps();

            $table->index("game_id");
        });

        //用户升级保级日志表
        Schema::create('log_player_level_update', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->default(0)->comment("运营商ID");   
            $table->bigInteger('player_id')->comment("用户ID");
            $table->string('user_name',64)->comment("帐号");
            $table->tinyInteger('type')->comment("状态 1=升级  2=降级"); 
            $table->integer('time')->comment("变动时间");
            $table->integer('day')->comment("日期");
              
            $table->timestamps();

            $table->index("player_id");
        });

        // 银行卡统计数据
        Schema::create('log_bank_stat', function (Blueprint $table) {
            $table->increments('id');
            $table->string('banknumber',30)->comment("银行卡号");
            $table->timestamps();

            $table->index("banknumber");
        });

        // 支付宝统计数据
        Schema::create('log_alipay_stat', function (Blueprint $table) {
            $table->increments('id');
            $table->string('banknumber',30)->comment("支付宝帐号");
            $table->timestamps();

            $table->index("banknumber");
        });

        // P图IP及指纹
        Schema::create('log_fraud_recharge', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ip',15)->default('')->comment("IP地址");
            $table->string('fingerprint',64)->default('')->comment("指纹");
            $table->tinyInteger('type')->comment("状态 1=IP  2=指纹"); 
            $table->timestamps();
        });

        //更新版本号
        Schema::create('log_prefix_version', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment("运营商ID");
            $table->string('prefix',4)->comment("网站");
            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('log_carrier_remainquota');
        Schema::dropIfExists('log_player_bet_flow');
        Schema::dropIfExists('log_player_deposit_pay');
        Schema::dropIfExists('log_player_withdraw');
        Schema::dropIfExists('log_player_login');
        Schema::dropIfExists('log_player_transfer_casino');
        Schema::dropIfExists('log_player_withdraw_flow_limit');
        Schema::dropIfExists('log_player_bet_flow_middle');
        Schema::dropIfExists('log_digital_callback');
        Schema::dropIfExists('log_carrier_admin_log');
        Schema::dropIfExists('log_admin_session');
        Schema::dropIfExists('log_player_operate');
        Schema::dropIfExists('log_carrier_sms');
        Schema::dropIfExists('log_third_part_pay_callback');
        Schema::dropIfExists('log_sign_in');
        Schema::dropIfExists('log_sign_in_receive');
        Schema::dropIfExists('log_player_fingerprint');
        Schema::dropIfExists('log_player_break_through');
        Schema::dropIfExists('log_player_gift_code');
        Schema::dropIfExists('log_ranking_list');
        Schema::dropIfExists('log_player_capitation_fee');
        Schema::dropIfExists('log_player_token');
        Schema::dropIfExists('log_player_commission_tongbao');
        Schema::dropIfExists('log_player_real_commission_tongbao');
        Schema::dropIfExists('log_player_dividends_tongbao');
        Schema::dropIfExists('log_player_real_dividends_tongbao'); 
        Schema::dropIfExists('log_player_software_login');
        Schema::dropIfExists('log_player_middle_returnwater');
        Schema::dropIfExists('log_games_hot');
        Schema::dropIfExists('log_player_level_update');
        Schema::dropIfExists('log_bank_stat');
        Schema::dropIfExists('log_alipay_stat');
        Schema::dropIfExists('log_fraud_recharge');
        Schema::dropIfExists('log_prefix_version');
    }
}
