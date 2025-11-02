<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class CarrierActivityLuckDraw extends Model
{
    public $table = 'inf_carrier_activity_luck_draw';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
       
    ];

    protected $casts = [
       
    ];

    public static $rules = [

    ];

    public function saveItem($carrierUser,$carrier) 
    {
        $input                        = request()->all();

        if(!isset($input['name']) || empty($input['name'])) {
            return '对不起,活动名称不能为空';
        }

        if(!isset($input['startTime'])  || !strtotime($input['startTime'].' 00:00:00') ) {
            return '对不起,活动开始时间无效';
        }

        if(!isset($input['endTime']) ||  !strtotime($input['endTime'].' 23:59:59')) {
            return '对不起,活动结束时间无效';
        }

        if(!isset($input['signup_type']) || !in_array($input['signup_type'], [1,2])) {
            return '对不起,参于类型不正确';
        }

        if($input['signup_type']==2){
            if(!isset($input['game_category']) || !in_array($input['game_category'], [0,1,2,3,4,5,6,7])){
                return '对不起,游戏分类取值不正确';
            }
        }

        if($this->id){
            if(!isset($input['status']) || !in_array($input['status'], [0,1])) {
                return '对不起,状态取值不正确';
            }
        }

        $currLuckDraw = self::where('carrier_id',$carrier->id)->where('status',1)->first();
        
        if($this->id){
            if($input['status']==1 && $currLuckDraw && $currLuckDraw->id != $this->id){
                return '对不起,仅能有开启一个幸运轮盘活动';
            }
        } else {
            if($input['status']==1 && $currLuckDraw){
                return '对不起,仅能有开启一个幸运轮盘活动';
            }
        }

        if(!isset($input['number']) || !is_numeric($input['number'])) {
            return '对不起,板块数取值不正确';
        } else {
            $intNumber = intval($input['number']);
            if($intNumber<6 || $intNumber>10){
                return '对不起,板块数只能在6到10之间';
            }
        }

        if(!isset($input['prize_json']) || !is_array($input['prize_json'])) {
            return '对不起,中奖机率规则错误';
        }

        $probability = 0;
        foreach ($input['prize_json'] as $key => $value) {
            if(!isset($value['bonus']) || !isset($value['probability']) || !is_numeric($value['bonus']) || !is_numeric($value['probability']) || $value['bonus']<0 || $value['probability'] < 0){
                return '对不起,中奖机率规则错误';
            }
            $probability += $value['probability'];
        }

        if($probability!=1000){
            return '对不起,中奖机率总合不等于1000';
        }


        if(!isset($input['number_luck_draw_json']) || !is_array($input['number_luck_draw_json'])) {
            return '对不起,抽奖规则错误';
        }

        foreach ($input['number_luck_draw_json'] as $key => $value) {
            if(!isset($value['amount']) || !isset($value['number']) || !is_numeric($value['amount']) || !is_numeric($value['number']) || $value['amount']<0 || $value['number']<0){
                return '对不起,中奖机率规则错误';
            }
        }

        if(isset($input['content'])){
            $this->content                = $input['content'];
        }

        if(isset($input['vi_content'])){
            $this->vi_content                = $input['vi_content'];
        }

        if(isset($input['th_content'])){
            $this->th_content                = $input['th_content'];
        }

        if(isset($input['id_content'])){
            $this->id_content                = $input['id_content'];
        }

        if(isset($input['hi_content'])){
            $this->hi_content                = $input['hi_content'];
        }

        if(isset($input['en_content'])){
            $this->en_content                = $input['en_content'];
        }

        $this->carrier_id             = $carrier->id;
        $this->game_category          = $input['signup_type']==1? 0 : $input['game_category'];
        $this->name                   = $input['name'];
        $this->startTime              = strtotime($input['startTime'].' 00:00:00');
        $this->endTime                = strtotime($input['endTime'].' 23:59:59');
        $this->signup_type            = $input['signup_type'];
        $this->number                 = $input['number'];
        $this->prize_json             = json_encode($input['prize_json']);
        $this->number_luck_draw_json  = json_encode($input['number_luck_draw_json']);
        $this->status                 = $input['status'];
        
        $this->save();

        return true;
    }
}
