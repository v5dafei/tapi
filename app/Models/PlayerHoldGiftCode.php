<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Lib\Cache\CarrierCache;

class PlayerHoldGiftCode extends Model
{

    public $table = 'inf_player_hold_gift_code';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    public $fillable = [
       
    ];

    protected $casts = [
       
    ];

    public static $rules = [
        
    ];

    public static function voucherList($user)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $registerGiftCodeAmount   = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'register_gift_code_amount',$user->prefix);
        $query                    = self::select('gift_code')->where('player_id',$user->player_id)->orderBy('id','desc');

        if(isset($input['status']) && in_array($input['status'],[0,1,-1])){
            $query->where('status',$input['status']);
        }

        $total       = $query->count();
        $items       = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->endTime = date('Y-m-d H:i:s',$value->endTime);
        }

        $playerTransfers = PlayerTransfer::select(\DB::raw('sum(amount) as  amount'),'player_id')->where('parent_id',$user->player_id)->groupBy('player_id')->get();

        $validnumber     = 0;
        foreach ($playerTransfers as $key => $value) {
            if($value->amount >= $registerGiftCodeAmount*10000){
                $validnumber ++;
            }
        }

        return [ 'data' => $items,'validnumber'=>$validnumber, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ];
    }

    public static function agentVoucherList($user)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $registerGiftCodeAmount   = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'register_gift_code_amount',$user->prefix);
        $query                    = self::where('player_id',$user->player_id)->orderBy('id','desc');

        if(isset($input['status']) && in_array($input['status'],[0,1,-1])){
            $query->where('status',$input['status']);
        }

        $total       = $query->count();
        $items       = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->endTime = date('Y-m-d H:i:s',$value->endTime);
        }

        $playerTransfers = PlayerTransfer::select(\DB::raw('sum(amount) as  amount'),'player_id')->where('parent_id',$user->player_id)->groupBy('player_id')->get();

        $validnumber     = 0;
        foreach ($playerTransfers as $key => $value) {
            if($value->amount >= $registerGiftCodeAmount*10000){
                $validnumber ++;
            }
        }

        return [ 'data' => $items,'validnumber'=>$validnumber, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ];
    }
}