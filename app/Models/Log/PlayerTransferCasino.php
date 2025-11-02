<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Def\PayChannel;
use App\Models\PlayerBankCard;
use App\Models\CarrierUser;
use App\Models\Carrier;
use App\Models\Player;

class PlayerTransferCasino extends Model
{
    public $table = 'log_player_transfer_casino';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'player_id',
        'carrier_id',
        'user_name',
        'main_game_plat_id',
        'main_game_plat_code',
        'type',
        'status',
        'price',
        'transferid',
        'admin_id',
    ];

    protected $casts = [
        'player_id'           => 'integer',
        'carrier_id'           => 'integer',
        'user_name'           => 'string',
        'main_game_plat_id'   => 'integer',
        'main_game_plat_code' => 'string',
        'type'                => 'integer',
        'status'              => 'integer',
        'price'               => 'integer',
        'transferid'          => 'string',
        'admin_id'            => 'integer',
    ];

    public static $rules = [];

    public static function playerCasinoTransferList($carrier)
    {
        $input          = request()->all();

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query = self::select('log_player_transfer_casino.*','def_main_game_plats.alias')
            ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','log_player_transfer_casino.main_game_plat_id')
            ->where('log_player_transfer_casino.carrier_id',$carrier->id)
            ->where('log_player_transfer_casino.status',0)
            ->orderBy('log_player_transfer_casino.id','desc');

        if(isset($input['startTime']) && !empty(trim($input['startTime'])) && strtotime($input['startTime'])){
            $query->where('log_player_transfer_casino.created_at','>=',$input['startTime']);
        }

        if(isset($input['endTime']) && !empty(trim($input['endTime'])) && strtotime($input['endTime'])){
            $query->where('log_player_transfer_casino.created_at','<=',$input['endTime']);
        }

        if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
            $playerIds   = Player::where('user_name','like','%'.$input['user_name'].'%')->where('carrier_id',$carrier->id)->pluck('player_id')->toArray();
            $playerIds   = count($playerIds) ? $playerIds : [];
            $query->whereIn('log_player_transfer_casino.player_id',$playerIds);
        }

        if(isset($input['player_id']) && !empty(trim($input['player_id']))) {
             $query->where('log_player_transfer_casino.player_id',$input['player_id']);
        }

        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        $nocheck         = config('game')['pub']['nocheckout'];
        return ['nocheck'=>$nocheck,'item' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
