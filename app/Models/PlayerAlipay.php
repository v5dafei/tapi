<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\CarrierBankCardType;
use App\Models\CarrierPreFixDomain;
use App\Models\ArbitrageBank;
use App\Models\PlayerTransfer;
use App\Lib\Cache\CarrierCache;
use App\Models\Def\Alipay;

class PlayerAlipay extends BaseModel
{    
    public $table = 'inf_player_alipay';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public static $rules = [
    ];

    public $fillable = [
    
    ];

    protected $casts = [
    
    ];

    public static function memberAlipayList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::where('carrier_id',$carrier->id)->orderBy('id','desc');

        if(isset($input['user_name']) && !empty($input['user_name'])) {
            $query->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['player_id']) && !empty($input['player_id'])) {
            $query->where('player_id',$input['player_id']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])) {
            $query->where('prefix',$input['prefix']);
        }

        if(isset($input['account']) && !empty($input['account'])) {
            $query->where('account',$input['account']);
        }

        if(isset($input['real_name']) && !empty($input['real_name'])) {
            $query->where('real_name',$input['real_name']);
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($data as $k => &$v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function alipayAdd($user,$carrier,$prefix)
    {
        $input     = request()->all();
        $language  = CarrierCache::getLanguageByPrefix($user->prefix);
        $flag      = false;

        if(!isset($input['real_name'])) {
            return config('language')[$language]['error21'];
        }

        if(!isset($input['account']) || empty(trim($input['account']))) {
            return config('language')[$language]['error21'];
        }

        if(is_numeric($input['account'])){
            if(strlen($input['account'])!=11){
                return config('language')[$language]['error543'];
            }

            if(substr($input['account'], 0,1)!=1){
                return config('language')[$language]['error544'];
            }
            $flag      = true;
        }

        if(!$flag){
            if(strpos($input['account'],'@') ===false){
                return config('language')[$language]['error545'];
            }
        }

        if(!empty($user->real_name) && $user->real_name != $input['real_name']){
            return config('language')[$language]['error515'];
        }

        $playerAlipay = self::where('carrier_id',$carrier->id)->where('account',$input['account'])->where('prefix',$prefix)->first();

        if($playerAlipay && $playerAlipay->player_id != $user->player_id) {
            return config('language')[$language]['error514'];
        }

        $existPlayerTransfer = PlayerTransfer::whereIn('type',['register_gift','code_gift'])->first();
        if($existPlayerTransfer && $this->id){
            return config('language')[$language]['error546'];
        }

        if(empty($user->real_name)){
            $user->real_name = $input['real_name'];
            $user->save();
        }

        if(!$this->id) {
            $this->carrier_id        = $user->carrier_id;
            $this->player_id         = $user->player_id;
            $this->user_name         = $user->user_name;
        }

        $this->real_name         = $user->real_name;
        $this->account           = $input['account'];
        $this->status            = 1;
        $this->prefix            = $prefix;
        $this->save();

        //写入Alipay
        $existAlipay              = Alipay::where('account',$input['account'])->first();
        if(!$existAlipay){
            $alipay               = new Alipay();
            $alipay->real_name    = $user->real_name;
            $alipay->account      = $input['account'];
            $alipay->type         = is_numeric($input['account']) ? 1:2;
            $alipay->verification = 0;
            $alipay->save();
        }
            
        return true;
    }
}
