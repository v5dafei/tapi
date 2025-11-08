<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
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
            $table->tinyInteger('status')->default(1)->comment("是否正常 1=是，0=否");
            $table->tinyInteger('is_super_admin')->default(0)->comment("是否超管 1=是，0=否");
            $table->string('google_img',255)->default('')->commit("");
            $table->tinyInteger('bind_google_status')->default(0)->comment("是否绑定google验证码");
            $table->rememberToken();
            $table->timestamp('login_at')->nullable()->comment("登录时间");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inf_carrier_service_team');
        Schema::dropIfExists('inf_carrier_user');
    }
};
