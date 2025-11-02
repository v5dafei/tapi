<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLanguageTable extends Migration
{

    public function up()
    {
        // 创建语言名称
        Schema::create('def_language', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',32)->comment("语言代码");
            $table->string('zh_name',32)->comment("语言名称");
            $table->timestamps();
        });

        // 创建语言名称
        Schema::create('def_currency', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',32)->comment("币种代码");
            $table->string('zh_name',32)->comment("币种名称");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('def_language');
        Schema::dropIfExists('def_currency');
    }
}
