<?php

namespace App\Models\Report;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Map\CarrierGame;
use App\Models\Def\Game;
use App\Models\Player;

class ReportPlayerEarnings extends Model
{
    public $table    = 'report_player_earnings';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
        
    ];

    protected $casts = [
    ];

    public function playerEarnings($user)
    {
        $params                    = request()->all();
        $currentPage               = isset($params['page_index']) ? intval($params['page_index']) : 1;
        $pageSize                  = isset($params['page_size']) ? intval($params['page_size']) : config('main')['page_size'];
        $offset                    = ($currentPage - 1) * $pageSize;

        $selfQuery = self::where('player_id',$user->player_id)->orderBy('id','desc')->where('status',1);
        $query     = self::where('parent_id',$user->player_id)->orderBy('id','desc');

        if(isset($params['date']) && !empty($params['date']) && strtotime($params['date'])) {

            $startTime = strtotime($params['date']);
            $endTime   = strtotime($params['date'].' 23:59:59');

            $selfQuery->where('init_time','>=',$startTime)->where('init_time','<=',$endTime);
            $query->where('init_time','>=',$startTime)->where('init_time','<=',$endTime);;
        }

        if(isset($params['user_name']) && !empty($params['user_name'])) {
            $query->where('user_name',$params['user_name']);
        }

        $selfItem       = $selfQuery->first();
        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();
        
        if(!$selfItem){
            return ['selfItem'=>[],'item' => [], 'total' => 0 ,'currentPage' => 1, 'totalPage' => 0];
        }

        $selfItem->total_bets                      = bcdiv($selfItem->total_bets, 10000,2);
        $selfItem->total_win_amount                = bcdiv($selfItem->total_win_amount, 10000,2);
        $selfItem->total_gift                      = bcdiv($selfItem->total_gift, 10000,2);
        $selfItem->profit                          = bcdiv($selfItem->profit, 10000,2);
        $selfItem->amount                          = bcdiv($selfItem->amount, 10000,2);
        $selfItem->real_amount                     = bcdiv($selfItem->real_amount, 10000,2);
        $selfItem->total_commission_from_child     = bcdiv($selfItem->total_commission_from_child, 10000,2);
        $selfItem->total_commission_from_bet       = bcdiv($selfItem->total_commission_from_bet, 10000,2);

        foreach ($items as $key => $value) {
            $value->total_bets                      = bcdiv($value->total_bets, 10000,2);
            $value->total_win_amount                = bcdiv($value->total_win_amount, 10000,2);
            $value->total_gift                      = bcdiv($value->total_gift, 10000,2);
            $value->profit                          = bcdiv($value->profit, 10000,2);
            $value->amount                          = bcdiv($value->amount, 10000,2);
            $value->real_amount                     = bcdiv($value->real_amount, 10000,2);
            $value->total_commission_from_child     = bcdiv($value->total_commission_from_child, 10000,2);
            $value->total_commission_from_bet       = bcdiv($value->total_commission_from_bet, 10000,2);
        }

        return ['selfItem'=>$selfItem,'item' => $items, 'total' => $total ,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
