<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityTable extends Migration
{

    public function up()
    {

        // 活动类型表
        Schema::create('inf_carrier_activity', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment('运营商ID');
            $table->string('name',128)->default('')->comment('活动名称');
            $table->string('en_name',128)->default('')->comment('活动名称');
            $table->string('vi_name',128)->default('')->comment('活动名称');
            $table->string('id_name',128)->default('')->comment('活动名称');
            $table->string('hi_name',128)->default('')->comment('活动名称');
            $table->string('th_name',128)->default('')->comment('活动名称');
            $table->string('tl_name',128)->default('')->comment('活动名称');
            $table->integer('act_type_id')->comment('1=首充，2=充送，3=静态,4=闯关,5=累存,6=每日首存,7=夜间每日首存');
            $table->tinyInteger('game_category')->nullable()->comment("0=全部,1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼");
            $table->tinyInteger('bonuses_type')->nullable()->comment("1=百分比  2=固定金额 ,3=其它");
            $table->text('rebate_financial_bonuses_step_rate_json')->nullable()->comment('红利类型阶梯比例 josn');
            $table->tinyInteger('apply_way')->nullable()->comment('申请方式, 1=手动，2=自动 3=无需申请');
            $table->tinyInteger('apply_times')->nullable()->comment("0=不限,1=每日-次，2=每周一次，3=每月一次，4=永久一次");
            $table->tinyInteger('censor_way')->nullable()->comment("1=手动，2=自动 3=无需审核");
            $table->string('betflow_limit_main_game_plat_id',64)->default('')->comment("限制平台 多个用,号分开隔");
            $table->string('betflow_limit_category',64)->default('')->comment('流水限制分类,多个用,号分开隔');
            $table->integer('image_id')->nullable()->comment("汉语PC端活动图片");
            $table->integer('mobile_image_id')->nullable()->comment("汉语手机端活动图片");
            $table->integer('en_image_id')->nullable()->comment("英语PC端活动图片");
            $table->integer('en_mobile_image_id')->nullable()->comment("英文文手机端活动图片");
            $table->integer('vi_image_id')->nullable()->comment("越南语PC端活动图片");
            $table->integer('vi_mobile_image_id')->nullable()->comment("越南文文手机端活动图片");
            $table->integer('th_image_id')->nullable()->comment("泰语PC端活动图片");
            $table->integer('th_mobile_image_id')->nullable()->comment("泰语手机端活动图片");
            $table->integer('id_image_id')->nullable()->comment("印尼语PC端活动图片");
            $table->integer('id_mobile_image_id')->nullable()->comment("印尼语手机端活动图片");
            $table->integer('hi_image_id')->nullable()->comment("印地语PC端活动图片");
            $table->integer('hi_mobile_image_id')->nullable()->comment("印地语手机端活动图片");
            $table->integer('tl_image_id')->nullable()->comment("他加禄语PC端活动图片");
            $table->integer('tl_mobile_image_id')->nullable()->comment("他加禄语手机端活动图片");
            $table->text('apply_rule_string')->nullable()->comment("申请规则");
            $table->text('content')->nullable()->comment("中文活动内容");
            $table->text('en_content')->nullable()->comment("英文活动内容");
            $table->text('vi_content')->nullable()->comment("越南文活动内容");
            $table->text('hi_content')->nullable()->comment("印地文活动内容");
            $table->text('th_content')->nullable()->comment("泰文活动内容");
            $table->text('id_content')->nullable()->comment("印尼文活动内容");
            $table->text('tl_content')->nullable()->comment("他加禄语活动内容");
            $table->integer('startTime')->comment("开始时间");
            $table->integer('endTime')->comment("结束时间");
            $table->tinyInteger('status')->default(0)->comment("状态 1 启用  0=禁用");
            $table->integer('day')->default(1)->comment("赠送天数");
            $table->tinyInteger('gift_limit_method')->default(1)->comment("流水限制方式 1 本金加礼彩是  2=礼金");
            $table->integer('person_account')->default(0)->comment("申请人数");
            $table->integer('account')->default(0)->comment("申请次数");
            $table->bigInteger('gift_amount')->default(0)->comment("礼金总数");
            $table->integer('withdraw_account')->default(0)->comment("提现次数");
            $table->bigInteger('withdraw_amount')->default(0)->comment("提现总数");
            $table->integer('sort')->default(1)->comment("排序");
            $table->string('prefix',4)->default('A')->commit("站点前辍");
            $table->tinyInteger('is_agent_activity')->default(0)->commit("是否代理活动 1=是 0=否");
            $table->timestamps();

        });

        //活动审核列表
         Schema::create('inf_player_activity_audit', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment('运营商ID');
            $table->integer('act_id')->comment('活动ID');
            $table->integer('depositpay_id')->default(0)->comment('存款订单ID');
            $table->bigInteger('top_id')->comment('直属ID');           
            $table->bigInteger('parent_id')->comment('父级ID');                   
            $table->string('rid',255);
            $table->bigInteger('player_id')->comment('用户ID');
            $table->string('user_name',64)->comment('帐号');
            $table->tinyInteger('status')->default(0)->comment('0=待审核，1=通过，2=拒绝');
            $table->string('ip',15)->comment('IP');
            $table->bigInteger('deposit_amount')->default(0)->comment('存款金额');
            $table->bigInteger('gift_amount')->default(0)->comment('红利金额');
            $table->bigInteger('withdraw_flow_limit')->default(0)->comment('流水限制');
            $table->string('betflow_limit_category',64)->default('')->comment('流水限制分类,多个用,号分开隔');
            $table->string('betflow_limit_main_game_plat_id',64)->default('')->comment("限制平台 多个用,号分开隔");
            $table->integer('admin_id')->default(0)->comment("操作人员id");
            $table->timestamps();

            $table->index("player_id");
        });

        //幸运轮盘
        Schema::create('inf_carrier_activity_luck_draw', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment('运营商ID');
            $table->tinyInteger('game_category')->default(0)->comment("日流水分类 0=全部,1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼");
            $table->string('name',32)->comment('轮盘活动名称');
            $table->integer('startTime')->comment("活动开始时间");
            $table->integer('endTime')->comment("活动结束时间");
            $table->tinyInteger('signup_type')->comment("1=日充值金额抽奖，2=日流水金额抽奖");
            $table->text('content')->nullable()->comment("活动内容");
            $table->text('en_content')->nullable()->comment("活动内容");
            $table->text('vi_content')->nullable()->comment("活动内容");
            $table->text('th_content')->nullable()->comment("活动内容");
            $table->text('id_content')->nullable()->comment("活动内容");
            $table->text('hi_content')->nullable()->comment("活动内容");
            $table->text('tl_content')->nullable()->comment("活动内容");
            $table->integer('number')->comment("转盘面板方块 数字为6-10");
            $table->text('prize_json')->nullable()->comment("轮盘奖金JSON,分为金额对应中奖机率");
            $table->text('number_luck_draw_json')->nullable()->comment("抽奖次数JSON,分为金额对应抽奖次数");
            $table->integer('person_account')->default(0)->comment("参与人数");
            $table->bigInteger('payout')->default(0)->comment("派奖金额");
            $table->tinyInteger('status')->default(0)->comment("状态 1 启用  0=禁用");
            $table->timestamps();

        });

        //幸运轮盘抽奖记录
        Schema::create('inf_carrier_activity_player_luck_draw', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment('运营商ID');
            $table->bigInteger('player_id')->comment('用户');
            $table->string('user_name',64)->comment('帐号'); 
            $table->integer('luck_draw_id')->comment('幸运轮盘id');
            $table->bigInteger('money')->comment('中奖金额');
            $table->timestamps();

        });

        //体验券
        Schema::create('inf_carrier_activity_gift_code', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment('运营商ID');
            $table->string('name',32)->comment('体验券活动名称');
            $table->string('prefix',4)->comment("站点前辍");
            $table->integer('startTime')->comment("活动开始时间");
            $table->integer('endTime')->comment("活动结束时间");
            $table->string('gift_code',16)->comment("体验券");
            $table->integer('money')->comment("金额");
            $table->integer('betflowmultiple')->comment("流水倍数");
            $table->string('betflow_limit_main_game_plat_id',64)->default('')->comment("限制平台 多个用,号分开隔");
            $table->string('betflow_limit_category',64)->default('')->comment('流水限制分类,多个用,号分开隔');
            $table->tinyInteger('status')->default(0)->comment("状态 0=未使用 1=已使用 -1=已失效");
            $table->tinyInteger('distributestatus')->default(0)->comment("状态 0=未发放 1 已发放");
            $table->tinyInteger('type')->comment("状态 1=注册发放体验券 2=充值发放体验券");
            $table->bigInteger('player_id')->default(0)->comment("拥有者");

            $table->timestamps();

            $table->index("player_id");
        });

        //代理体验券
        Schema::create('inf_player_hold_gift_code', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id')->comment('运营商ID');
            $table->bigInteger('player_id')->comment("玩家ID");
            $table->string('gift_code',16)->comment("体验券");
            $table->integer('money')->comment("金额");
            $table->integer('betflowmultiple')->comment("流水倍数");
            $table->integer('endTime')->comment("活动结束时间");
            $table->tinyInteger('status')->default(0)->comment("状态 0=未使用 1 已使用");
            $table->string('prefix',4)->comment("站点前辍");
            $table->timestamps();

            $table->index("player_id");
        });
    }

    public function down()
    {
        Schema::dropIfExists('inf_carrier_activity');
        Schema::dropIfExists('inf_player_activity_audit');
        Schema::dropIfExists('inf_carrier_activity_luck_draw');
        Schema::dropIfExists('inf_carrier_activity_player_luck_draw');
        Schema::dropIfExists('inf_carrier_activity_gift_code');
        Schema::dropIfExists('inf_player_hold_gift_code');
    }
}
