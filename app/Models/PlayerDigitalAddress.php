<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Def\DigitalAddressLib;
use App\Lib\Cache\CarrierCache;
use App\Models\Def\ThirdWallet;

class PlayerDigitalAddress extends Model
{
    public $table    = 'inf_player_digital_address';

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

    public static function memberDigitalAddressList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::where('carrier_id',$carrier->id)->orderBy('id','desc');

        if(isset($input['player_id']) && !empty($input['player_id'])) {
            $query->where('player_id',$input['player_id']);
        }

        if(isset($input['address']) && !empty($input['address'])) {
            $query->where('address',$input['address']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])) {
            $query->where('prefix',$input['prefix']);
        }

        if(isset($input['type']) && !empty($input['address'])) {
            $query->where('type',$input['type']);
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        $thirdWallets    = ThirdWallet::all();
        $thirdWalletsArr = [];

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($thirdWallets as $key => $value) {
            $thirdWalletsArr[$value->id] = $value->name;
        }

        $addresses = [];
        foreach ($data as $k => &$v) {
            $v->type_name = $thirdWalletsArr[$v->type];
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
            $addresses[]      = $v->address;
        }

        $digitalAddressLibs    = DigitalAddressLib::whereIn('address',$addresses)->get();
        $digitalAddressKeyMaps = [];
        foreach ($digitalAddressLibs as $key => $value) {
            $digitalAddressKeyMaps[$value->address] = $value->name;
        }

        foreach ($data as $k => &$v) {
            if(isset($digitalAddressKeyMaps[$v->address])){
                $v->realName = $digitalAddressKeyMaps[$v->address];
            } else{
                $v->realName = '';
            }
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function digitalAdd($user,$carrier)
    {
        $input = request()->all();
        $str   = 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $language  = CarrierCache::getLanguageByPrefix($user->prefix);

        if(!isset($input['status']) || !in_array($input['status'], [0,1])){
            return config('language')[$language]['error181'];
        }

        if(!isset($input['is_default']) || !in_array($input['is_default'], [0,1])){
            return config('language')[$language]['error182'];
        }

        if(!isset($input['address']) || empty($input['address'])){
            return config('language')[$language]['error183'];
        }

        $addressArr = str_split($input['address']);
        $allStrArr  = str_split($str);
        foreach ($addressArr as $key => $value) {
            if(!in_array($value,$allStrArr)){
                return '对不起，地址填写不到确不正常';
            }
        }

        $thirdWalletIds    = CarrierCache::getCarrierMultipleConfigure($carrier->id,'third_wallet',$user->prefix);
        $thirdWalletIds    = json_decode($thirdWalletIds,true);
        
        if(!isset($input['type']) || !in_array($input['type'], $thirdWalletIds)){

            return config('language')[$language]['error199'];
        }

        if($input['is_default'] == 1){
            self::where('player_id',$user->player_id)->where('is_default',1)->update(['is_default'=>0]);
        }

        $existDigitalAddress = self::where('carrier_id',$carrier->id)->where('address',$input['address'])->where('type',$input['type'])->where('prefix',$input['prefix'])->first();
        if($this->id){
            if($existDigitalAddress && $existDigitalAddress->id != $this->id){
                return config('language')[$language]['error248'];
            }
        } else {
            if($existDigitalAddress){
                return config('language')[$language]['error248'];
            }
        }

        $this->carrier_id = $carrier->id;
        $this->player_id  = $user->player_id;
        $this->address    = $input['address'];
        $this->is_default = $input['is_default'];
        $this->status     = $input['status'];
        $this->sort       = isset($input['sort']) && is_numeric($input['sort']) ? $input['sort'] : 0;
        $this->type       = $input['type'];
        $this->prefix     = $input['prefix'];

        if(isset($input['real_name'])){
            if(empty($user->real_name)){
                $user->real_name = $input['real_name'];
                $user->save();
            } elseif($user->real_name != $input['real_name']){
                return '对不起，真实姓名取值不正常';
            }
        }
        $this->save();

        return true;
    }
}
