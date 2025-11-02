<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Cache\CarrierCache;

class CarrierBankCard extends Model
{
    public $table = 'inf_carrier_bankcard';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
        'carrier_id',
        'bank_id',
        'bank_username',
        'bank_account',
        'status',
        'sort'
    ];

    protected $casts = [
        'carrier_id'        => 'integer',
        'bank_id'           => 'integer',
        'bank_username'     => 'string',
        'bank_account'      => 'string',
        'status'            => 'integer',
        'sort'              => 'integer',
    ];

    public  $rules = [
        'bank_id'            => 'required|exists:inf_carrier_bank_type,id',
        'bank_username'      => 'required|min:2|string',
        'bank_account'       => 'required|min:5|string',
    ];

    public $messages = [
        'bank_id.required'        => '所属银行必须填写',
        'bank_id.exists'          => '所属银行不存在',
        'bank_username.required'  => '持卡人必须填写',
        'bank_username.min'       => '持卡人必须大于1个字符',
        'bank_account.required'   => '卡号必须填写',
        'bank_account.min'        => '卡号必须大于4个字符',
    ];

    static function cashBanklist($carrier)
    {
        $input          = request()->all();
        $query          = self::select('inf_carrier_bankcard.*','inf_carrier_bank_type.bank_name','inf_carrier_bank_type.currency')->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_carrier_bankcard.bank_id')->where('inf_carrier_bankcard.carrier_id',$carrier->id)->orderBy('inf_carrier_bankcard.created_at','asc');
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['bank_username']) && trim($input['bank_username']) != '') {
            $query->where('inf_carrier_bankcard.bank_username','like','%'.$input['bank_username'].'%');
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();
        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function cashbankAdd($carrier)
    {
        $input     = request()->all();
        $validator = Validator::make($input, $this->rules, $this->messages);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $carrierBankCard = self::where('bank_account',$input['bank_account'])->where('carrier_id',$carrier->id)->first();

        if(!$this->id) {
            if($carrierBankCard) {
                return '对不起,此银行卡已存在！';
            }
            
            $this->carrier_id = $carrier->id;
        } else {
            if($carrierBankCard && $carrierBankCard->id != $this->id) {
                return '对不起,此银行卡已存在！';
            }
        }

        $this->bank_id       = $input['bank_id'];
        $this->bank_username = $input['bank_username'];
        $this->bank_account  = $input['bank_account'];
        $this->sort          = isset($input['sort']) && is_numeric($input['sort']) ? $input['sort'] : 0;
        $this->save();

        return true;
    }
}
