<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;
use App\Models\Player;
use App\Models\PlayerTransfer;


class TestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {
    }

    public function handle()
    {
        $this->test();
    }

    public function test()
    {

        \Log::info('进入测试队列3');

        
            throw new \App\Exceptions\ErrMsg('队列异常。。。。。！');
        
    }
}
