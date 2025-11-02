<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CarrierPreFixDomain;

class PlayerGiftCode extends Model
{
    public $table = 'log_player_gift_code';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $fillable = [
    
    ];

    protected $casts = [
       
    ];

    public static $rules = [];

    public static function giftcodePersonPersonList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::select('log_player_gift_code.*','log_player_gift_code.user_name')->where('log_player_gift_code.carrier_id',$carrier->id)->leftJoin('inf_player','inf_player.player_id','=','log_player_gift_code.player_id')->orderBy('log_player_gift_code.id','desc');
        $query1          = self::select(\DB::raw('sum(log_player_gift_code.amount) as log_player_gift_code'))->where('log_player_gift_code.carrier_id',$carrier->id);

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('log_player_gift_code.player_id',$input['player_id']);
            $query1->where('log_player_gift_code.player_id',$input['player_id']);
        }

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $query->where('log_player_gift_code.user_name','like','%'.$input['user_name'].'%');
            $query1->where('log_player_gift_code.user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['status']) && in_array($input['status'], [0,1,-1])){
            $query->where('log_player_gift_code.status',$input['status']);
            $query1->where('log_player_gift_code.status',$input['status']);
        }

        if(isset($input['type']) && in_array($input['type'], [1,2])){
            $query->where('log_player_gift_code.type',$input['type']);
            $query1->where('log_player_gift_code.type',$input['type']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('log_player_gift_code.prefix',$input['prefix']);
            $query1->where('log_player_gift_code.prefix',$input['prefix']);
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('log_player_gift_code.day','>=',date('Ymd',strtotime($input['startDate'])));
            $query1->where('log_player_gift_code.day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('log_player_gift_code.day','<',date('Ymd',strtotime($input['endDate'])+86400));
            $query1->where('log_player_gift_code.day','<',date('Ymd',strtotime($input['endDate'])+86400));
        }

        $total      = $query->count();
        $items      = $query->skip($offset)->take($pageSize)->get();
        $stat       = $query1->first();
        $stat       = is_null($stat->log_player_gift_code) ? 0: $stat->log_player_gift_code;


        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($items as $k => $v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
        }

        return ['stat'=>$stat,'items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}