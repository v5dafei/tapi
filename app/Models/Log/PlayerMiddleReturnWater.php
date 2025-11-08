<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerBankCard;
use App\Models\PlayerTransfer;
use App\Models\CarrierUser;
use App\Models\Carrier;
use App\Models\Player;

class PlayerMiddleReturnWater extends Model
{
    public $table = 'log_player_middle_returnwater';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    ];

    protected $casts = [
    ];

    public static $rules = [];

    public function selfReturnCommission($user)
    {
        $input = request()->all();

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::select('inf_player.user_name','log_player_middle_returnwater.amount','log_player_middle_returnwater.created_at')->where('log_player_middle_returnwater.player_id',$user->player_id)->where('log_player_middle_returnwater.status',1)->where('log_player_middle_returnwater.created_at','>=',date('Y-m-d').' 00:00:00')->where('log_player_middle_returnwater.created_at','<=',date('Y-m-d').' 23:59:59')->orderBy('id','desc');

        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->amount = bcdiv($value->amount,10000,2);
        }
        
        return ['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}