<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\CarrierPreFixDomain;

class CarrierActivityGiftCode extends Model
{
   
    public $table = 'inf_carrier_activity_gift_code';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $fillable = [
    
    ];

    protected $casts = [
       
    ];

    public static $rules = [];

    static function giftCodeList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::where('carrier_id',$carrier->id)->orderBy('id','desc');

        if(isset($input['status']) && in_array($input['status'],[0,1,-1])) {
            $query->where('status',$input['status']);
        }

        if(isset($input['distributestatus']) && in_array($input['distributestatus'],[0,1])) {
            $query->where('distributestatus',$input['distributestatus']);
        }

        if(isset($input['money']) && is_numeric($input['money'])) {
            $query->where('money',$input['money']);
        }

        if(isset($input['type']) && in_array($input['type'],[1,2])) {
            $query->where('type',$input['type']);
        }

        if(isset($input['player_id']) && !empty($input['player_id'])) {
            $query->where('player_id',$input['player_id']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])) {
            $query->where('prefix',$input['prefix']);
        }

        if(isset($input['gift_code']) && !empty($input['gift_code'])) {
            $query->where('gift_code',$input['gift_code']);
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($data as $k => $v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
