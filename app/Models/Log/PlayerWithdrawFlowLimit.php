<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Player;
use App\Models\Def\MainGamePlat;

class PlayerWithdrawFlowLimit extends Model
{
   
    public $table = 'log_player_withdraw_flow_limit';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    
    ];

    protected $casts = [
       
    ];

    public static $rules = [];


    static function withdrawsLimitList($carrier)
    {
    	$input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::select('log_player_withdraw_flow_limit.*','inf_player.user_name')
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_withdraw_flow_limit.player_id')
            ->where('log_player_withdraw_flow_limit.carrier_id',$carrier->id)
            ->orderBy('log_player_withdraw_flow_limit.id','desc');

        if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
            $playerIds = Player::where('user_name','like','%'.$input['user_name'].'%')->where('carrier_id',$carrier->id)->pluck('player_id')->toArray();
            if(count($playerIds)){
                $query->whereIn('log_player_withdraw_flow_limit.player_id',$playerIds);
            } else {
                $query->where('log_player_withdraw_flow_limit.player_id',0);
            }
            
        }

        if(isset($input['player_id']) && !empty(trim($input['player_id']))) {
            $query->where('log_player_withdraw_flow_limit.player_id',$input['player_id']);
        }

        if(isset($input['extend_id']) && !empty($input['extend_id'])){
            $playerIds = Player::where('extend_id',$input['extend_id'])->where('carrier_id',$carrier->id)->pluck('player_id')->toArray();
            if(count($playerIds)){
                $query->whereIn('log_player_withdraw_flow_limit.player_id',$playerIds);
            } else {
                $query->where('log_player_withdraw_flow_limit.player_id',0);
            }
        }

        $total        = $query->count();
        $items        = $query->skip($offset)->take($pageSize)->get();
        $mainGamePlats= MainGamePlat::select('main_game_plat_id','short')->get();

        $mainGamePlatsArr = [];
        foreach ($mainGamePlats as $key => $value) {
            $mainGamePlatsArr[$value->main_game_plat_id]=$value->short;
        }


        foreach ($items as $key => &$value) {
            if(!empty($value->betflow_limit_category)){
                $str = '';
                $betflowLimitCategoryArr = explode(',',$value->betflow_limit_category);
                foreach ($betflowLimitCategoryArr as $k => $v) {
                    switch ($v) {
                        case 1:
                            $str.='视讯,';
                            break;
                         case 2:
                            $str.='电子,';
                            break;
                         case 3:
                            $str.='电竞,';
                            break;
                         case 4:
                            $str.='棋牌,';
                            break;
                         case 5:
                            $str.='体育,';
                            break;
                         case 6:
                            $str.='彩票,';
                            break;
                         case 7:
                            $str.='捕鱼,';
                            break;
                        default:
                            // code...
                            break;
                    }
                }
                $value->betflow_limit_category=rtrim($str,',');
            } else{
                $value->betflow_limit_category='不限';
            }

            if(!empty($value->betflow_limit_main_game_plat_id)){
                $betflowLimitMainGamePlatIds = explode(',',$value->betflow_limit_main_game_plat_id);
                $value->betflow_limit_main_game_plat =''; 
                foreach($betflowLimitMainGamePlatIds as $k => $v){
                    $value->betflow_limit_main_game_plat .= $mainGamePlatsArr[$v].',';
                }
                $value->betflow_limit_main_game_plat = rtrim($value->betflow_limit_main_game_plat,',');
            } else{
                $value->betflow_limit_main_game_plat = '不限';
            }
        }

        return ['item' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    static function withdrawsLimitComplete($carrierUser,$carrier)
    {
    	$input          = request()->all();

    	if(!isset($input['id']) || empty($input['id'])) {
    		return '对不起，流水限制ID不能为空';
    	}

    	$playerWithdrawLimit = self::where('carrier_id',$carrier->id)->where('id',$input['id'])->first();

    	if(!$playerWithdrawLimit) {
    		return '对不起，纪录不存在';
    	}

    	if($playerWithdrawLimit->is_finished==1) {
    		return '对不起，此流水已完成无需重复完成';
    	}
        
    	$playerWithdrawLimit->complete_limit_amount = $playerWithdrawLimit->limit_amount;
    	$playerWithdrawLimit->is_finished           = 1;
        $playerWithdrawLimit->operator_id           = $carrierUser->id;
    	$playerWithdrawLimit->save();

    	return true;
    }
}
