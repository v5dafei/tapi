<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Player;
use App\Models\PlayerInviteCode;
use App\Models\PlayerTransfer;
use App\Models\CarrierPreFixDomain;

class DeleteShortLinkCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deleteshortlink';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'deleteshortlink';

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
      $carriers = Carrier::all();
      foreach ($carriers as $key => $value) {
         $carrierPreFixDomains             = CarrierPreFixDomain::where('carrier_id',$value->id)->get();
         foreach ($carrierPreFixDomains as $k => $v) {

            $shortLinkNoRegister = CarrierCache::getCarrierMultipleConfigure($value->id,'short_link_no_register',$v->prefix);
            $noDeleteShortLink   = CarrierCache::getCarrierMultipleConfigure($value->id,'no_delete_short_link',$v->prefix);
            $date                = date('Y-m-d',strtotime('-'.$shortLinkNoRegister.' days')).' 00:00:00';
            $playerInviteCodes   = PlayerInviteCode::where('domain','!=','')->where('updated_at','<=',$date)->get();

            foreach ($playerInviteCodes as $k1 => $v1) {
               $existplayer = Player::where('day','>=',date('Ymd',strtotime('-'.$shortLinkNoRegister.' days')))->where('parent_id',$v1->player_id)->first();
               if(!$existplayer){
                  $existRecharge = PlayerTransfer::where('day','>=',date('Ymd',strtotime('-'.$shortLinkNoRegister.' days')))->where('parent_id',$v1->player_id)->where('type','recharge')->first();
                  if(!$existRecharge){
                     $rechargeAmount = PlayerTransfer::where('rid','like',$v1->rid.'|%')->where('type','recharge')->sum('amount');
                     if($rechargeAmount < $noDeleteShortLink*10000){
                        $v1->domain =  '';
                        $v1->save();
                     }
                  }
               }
            }
         }
      }
    }
}