<?php

namespace App\Models\Conf;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;
use App\Models\Def\PayChannel;
use App\Models\Conf\CarrierPayChannel;

class CarrierThirdPartPay extends Model
{    
    protected $table = 'conf_carrier_third_part_pay';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    ];

    protected $casts = [
    ];

     public $rules = [
        'merchant_number'          => 'required|string',
        'merchant_bind_domain'     => 'required|string',
        'merchant_query_domain'    => 'required|string',
        'private_key'              => 'required|string',
        'startTime'                => 'required|string|min:8|max:8',
        'endTime'                  => 'required|string|min:8|max:8'
    ];

    public $messages = [
        'merchant_bind_domain.required'   => '支付域名必须填写',
        'merchant_query_domain.required'  => '查询域名必须填写',
        'merchant_number.required'        => '商户号必须填写',
        'private_key.required'            => '密钥必须填写',
        'startTime.required'              => '开始时间必须填写',
        'startTime.min'                   => '开始时间长度必须为8位',
        'startTime.max'                   => '开始时间长度必须为8位',
        'endTime.min'                     => '结束时间长度必须为8位',
        'endTime.max'                     => '结束时间长度必须为8位',
        'endTime.required'                => '结束时间必须填写',
    ];

    public $daifu = [
        'merchant_number'          => 'required|string',
        'merchant_bind_domain'     => 'required|string',
        'merchant_query_domain'    => 'required|string',
        'private_key'              => 'required|string',
    ];

    public $daifumessages = [
        'merchant_bind_domain.required'   => '支付域名必须填写',
        'merchant_query_domain.required'  => '查询域名必须填写',
        'merchant_number.required'        => '商户号必须填写',
        'private_key.required'            => '密钥必须填写',
    ];

    public function thirdPayAdd($carrier)
    {
        $input     = request()->all();

        if($input['type']==1){
            $validator = Validator::make($input, $this->rules, $this->messages);

            if ($validator->fails()) {
                return $validator->errors()->first();
            }
        } else{
            $validator = Validator::make($input, $this->daifu, $this->daifumessages);

            if ($validator->fails()) {
                return $validator->errors()->first();
            }

            $input['min']                 = 0;
            $input['max']                 = 0;
            $input['enum']                = '';
            $input['is_smallamountpay']   = 0;
            $input['startTime']           = '00:00:00';
            $input['endTime']             = '23:59:59';
            $input['channel_code']        = '';
        }

        if(!strtotime('2021-10-28 '.$input['startTime']) || !strtotime('2021-10-28 '.$input['startTime'])){
            return '对不起,开始时间或结束时间格式不正确';
        }

        if($this->id){

            $PayChannel                      =  PayChannel::where('id',$this->def_pay_channel_id)->first();
            $PayChannel->factory_id          =  $input['factory_id'];
            $PayChannel->type                =  $input['type'];
            $PayChannel->name                =  $input['name'];
            $PayChannel->channel_code        =  is_null($input['channel_code']) ? '' : $input['channel_code'];
            $PayChannel->min                 =  isset($input['min']) && !empty($input['min']) ? $input['min'] :0 ;
            $PayChannel->max                 =  isset($input['max']) && !empty($input['max']) ? $input['max'] :0 ;
            $PayChannel->enum                =  is_null($input['enum'])?'':$input['enum'];
            $PayChannel->is_smallamountpay   =  0;
            $PayChannel->trade_rate          =  isset($input['trade_rate'])?$input['trade_rate']:0;
            $PayChannel->single_fee          =  isset($input['single_fee'])?$input['single_fee']:0;

            if($PayChannel->min >0){
                $PayChannel->is_show_enter = 1;
            } else{
                $PayChannel->is_show_enter = 0;
            }

            if(isset($input['remark']) && !empty($input['remark'])){
                $this->remark         = $input['remark'] ;
            } else {
                $this->remark    = '' ;
            }

            if(isset($input['has_realname']) && $input['has_realname']){
                $PayChannel->has_realname    = 1 ;
            } else {
                $PayChannel->has_realname    = 0 ;
            }
            $this->startTime                 = $input['startTime'];
            $this->endTime                   = $input['endTime'];

            $PayChannel->save();

            if(isset($input['is_returnlink_hascode']) && in_array($input['is_returnlink_hascode'],[0,1])){
                $this->is_returnlink_hascode  = $input['is_returnlink_hascode'];
            }

            if(isset($input['auto_shutdown_number']) && is_numeric($input['auto_shutdown_number']) && intval($input['auto_shutdown_number']) == $input['auto_shutdown_number'] && $input['auto_shutdown_number'] >= 0){
                $this->auto_shutdown_number  = $input['auto_shutdown_number'];
            }

            if(isset($input['enabled_auto'])  && in_array($input['enabled_auto'],[0,1])){
                $this->enabled_auto  = $input['enabled_auto'];
            }

            $this->carrier_id            = $carrier->id;
            $this->def_pay_channel_id    = $input['def_pay_channel_id'];
            $this->merchant_number       = $input['merchant_number'];
            $this->merchant_bind_domain  = $input['merchant_bind_domain'];
            $this->merchant_query_domain = $input['merchant_query_domain'];
            $this->private_key           = $input['private_key'];
            $this->is_anti_complaint     = isset($input['is_anti_complaint'])?$input['is_anti_complaint']:1;

            $this->rsa_private_key       = isset($input['rsa_private_key']) && !empty($input['rsa_private_key'])?$input['rsa_private_key']:'';
            $this->rsa_public_key       = isset($input['rsa_public_key']) && !empty($input['rsa_public_key'])?$input['rsa_public_key']:'';
            $this->save();

        } else {
            $payChannel                      =  new PayChannel();
            $payChannel->factory_id          =  $input['factory_id'];
            $payChannel->type                =  $input['type'];
            $payChannel->name                =  $input['name'];
            $payChannel->channel_code        =  is_null($input['channel_code']) ? '' : $input['channel_code'];
            $payChannel->min                 =  isset($input['min']) && !empty($input['min']) ? $input['min'] :0 ;
            $payChannel->max                 =  isset($input['max']) && !empty($input['max']) ? $input['max'] :0 ;
            $payChannel->enum                =  is_null($input['enum'])?'':$input['enum'];
            $payChannel->is_smallamountpay   =  0;
            $payChannel->trade_rate          =  isset($input['trade_rate'])?$input['trade_rate']:0;
            $payChannel->single_fee          =  isset($input['single_fee'])?$input['single_fee']:0;

            if($payChannel->min>0){
                $payChannel->is_show_enter = 1;
            } else{
                $payChannel->is_show_enter = 0;
            }

            if(isset($input['remark']) && !empty($input['remark'])){
                $this->remark         = $input['remark'] ;
            } else {
                $this->remark    = '' ;
            }

            if(isset($input['has_realname']) && $input['has_realname']){
                $payChannel->has_realname    = 1;
            } else {
                $payChannel->has_realname    = 0;
            }

            $this->startTime                 = $input['startTime'];
            $this->endTime                  = $input['endTime'];

            $payChannel->save();

            if(isset($input['is_returnlink_hascode']) && in_array($input['is_returnlink_hascode'],[0,1])){
                $this->is_returnlink_hascode  = $input['is_returnlink_hascode'];
            }

            if(isset($input['auto_shutdown_number']) && is_numeric($input['auto_shutdown_number']) && intval($input['auto_shutdown_number']) == $input['auto_shutdown_number'] && $input['auto_shutdown_number'] >= 0){
                $this->auto_shutdown_number  = $input['auto_shutdown_number'];
            }

            if(isset($input['enabled_auto'])  && in_array($input['enabled_auto'],[0,1])){
                $this->enabled_auto  = $input['enabled_auto'];
            }

            $this->carrier_id            = $carrier->id;
            $this->def_pay_channel_id    = $payChannel->id;
            $this->merchant_number       = $input['merchant_number'];
            $this->merchant_bind_domain  = $input['merchant_bind_domain'];
            $this->merchant_query_domain = $input['merchant_query_domain'];
            $this->private_key           = $input['private_key'];
            $this->is_anti_complaint     = isset($input['is_anti_complaint'])?$input['is_anti_complaint']:1;

            $this->rsa_private_key       = isset($input['rsa_private_key']) && !empty($input['rsa_private_key'])?$input['rsa_private_key']:'';
            $this->rsa_public_key        = isset($input['rsa_public_key']) && !empty($input['rsa_public_key'])?$input['rsa_public_key']:'';
            $this->save();
        }

        return true;
    }

    static function thirdPayList($type,$carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::select('def_pay_channel_list.channel_code','def_pay_factory_list.factory_name','def_pay_channel_list.type','def_pay_channel_list.name','def_pay_channel_list.trade_rate','def_pay_channel_list.single_fee','conf_carrier_third_part_pay.remark','conf_carrier_third_part_pay.is_anti_complaint','conf_carrier_third_part_pay.total_order','conf_carrier_third_part_pay.success_order','def_pay_channel_list.has_realname','def_pay_channel_list.factory_id','def_pay_channel_list.min','def_pay_channel_list.max','def_pay_channel_list.enum','def_pay_channel_list.is_smallamountpay','def_pay_channel_list.channel_code','conf_carrier_third_part_pay.id','conf_carrier_third_part_pay.carrier_id','conf_carrier_third_part_pay.def_pay_channel_id','conf_carrier_third_part_pay.merchant_bind_domain','conf_carrier_third_part_pay.startTime','conf_carrier_third_part_pay.endTime','conf_carrier_third_part_pay.merchant_query_domain','conf_carrier_third_part_pay.merchant_number','conf_carrier_third_part_pay.private_key','conf_carrier_third_part_pay.rsa_private_key','conf_carrier_third_part_pay.rsa_public_key','conf_carrier_third_part_pay.is_returnlink_hascode','conf_carrier_third_part_pay.auto_shutdown_number','conf_carrier_third_part_pay.enabled_auto')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->where('conf_carrier_third_part_pay.carrier_id',$carrier->id);

        if($type==1){
            $query->where('def_pay_channel_list.type',1);
        } else {
            $query->where('def_pay_channel_list.type',2);
        }

        if(isset($input['factory_id']) && !empty($input['factory_id'])){
            $query->where('def_pay_factory_list.id',$input['factory_id']);
        }

        $total          = $query->count();
        $data           = $query->skip($offset)->take($pageSize)->get();

        return ['item' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
