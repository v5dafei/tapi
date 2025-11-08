<?php
namespace App\Observers;

use App\Lib\Cache\SystemCache;
use App\Models\Def\Banks;

class BankObserver
{
    public function created(Banks $banks)
    {
        SystemCache::forgetBank($banks->id);
    }

    public function updated(Banks $banks)
    {
        SystemCache::forgetBank($banks->id);
    }

    public function deleted(Banks $banks)
    {
        SystemCache::forgetBank($banks->id);
    }
}

