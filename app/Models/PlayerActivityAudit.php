<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\PlayerTransfer;
use App\Models\PlayerAccount;
use App\Models\Player;
use App\Lib\Clog;

class PlayerActivityAudit extends Model
{
    public $table = 'inf_player_activity_audit';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
       
    ];

    protected $casts = [
       
    ];

    public static $rules = [

    ];

    public function activitiesAuth($carrierUser,$carrier)
    {
        $input          = request()->all();
        $activityAudit  = self::where('id',$input['id'])->first();

        if($input['status'] ==1) {
            try {
                \DB::beginTransaction();

                $playerAccount  = PlayerAccount::where('player_id',$activityAudit->player_id)->where('carrier_id',$carrier->id)->lockForUpdate()->first();
                $player         = Player::where('player_id',$activityAudit->player_id)->where('carrier_id',$carrier->id)->first();

                $playerTransfer                                = new PlayerTransfer();
                $playerTransfer->prefix                        = $player->prefix;
                $playerTransfer->carrier_id                    = $carrier->id;
                $playerTransfer->rid                           = $activityAudit->rid;
                $playerTransfer->top_id                        = $activityAudit->top_id;
                $playerTransfer->parent_id                     = $activityAudit->parent_id;
                $playerTransfer->player_id                     = $activityAudit->player_id;
                $playerTransfer->is_tester                     = $player->is_tester;
                $playerTransfer->user_name                     = $player->user_name;
                $playerTransfer->level                         = $player->level;
                $playerTransfer->from_id                       = 0;
                $playerTransfer->to_id                         = 0;
                $playerTransfer->platform_id                   = 0;
                $playerTransfer->mode                          = 1;
                $playerTransfer->type                          = 'gift';
                $playerTransfer->type_name                     = '活动礼金';
                $playerTransfer->project_id                    = '';
                $playerTransfer->day_m                         = date('Ym');
                $playerTransfer->day                           = date('Ymd');
                $playerTransfer->amount                        = $input['gift_amount']*10000;
                $playerTransfer->before_balance                = $playerAccount->balance;
                $playerTransfer->balance                       = $playerAccount->balance + $input['gift_amount']*10000;
                $playerTransfer->before_frozen_balance         = $playerAccount->frozen;
                $playerTransfer->frozen_balance                = $playerAccount->frozen;
                $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;
                
                $playerTransfer->admin_id                      = $carrierUser->id;
                $playerTransfer->activity_id                   = $activityAudit->act_id;
                $playerTransfer->save();

                $playerWithdrawFlowLimit                          = new PlayerWithdrawFlowLimit();
                $playerWithdrawFlowLimit->carrier_id              = $carrier->id;
                $playerWithdrawFlowLimit->act_id                  = $activityAudit->act_id;
                $playerWithdrawFlowLimit->player_id               = $activityAudit->player_id;
                $playerWithdrawFlowLimit->user_name               = $activityAudit->user_name;
                $playerWithdrawFlowLimit->parent_id               = $activityAudit->parent_id;
                $playerWithdrawFlowLimit->top_id                  = $activityAudit->top_id;
                $playerWithdrawFlowLimit->rid                     = $activityAudit->rid;
                $playerWithdrawFlowLimit->betflow_limit_category           = $activityAudit->betflow_limit_category;
                $playerWithdrawFlowLimit->betflow_limit_main_game_plat_id  = $activityAudit->betflow_limit_main_game_plat_id;
                $playerWithdrawFlowLimit->limit_amount                     = $input['withdraw_flow_limit']*10000;
                $playerWithdrawFlowLimit->complete_limit_amount            = 0;
                $playerWithdrawFlowLimit->is_finished                      = 0;
                $playerWithdrawFlowLimit->limit_type                       = 2;
                $playerWithdrawFlowLimit->operator_id                      = $carrierUser->id;
                $playerWithdrawFlowLimit->save();

                $playerAccount->balance = $playerTransfer->balance;
                $playerAccount->save();

                $activityAudit->status                         = 1;
                $activityAudit->gift_amount                    = $input['gift_amount']*10000;
                $activityAudit->withdraw_flow_limit            = $input['withdraw_flow_limit']*10000;
                $activityAudit->admin_id                       = $carrierUser->id;
                $activityAudit->save();

                if($activityAudit->depositpay_id){
                    $playerDepositPayLog = PlayerDepositPayLog::where('id',$activityAudit->depositpay_id)->first();
                    if(empty($playerDepositPayLog->activityids)){
                        $playerDepositPayLog->activityids = $activityAudit->act_id;
                    } else{
                        $playerDepositPayLog->activityids = $playerDepositPayLog->activityids.','.$activityAudit->act_id;
                    }
                    $playerDepositPayLog->save();
                }

                \DB::commit();
                    
                return true;
            } catch (\Exception $e) {
                \DB::rollback();
                Clog::recordabnormal('玩家申请活动异常：'.$e->getMessage());   
                return '操作异常activitiesAuth：'.$e->getMessage();
            }
        } else {
            $activityAudit->status              = 2;
            $activityAudit->admin_id            = $carrierUser->id;
            $activityAudit->gift_amount         = 0;
            $activityAudit->withdraw_flow_limit = 0;
            $activityAudit->save();

            return true;
        }
    }
}
