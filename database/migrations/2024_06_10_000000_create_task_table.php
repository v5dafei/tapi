<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskTable extends Migration
{

    public function up()
    {
        // 聊天管理行为记录表
        Schema::create('inf_task_setting', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrier_id');
            $table->integer('game_category')->default(0)->comment("类型 1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼");
            $table->integer('amount')->default(0)->comment('奖励金额');
            $table->integer('available_bet_amount')->comment('有效投注');
            $table->integer('giftmultiple')->comment('流水倍数');
            $table->tinyInteger('sort')->default(1)->comment("关卡");
            $table->tinyInteger('status')->default(0)->comment("1=开启,0=关闭");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inf_task_setting');
    }
}
