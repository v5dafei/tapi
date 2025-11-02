<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\CarrierBankCardType;
use App\Models\CarrierPreFixDomain;
use App\Models\ArbitrageBank;
use App\Models\PlayerTransfer;
use App\Lib\Cache\CarrierCache;

class PlayerBankCard extends BaseModel
{    
    public $table = 'inf_player_bank_cards';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public static $rules = [
    ];

    public $fillable = [
    
    ];

    protected $casts = [
    
    ];

    public static function memberBankList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::select('inf_player_bank_cards.*','inf_carrier_bank_type.bank_name')->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_player_bank_cards.bank_Id')->where('inf_player_bank_cards.carrier_id',$carrier->id)->orderBy('inf_player_bank_cards.id','desc');

        if(isset($input['user_name']) && !empty($input['user_name'])) {
            $query->where('inf_player_bank_cards.user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['player_id']) && !empty($input['player_id'])) {
            $query->where('inf_player_bank_cards.player_id',$input['player_id']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])) {
            $query->where('inf_player_bank_cards.prefix',$input['prefix']);
        }

        if(isset($input['card_account']) && !empty($input['card_account'])) {
            $query->where('inf_player_bank_cards.card_account',$input['card_account']);
        }

        if(isset($input['card_owner_name']) && !empty($input['card_owner_name'])) {
            $query->where('inf_player_bank_cards.card_owner_name',$input['card_owner_name']);
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

    public function bankcardAdd($user,$carrier,$prefix)
    {
        $input     = request()->all();
        $language  = CarrierCache::getLanguageByPrefix($user->prefix);

        if(!isset($input['bank_Id']) || trim($input['bank_Id']) == '') {
            return config('language')[$language]['error21'];
        } 

        $bnks  = CarrierBankCardType::where('id',$input['bank_Id'])->where('carrier_id',$carrier->id)->first();

        if(!$bnks) {
            return config('language')[$language]['error21'];
        }

        if(!isset($input['card_owner_name'])) {
            return config('language')[$language]['error21'];
        }

        if(!isset($input['is_default']) || !in_array($input['is_default'], [0,1])) {
            return config('language')[$language]['error21'];
        }

        if(!isset($input['card_account']) || empty(trim($input['card_account'])) ||!is_numeric($input['card_account'])) {
            return config('language')[$language]['error21'];
        }

        if(!empty($user->real_name) && $user->real_name != $input['card_owner_name']){
            return config('language')[$language]['error143'];
        }

        $playerBankCard = self::where('carrier_id',$carrier->id)->where('card_account',$input['card_account'])->where('bank_Id',$input['bank_Id'])->where('prefix',$prefix)->first();

        if($playerBankCard && $playerBankCard->player_id != $user->player_id) {
            return config('language')[$language]['error71'];
        }

        //查询是不是在套利银行卡列表中
        $arbitrageBank = ArbitrageBank::where('card_account',$input['card_account'])->first();
        if($arbitrageBank){
            return '对不起，此卡大数据涉及套利请更换银行卡';
        }

        $existPlayerTransfer = PlayerTransfer::whereIn('type',['register_gift','code_gift'])->first();
        if($existPlayerTransfer && $this->id){
            return '对不起，参与了注册送或兑换码活动不能修改银行卡信息';
        }

        if(empty($user->real_name)){
            $user->real_name = $input['card_owner_name'];
            $user->save();
        }

        if(!$this->id) {
            $this->carrier_id        = $user->carrier_id;
            $this->player_id         = $user->player_id;
            $this->user_name         = $user->user_name;
        }

        if($input['is_default'] ==1) {
            self::where('carrier_id',$carrier->id)->where('player_id',$user->player_id)->update(['is_default'=>0]);
        }

        $this->card_owner_name   = $user->real_name;
        $this->bank_Id           = $input['bank_Id'];
        $this->card_account      = $input['card_account'];
        $this->is_default        = $input['is_default'];
        $this->status            = $input['status'];
        $this->prefix            = $input['prefix'];
        $this->save();
            
        return true;
    }
}
