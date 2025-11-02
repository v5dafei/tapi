<?php

namespace App\Models;

use App\Utils\Arr\ArrHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Cache\CarrierCache;
use App\Models\Conf\CarrierPayChannel;
use App\Models\CarrierBankCard;
use App\Models\Def\MainGamePlat;

class PlayerReceiveGiftCenter extends Model
{
    public $table    = 'inf_player_receive_gift_center';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
       
    ];

    protected $casts = [
    ];

    public $rules = [
        
    ];

    public $messages = [
        
    ];

    public static function receivegiftList($user)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $maptyypename   = config('main')['maptyypename'];

        $query          = self::where('player_id',$user->player_id)->orderBy('id','desc');

        if(isset($input['status']) && in_array($input['status'],[0,1,2])) {
            $query->where('status',$input['status']);
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('created_at','>=',date('Y-m-d',strtotime($input['startDate'])).' 00:00:00');
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('created_at','<=',date('Y-m-d',strtotime($input['endDate'])).' 23:59:59');
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        foreach ($data as $key => &$value) {
            $value->typename = $maptyypename[$value->type];
            $value->amount  = bcdiv($value->amount, 10000,2);
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public static function receiveList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $query          = self::where('carrier_id',$carrier->id)->orderBy('id','desc');
        $maptyypename   = config('main')['maptyypename'];

        if(isset($input['user_name']) && !empty($input['user_name'])) {
            $query->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['player_id']) && !empty($input['player_id'])) {
            $query->where('player_id',$input['player_id']);
        }

        if(isset($input['status']) && in_array($input['status'],[0,1,2])) {
            $query->where('status',$input['status']);
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('created_at','>=',date('Y-m-d',strtotime($input['startDate'])).' 00:00:00');
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('created_at','<=',date('Y-m-d',strtotime($input['endDate'])).' 23:59:59');
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        foreach ($data as $key => &$value) {
            $value->typename = $maptyypename[$value->type];
            if($value->betflow_limit_category==0 || $value->betflow_limit_category==''){
                $value->betflow_limit_category = '';
            } else{
                $categoryArr     = explode(',', $value->betflow_limit_category);
                $categoryDataArr = [
                    '1' =>'视讯',
                    '2' => '电子',
                    '3' => '电竞',
                    '4' => '棋牌',
                    '5' => '体育',
                    '6' => '彩票',
                    '7' => '捕鱼'
                ];
                $categoryValue = '';
               foreach ($categoryArr as $k => $v) {
                   $categoryValue = $categoryValue.$categoryDataArr[$v].',';
               }
               $value->betflow_limit_category = rtrim($categoryValue,',');
            }

            if(!empty($value->betflow_limit_main_game_plat_id)){
                $mainGamePlats        = MainGamePlat::all();
                $mainGamePlatArrs     = [];
                $limitMainGamePlatStr = '';

                foreach ($mainGamePlats as $k => $v) {
                    $mainGamePlats[$v->main_game_plat_id] =$v->main_game_plat_code;
                }

                $limitMainGamePlatArr = explode(',', $value->betflow_limit_main_game_plat_id);
                foreach ($limitMainGamePlatArr as $k => $v) {
                    $limitMainGamePlatStr =  $limitMainGamePlatStr.$mainGamePlatArrs[$v].',';
                }
                $value->betflow_limit_main_game_plat_id = rtrim($limitMainGamePlatStr,',');
            } 

            if($value->invalidtime){
                $value->invalidtime = date('Y-m-d H:i:s',$value->invalidtime);
            } else{
                $value->invalidtime = '';
            }

            if($value->receivetime){
                $value->receivetime = date('Y-m-d H:i:s',$value->receivetime);
            } else{
                $value->receivetime = '';
            }
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
