<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class CarrierCapitationFeeSetting extends Model
{
    public $table    = 'inf_carrier_capitation_fee_setting';

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

    public static function capitationFeeLevelsList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::where('carrier_id',$carrier->id)->orderBy('sort','asc');

        if(isset($input['prefix']) || !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        $total         = $query->count();
        $item          = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }

        foreach ($item as $key => &$value) {
            $value->multiple_name = $carrierPreFixDomainArr[$value->prefix];
        }

        return ['item' => $item, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ];
    }
}
