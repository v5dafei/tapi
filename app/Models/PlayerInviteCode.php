<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Conf\PlayerSetting;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;

class PlayerInviteCode extends Model
{

    public $table = 'inf_player_invite_code';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    public $fillable = [
       
    ];

    protected $casts = [
       
    ];

    public static $rules = [
        
    ];

    static function createRegisterLink($carrier,$user)
    {
        $input                   = request()->all();
        $playerSetting           = PlayerCache::getPlayerSetting($user->player_id);
        $language                = CarrierCache::getLanguageByPrefix($user->prefix);

        if(!isset($input['type']) || $input['type'] !=2 ) {
            return config('language')[$language]['error105'];
        }

        if(isset($input['earnings']) && !is_numeric($input['earnings'])){
            return config('language')[$language]['error103'];
        }

        if(!isset($input['earnings'])) {
            $input['earnings'] = 0;
        }

        if($playerSetting->earnings < $input['earnings'] || $input['earnings'] < 0) {
            return config('language')[$language]['error104'];
        }

        //附加
        $playerInviteCode                              = new PlayerInviteCode();
        $playerInviteCode->player_id                   = $user->player_id;
        $playerInviteCode->carrier_id                  = $user->carrier_id;
        $playerInviteCode->rid                         = $user->rid;
        $playerInviteCode->username                    = $user->user_name;
        $playerInviteCode->is_tester                   = $user->is_tester;
        $playerInviteCode->lottoadds                   = CarrierCache::getCarrierConfigure($carrier->id,'default_lottery_odds');
        $playerInviteCode->type                        = 2;
        $playerInviteCode->earnings                    = $input['earnings'];
        $playerInviteCode->code                        = $user->extend_id;
        $playerInviteCode->expired_at                  = $input['expired_at']==0 ?0:strtotime($input['expired_at']);
        $playerInviteCode->save();

        return true;
    }
}