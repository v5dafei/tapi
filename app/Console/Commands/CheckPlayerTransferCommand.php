<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Player;
use App\Models\PlayerTransfer;
use App\Jobs\TelegramJob;

class CheckPlayerTransferCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkplayertransfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'checkplayertransfer';

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
        $time            = time()-600;
        $playerIds = PlayerTransfer::where('created_at','>=',date('Y-m-d H:i:s',$time))->pluck('player_id')->toArray();
        $playerIds = array_unique($playerIds);
        foreach ($playerIds as $key => $value) {
            $playerTransfers = PlayerTransfer::where('created_at','>=',date('Y-m-d H:i:s',$time))->where('player_id',$value)->orderBy('id','asc')->get();
            $flag            = false;
            $beforeAccount   = 0;
            foreach ($playerTransfers as $k => $v) {
                if($flag===false){
                    $beforeAccount = $v->balance;
                    $flag         = true;
                } else{
                    if($beforeAccount!= $v->before_balance){
                        //推送小飞机
                        $text  = '用户名 : '.$v->user_name. chr(10);
                        $text .= '用户ID : '.$v->player_id. chr(10);
                        $text .= 'TransferID : '.$v->id. chr(10);
                        $text .= '帐变类型 : '.$v->type. chr(10);
                        $text .= '上个数据 : '.$beforeAccount. chr(10);
                        $text .= '当前数据 : '.$v->before_balance;

                        //$data = ['text'=>$text,'carrier_id'=> 0];
                       
                        //TelegramJob::dispatch($data);
                        \App\Utils\File\Logger::write($text, 'transfer/abnormal', \App\Utils\File\Logger::LEVEL_ERR);

                    }
                    $beforeAccount = $v->balance;
                }
            }

        }
    }
}