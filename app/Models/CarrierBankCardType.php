<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Cache\CarrierCache;

class CarrierBankCardType extends Model
{
    public $table = 'inf_carrier_bank_type';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [

    ];

    protected $casts = [
       
    ];

    public  $rules = [
       
    ];

    public $messages = [
       
    ];

    static function getList($carrier)
    {
        $input          = request()->all();
        $query          = self::where('carrier_id',$carrier->id)->orderBy('id','asc');
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['currency']) && !empty($input['currency'])){
            $query->where('currency',$input['currency']);
        }
        
        $total          = $query->count();
        $item           = $query->skip($offset)->take($pageSize)->get();

        return ['item' => $item, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function bankAdd($carrier)
    {
        $input     = request()->all();
        $validator = Validator::make($input, $this->rules, $this->messages);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $exist = self::where('carrier_id',$carrier->id)->where('bank_code',$input['bank_code'])->first();

        if($this->id) {
            if($exist && $this->id != $exist->id) {
                return '对不起，此银行已存在';
            }
        } else {
            if($exist) {
                return '对不起，此银行已存在';
            }
        }

        if(!isset($input['bank_background_url']) || is_null($input['bank_background_url'])){
            return '对不起，银行图标不能为空';
        }

        if(!isset($input['currency']) || is_null($input['currency'])){
            return '对不起，币种不能为空';
        }

        $this->bank_background_url = $input['bank_background_url'];
        $this->bank_name           = $input['bank_name'];
        $this->bank_code           = $input['bank_code'];
        $this->carrier_id          = $carrier->id;
        $this->currency            = $input['currency'];
        
        $this->save();

        return true;
    }
}
