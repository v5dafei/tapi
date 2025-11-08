<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Carrier\BaseController;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerMessage;
use App\Models\Player;

class MessageController extends BaseController
{
    public function messageSave() 
    {
        $res           = PlayerMessage::messageSave($this->carrierUser,$this->carrier);

        if($res === true) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function messageList() 
    {
        $res = PlayerMessage::messageList($this->carrierUser,$this->carrier->id);

        if(is_array($res)) {
             return returnApiJson('操作成功', 1,$res);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function memberList($playerid=0)
    {
        if($playerid) {
            $player = Player::where('player_id',$playerid)->where('carrier_id',$this->carrier->id)->first();
            if(!$player) {
                return returnApiJson('对不起， 至用户不存在', 0);
            } else {
                $data = Player::where('parent_id',$player->player_id)->where('carrier_id',$this->carrier->id)->where('is_tester',0)->get();
            }
        } else {
            $data = Player::where('type',1)->where('user_name','<>',CarrierCache::getCarrierConfigure($this->carrier->id,'default_user_name'))->where('carrier_id',$this->carrier->id)->where('is_tester',0)->get();
        }
        return returnApiJson('操作成功', 1,$data);
    }
}
