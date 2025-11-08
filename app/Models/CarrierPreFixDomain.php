<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Carrier;
use App\Models\Language;
use App\Lib\Cache\CarrierCache;
use App\Models\Currency;

class CarrierPreFixDomain extends Model
{
    public $table = 'inf_carrier_prefix_domain';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [];

    protected $casts = [];

    public static $rules = [];


    public function prefixDomain()
    {
        $input = request()->all();

        if(!isset($input['carrier_id']) || empty($input['carrier_id'])){
            return '对不起，商户ID不能为空';
        }

        $carrier = Carrier::where('id',$input['carrier_id'])->first();
        if(!$carrier){
            return '对不起，此商户不存在';
        }

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return '对不起，前辍不能为空';
        }

        $existPrefix  = self::where('prefix',$input['prefix'])->first();

        if(!isset($input['domain']) || empty($input['domain'])){
            return '对不起，域名不能为空';
        }

        $languagesArrs = Language::get()->pluck('name')->toArray();
        if(!isset($input['language']) || !in_array($input['language'], $languagesArrs)){
            return '对不起，此语言设置不正确';
        }

        $currencyArrs = Currency::get()->pluck('name')->toArray();
        if(!isset($input['currency']) || !in_array($input['currency'], $currencyArrs)){
            return '对不起，此币种取值不在确';
        }

        if(!isset($input['sms_passage_id'])){
            $input['sms_passage_id'] = 0;
        }

        $existDomain  = self::where('domain',$input['domain'])->first();

        if($this->id){
            if($existPrefix && $existPrefix->id !=$this->id){
                return '对不起，此前辍已存在';
            }

            if($existDomain && $existDomain->id !=$this->id){
                return '对不起，此域名已存在';
            }
        } else{
            if($existPrefix){
                return '对不起，此前辍已存在';
            }

            if($existDomain){
                return '对不起，此域名已存在';
            }
        }

        $this->sms_passage_id    = $input['sms_passage_id'];
        $this->language          = $input['language'];
        $this->currency          = $input['currency'];
        $this->prefix            = $input['prefix'];
        $this->carrier_id        = $input['carrier_id'];
        $this->domain            = $input['domain'];
        $this->name              = isset($input['name']) && !empty($input['name']) ? $input['name']:'';
        $this->save();

        CarrierCache::forgetPreFix();
        return true;
    }
}
