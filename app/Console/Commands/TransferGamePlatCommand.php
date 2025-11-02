<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;
use App\Game\Game;
use App\Models\Log\PlayerBetFlow;
use App\Models\PlayerGameAccount;
use App\Models\PlayerTransfer;

class TransferGamePlatCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transfergameplat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'transfergameplat';

    public    $interval    = 1800;
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
        $activeGamePlatPlayerIds = cache()->get('activegameplatplayerIds',[]);
        $playerIds               = PlayerBetFlow::where('bet_time','>=',time()-$this->interval)->groupBy('player_id')->pluck('player_id')->toArray();
        $transferPlayerIds       = PlayerTransfer::whereIn('type',['casino_transfer_out','casino_transfer_in'])->where('created_at','>=',date('Y-m-d H:i:s',time()-$this->interval))->pluck('player_id')->toArray();
        $playerIds               = array_merge($playerIds,$transferPlayerIds);
        $playerIds               = array_unique($playerIds);
        $players                 = Player::whereIn('player_id',$activeGamePlatPlayerIds)->get();
        $carriers                = Carrier::all();
        $playerArrs              = [];
        $carriersArrs            = [];

        foreach ($carriers as $key => $value) {
            $carriersArrs[$value->id] = $value;
        }

        foreach ($players as $key => $value) {
            $playerArrs[$value->player_id] = $value;
        }

        if(count($activeGamePlatPlayerIds)){
            $transferOutPlayerIds = array_diff($activeGamePlatPlayerIds, $playerIds);
            foreach ($transferOutPlayerIds as $key => $value) {
                //转帐操作
                $playerGameAccounts  = PlayerGameAccount::where('player_id',$value)->where('exist_transfer',1)->get();
                foreach ($playerGameAccounts as $key1 => $value1) {
                    request()->offsetSet('accountUserName',$value1->account_user_name);
                    request()->offsetSet('password',$value1->password);
                    request()->offsetSet('mainGamePlatCode',$value1->main_game_plat_code);

                    $transferoutGame    = new Game($carriersArrs[$value1->carrier_id],$value1->main_game_plat_code);        
                    $transferoutBalance = $transferoutGame->getBalance();

                    if(is_array($transferoutBalance) && $transferoutBalance['success']){
                        if($transferoutBalance['data']['balance'] >= 1){
                            request()->offsetSet('price',intval($transferoutBalance['data']['balance']));
                            $transferoutGame->transferTo($playerArrs[$value]);
                        } 
                    }
                }
            }
        }
        $activeGamePlatPlayerIds = cache()->put('activegameplatplayerIds',$playerIds);
    }
}