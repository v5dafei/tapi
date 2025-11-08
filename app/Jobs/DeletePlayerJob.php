<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Lib\Cache\CarrierCache;
use App\Lib\Clog;

class DeletePlayerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $carrier = null;

    public function __construct($carrier) {
        $this->carrier = $carrier;
    }

    public function handle()
    {
        $this->deletePlayer();
    }

    public function deletePlayer()
    {
        $defaultUserName     = CarrierCache::getCarrierConfigure($this->carrier->id,'default_user_name');

        try {
            \DB::beginTransaction();
            PlayerSetting::where('carrier_id',$this->carrier->id)->where('user_name','<>',$defaultUserName)->delete();
            Player::where('carrier_id',$this->carrier->id)->where('user_name','<>',$defaultUserName)->delete();
            PlayerAccount::where('carrier_id',$this->carrier->id)->where('user_name','<>',$defaultUserName)->delete();
            PlayerInviteCode::where('carrier_id',$this->carrier->id)->where('user_name','<>',$defaultUserName)->delete();
            ReportPlayerStatDay::where('carrier_id',$this->carrier->id)->where('user_name','<>',$defaultUserName)->where('day','<>',date('Ymd'))->delete();                    
            PlayerActivityAudit::where('carrier_id',$this->carrier->id)->delete();
            PlayerBankCard::where('carrier_id',$this->carrier->id)->delete();
            PlayerDigitalAddress::where('carrier_id',$this->carrier->id)->delete();
            PlayerGameAccount::where('carrier_id',$this->carrier->id)->delete();                
            PlayerMessage::where('carrier_id',$this->carrier->id)->delete();
            PlayerRecent::where('carrier_id',$this->carrier->id)->delete();                
            PlayerTransfer::where('carrier_id',$this->carrier->id)->delete();
            PlayerBetFlow::where('carrier_id',$this->carrier->id)->delete();
            PlayerBetFlowMiddle::where('carrier_id',$this->carrier->id)->delete();
            PlayerDepositPayLog::where('carrier_id',$this->carrier->id)->delete();
            PlayerLogin::where('carrier_id',$this->carrier->id)->delete();
            PlayerOperate::where('carrier_id',$this->carrier->id)->delete();
            PlayerTransferCasino::where('carrier_id',$this->carrier->id)->delete();
            PlayerWithdraw::where('carrier_id',$this->carrier->id)->delete();
            PlayerWithdrawFlowLimit::where('carrier_id',$this->carrier->id)->delete();
            ReportPlayerEarnings::where('carrier_id',$this->carrier->id)->delete();     
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollback();

            Clog::recordabnormal('日删除用户数据操作异常：'.$e->getMessage());  
        }
    }
}
