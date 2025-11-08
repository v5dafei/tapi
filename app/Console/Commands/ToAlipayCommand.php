<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Log\PlayerWithdraw;
use App\Models\Def\Alipay;

class ToAlipayCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'toalipay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'toalipay';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $playerWithdraws = PlayerWithdraw::where('created_at','>=',date('Y-m-d').' 00:00:00')->where('player_alipay_id','!=','')->where('status',1)->get();
        $allAlipay       = Alipay::all();
        $allAlipayArr    = [];

        foreach ($allAlipay as $key => $value) {
            $allAlipayArr[] = $value->real_name.$value->account;
        }

        foreach ($playerWithdraws as $key => $value) {
            $collectionArr = explode('|', $value->collection);
            $str           = $collectionArr[2].$collectionArr[1];
            if(!in_array($str,$allAlipayArr)){
                $existAlipay       = Alipay::where('account',$collectionArr[1])->first();
                if($existAlipay && !$existAlipay->verification){
                    $alipay            = new Alipay();
                    $alipay->real_name = $collectionArr[2];
                    $alipay->account   = $collectionArr[1];

                    if(is_numeric($alipay->account)){
                        $alipay->type   = 1;
                    } else{
                        $alipay->type   = 2;
                    }

                    $alipay->verification = 1;
                    $alipay->save();

                    $allAlipayArr[] = $str;
                }
            }
        }
    }
}