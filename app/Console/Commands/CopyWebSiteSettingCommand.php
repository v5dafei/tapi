<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlayerBetflowCalculate;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\CarrierCache;
use App\Models\Carrier;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\CarrierGuaranteed;
use App\Models\CarrierCapitationFeeSetting;
use App\Models\TaskSetting;
use App\Models\Conf\CarrierPayChannel;
use App\Models\PayChannelGroup;
use App\Models\Map\CarrierPlayerLevelBankCardMap;
use App\Models\PlayerLevel;

class CopyWebSiteSettingCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copywebsitesetting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'copywebsitesetting';

    //来源
    const COPYPREFIX                         = 'G';

    //目录
    const TOPREFIX                           = 'H';

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
        
        $data                       = [];

        $carrierPreFixDomains       = CarrierMultipleFront::where('prefix',self::COPYPREFIX)->get();

        foreach ($carrierPreFixDomains as $k => $v) {
            if(!in_array($v->sign,['one_and_one_recharge_amount','one_and_one_withdrawal_amount','register_code_recharge','fake_withdraw_player_ids','first_deposit_activity_plus','forcibly_joinfakegame_activityid','ip_blacklist','materialIds','no_fake_pg_playerids','capitation_first_deposit_calculate_activityid','pg_replace_curr_cw_rate','pg_replace_today_curr_cw_rate','recharge_withdraw_proportion','replace_curr_cw_rate','replace_today_curr_cw_rate','site_stock','register_receive_activityid'])){
                CarrierMultipleFront::where('prefix',self::TOPREFIX)->where('sign',$v->sign)->update(['value'=>$v->value]);
            }
        }

        $carrierGuaranteeds = CarrierGuaranteed::where('prefix',self::COPYPREFIX)->orderBy('sort','asc')->get();

        foreach ($carrierGuaranteeds as $key => $value) {
            $row                       = [];
            $row['carrier_id']         = $value->carrier_id;
            $row['level']              = $value->level;
            $row['performance']        = $value->performance;
            $row['quota']              = $value->quota;
            $row['sort']               = $value->sort;
            $row['prefix']             = self::TOPREFIX;
            $row['created_at']         = $value->created_at;
            $row['updated_at']         = $value->updated_at;
            $data[]                    = $row;
        }
        if(count($data)){
            \DB::table('inf_carrier_guaranteed')->insert($data);
        }

        $data         = [];
        $taskSettings =TaskSetting::where('prefix',self::COPYPREFIX)->orderBy('sort','asc')->get();
        foreach ($taskSettings as $key => $value) {
            $row                         = [];
            $row['carrier_id']           = $value->carrier_id;
            $row['prefix']               = self::TOPREFIX;
            $row['game_category']        = $value->game_category;
            $row['amount']               = $value->amount;
            $row['available_bet_amount'] = $value->available_bet_amount;
            $row['giftmultiple']         = $value->giftmultiple;
            $row['sort']                 = $value->sort;
            $row['status']               = $value->status;
            $row['created_at']           = $value->created_at;
            $row['updated_at']           = $value->updated_at;
            $data[]                      = $row;
        }

        if(count($data)){
            \DB::table('inf_task_setting')->insert($data);
        }

        $data                         = [];
        $carrierCapitationFeeSettings = CarrierCapitationFeeSetting::where('prefix',self::COPYPREFIX)->orderBy('sort','asc')->get();

        foreach ($carrierCapitationFeeSettings as $key => $value) {
            $row                         = [];
            $row['carrier_id']           = $value->carrier_id;
            $row['prefix']               = self::TOPREFIX;
            $row['amount']               = $value->amount;
            $row['sort']                 = $value->sort;
            $row['status']               = $value->status;
            $row['created_at']           = $value->created_at;
            $row['updated_at']           = $value->updated_at;
            $data[]                      = $row;
        }

        if(count($data)){
            \DB::table('inf_carrier_capitation_fee_setting')->insert($data);
        }

        //支付相关copy
        $mapPayChannelId = [];
        $carrierPayChannels = CarrierPayChannel::where('prefix',self::COPYPREFIX)->orderBy('id','asc')->get();
        foreach ($carrierPayChannels as $key => $value) {
            $carrierPayChannel                          =  new CarrierPayChannel();
            $carrierPayChannel->carrier_id              = $value->carrier_id;
            $carrierPayChannel->prefix                  = self::TOPREFIX;
            $carrierPayChannel->show_name               = $value->show_name;
            $carrierPayChannel->img                     = $value->img;
            $carrierPayChannel->video_url               = $value->video_url;
            $carrierPayChannel->binded_third_part_pay_id= $value->binded_third_part_pay_id;
            $carrierPayChannel->status                  = $value->status;
            $carrierPayChannel->show                    = $value->show;
            $carrierPayChannel->gift_ratio              = $value->gift_ratio;
            $carrierPayChannel->is_recommend            = $value->is_recommend;
            $carrierPayChannel->sort                    = $value->sort;
            $carrierPayChannel->save();

            $mapPayChannelId[$value->id]                = $carrierPayChannel->id;
        }

        $data             = [];
        $payChannelGroups = PayChannelGroup::where('prefix',self::COPYPREFIX)->orderBy('id','asc')->get();
        foreach ($payChannelGroups as $key => $value) {
            $str                         = '';
            $row                         = [];
            $row['carrier_id']           = $value->carrier_id;
            $row['prefix']               = self::TOPREFIX;
            $row['name']                 = $value->name;
            $row['img']                  = $value->img;
            $row['sort']                 = $value->sort;
            $row['status']               = $value->status;
            $row['currency']             = $value->currency;
            $row['created_at']           = $value->created_at;
            $row['updated_at']           = $value->updated_at;

            if(!empty($value->carrier_pay_channel_ids)){
                $carrierPayChannelIds = explode(',', $value->carrier_pay_channel_ids);
                foreach ($carrierPayChannelIds as $key1 => $value1) {
                    $str.=$mapPayChannelId[$value1].',';
                }

                $row['carrier_pay_channel_ids']     = rtrim($str,',');

            } else{
                $row['carrier_pay_channel_ids']     = '';
            }

            $data[]                      = $row;
        }

        if(count($data)){
            \DB::table('inf_pay_channel_group')->insert($data);
        }

        //会员等级配对
        $data                           = [];
        $carrierPayChannels             = CarrierPayChannel::where('prefix',self::TOPREFIX)->get();
        $playerLevels                   = PlayerLevel::where('prefix',self::TOPREFIX)->get();

        foreach ($playerLevels as $key => $value) {
            foreach ($carrierPayChannels as $key1 => $value1) {
                $row                         = [];
                $row['carrier_id']           = $value->carrier_id;
                $row['carrier_channle_id']   = $value1->id;
                $row['player_level_id']      = $value->id;
                $row['created_at']           = $value->created_at;
                $row['updated_at']           = $value->updated_at;
                $data[]                      = $row;
            }
        }

        if(count($data)){
            \DB::table('map_carrier_player_level_pay_channel')->insert($data);
        }
    }
}