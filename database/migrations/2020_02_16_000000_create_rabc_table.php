<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRabcTable extends Migration
{

    public function up()
    {
        // 管理组权限表
        Schema::create('permission_group', function (Blueprint $table) {
            $table->increments('id');
            $table->string('group_name',32)->comment("权限分组名称");
            $table->integer('sort')->nullable();
            $table->integer('parent_id')->nullable();
            $table->timestamps();
        });

        // 权限表
        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('group_id')->nullable()->comment("权限分组ID");
            $table->string('name',255)->comment("后端路由");
            $table->string('frontroute',255)->default('')->comment("前端路由");
            $table->string('description',255)->comment("权限分组名称");
            $table->timestamps();
        });

        // 角色权限表
        Schema::create('permission_service_team', function (Blueprint $table) {
            $table->integer('permission_id');
            $table->integer('service_team_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('permission_group');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('permission_service_team');
    }
}
