<?php
namespace App\Observers;

use App\Lib\Cache\SystemCache;
use App\Models\Def\PayChannel;

class PayChannelObserver
{
    public function created(PayChannel $payChannel)
    {
        SystemCache::forgetChannelMap();
    }

    public function updated(PayChannel $payChannel)
    {
        SystemCache::forgetChannelMap();
    }

    public function deleted(PayChannel $payChannel)
    {
        SystemCache::forgetChannelMap();
    }
}

