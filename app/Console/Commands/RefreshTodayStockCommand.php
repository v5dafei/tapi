<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Carrier;
use App\Models\CarrierPreFixDomain;
use App\Models\Player;
use App\Models\PlayerAccount;
use App\Models\PlayerGameAccount;

class RefreshTodayStockCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refreshtodaystock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Today Stock';


    //重置用户数据库
    public   $deleteall = false;
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
        $playerGameAccount    = PlayerGameAccount::select('player_id',\DB::raw('sum(balance) as balance'))->groupBy('player_id');
        $playerAccounts       = PlayerAccount::all();
        $playerGameAccountArr = [];
        $playerStocks         = [];

        foreach ($playerGameAccount as $key => $value) {
            $playerGameAccountArr[$value->player_id] = $value->balance;
        }

        foreach ($playerAccounts as $key => $value) {
            if(isset($playerGameAccountArr[$value->player_id])){
                $playerStocks[$value->player_id] = $value->balance + $value->frozen + $value->agentbalance + $value->agentfrozen + $playerGameAccountArr[$value->player_id]*10000;
            } else{
                $playerStocks[$value->player_id] = $value->balance + $value->frozen + $value->agentbalance + $value->agentfrozen;
            }

            $preReportPlayerStatDay = ReportPlayerStatDay::where('player_id',$value->player_id)->where('day',date('Ymd',strtotime('-1 day')))->first();
            if($preReportPlayerStatDay){
                $changeSelfStock = $playerStocks[$value->player_id] - $preReportPlayerStatDay->self_stock;
                ReportPlayerStatDay::where('player_id',$value->player_id)->where('day',date('Ymd'))->update(['self_stock'=>$playerStocks[$value->player_id],'change_self_stock'=>$changeSelfStock]);
            } else{
                ReportPlayerStatDay::where('player_id',$value->player_id)->where('day',date('Ymd'))->update(['self_stock'=>$playerStocks[$value->player_id],'change_self_stock'=>$playerStocks[$value->player_id]]);
            }
        }
    }
}