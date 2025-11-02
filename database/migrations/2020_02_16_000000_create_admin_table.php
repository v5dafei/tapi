<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminTable extends Migration
{

    public function up()
    {
        // 总管理员表
        Schema::create('inf_admin_user', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 32)->comment("帐号");
            $table->string('password', 128)->comment("密码");

            $table->string('mobile',11)->nullable()->comment("手机");
            $table->string('email',64)->nullable()->comment("邮箱");
            $table->tinyInteger('status')->default(1)->comment("1 正常  0 关闭");

            $table->string('last_login_time', 64)->nullable()->comment("最后登录时间");
            $table->integer('login_ip')->nullable()->comment("登录IP");
            $table->integer('parent_id')->nullable();
            $table->rememberToken();

            $table->timestamps();

            // 索引
            $table->index("username");
        });
    }

    public function down()
    {
        Schema::dropIfExists('inf_admin_user');
    }
}
