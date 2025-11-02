<?php

namespace App\Console\Commands\Record;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Game\Game;
use App\Lib\Cache\Lock;
use App\Lib\Clog;

class ApiRecordCommand extends Command {
  
    protected $signature          = 'apiRecordFetch';

    protected $description        = 'Api Record Fetch';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $cacheKey = "ApiRecord";
        $redisLock = Lock::addLock($cacheKey,120);

        if (!$redisLock) {
            \Log::info('三方抓单ApiRecord未处理完，不能重复加锁');
            return false;
        } else {
            try {
                $carrier = Carrier::first();
                if(!is_null($carrier) && !empty($carrier->apiusername)){
                    $game     = new Game($carrier,null);
                    $carriers = Carrier::pluck('apiUsername')->toArray();
                    $game->getBetRecord($carriers);
                }

                Lock::release($redisLock);
                return true;
            } catch (\Exception $e) {
                Lock::release($redisLock);
                Clog::recordabnormal('抓单数据异常:'.$e->getMessage());
                return false;
            }
        }
    }
}