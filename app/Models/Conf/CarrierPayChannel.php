<?php

namespace App\Models\Conf;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;
use App\Models\Def\PayChannel;
use App\Models\Conf\CarrierThirdPartPay;
use App\Models\CarrierPreFixDomain;
use App\Models\Log\PlayerDepositPayLog;

class CarrierPayChannel extends Model
{
    
    protected $table = 'inf_carrier_pay_channel';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    ];

    protected $casts = [
    ];

    public  $rules = [
        'show_name'                  => 'required|string',
        'status'                     => 'required|in:0,1',
        'prefix'                     => 'required|string',
    ];

    public $messages = [
        'show_name.required'              => '显示明称必须填写',
        'status.required'                 => '状态必须填写',
        'status.in'                       => '状态取值不正确',
        'prefix.required'                 => '站点必须填写',
    ];

    static function paychannelList($carrier,$prefix='')
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['to_type']) && $input['to_type']==1){
            if(isset($input['factory_id']) && !empty($input['factory_id'])){
                $payChannelIds =  PayChannel::where('type',1)->where('factory_id',$input['factory_id'])->pluck('id')->toArray();
            } else{
                $payChannelIds =  PayChannel::where('type',1)->pluck('id')->toArray();
            }
            
        } elseif(isset($input['to_type']) && $input['to_type']==2){
            if(isset($input['factory_id']) && !empty($input['factory_id'])){
                $payChannelIds =  PayChannel::where('type',2)->where('factory_id',$input['factory_id'])->pluck('id')->toArray();
            } else{
                $payChannelIds =  PayChannel::where('type',2)->pluck('id')->toArray();
            }   
        }

        $carrierThirdPartPayIds = CarrierThirdPartPay::where('carrier_id',$carrier->id)->whereIn('def_pay_channel_id',$payChannelIds)->pluck('id')->toArray();

        if(empty($prefix)){
            $query = self::select('def_pay_factory_list.factory_name','def_pay_channel_list.type','def_pay_channel_list.id as channel_id','inf_carrier_pay_channel.*','def_pay_channel_list.name','def_pay_channel_list.trade_rate')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->where(function($query) use($carrier,$carrierThirdPartPayIds){
                $query->whereIn('inf_carrier_pay_channel.binded_third_part_pay_id',$carrierThirdPartPayIds)->where('inf_carrier_pay_channel.carrier_id',$carrier->id);
            })
            ->orWhere(function($query) use($carrier){
                $query->whereNull('inf_carrier_pay_channel.binded_third_part_pay_id')->where('inf_carrier_pay_channel.carrier_id',$carrier->id);
            })
            ->orderBy('inf_carrier_pay_channel.status','desc')
            ->orderBy('inf_carrier_pay_channel.sort','desc');
        } else{
            $query = self::select('def_pay_factory_list.factory_name','def_pay_channel_list.type','def_pay_channel_list.id as channel_id','inf_carrier_pay_channel.*','def_pay_channel_list.name','def_pay_channel_list.trade_rate')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->where(function($query) use($carrier,$prefix,$carrierThirdPartPayIds){
                $query->whereIn('inf_carrier_pay_channel.binded_third_part_pay_id',$carrierThirdPartPayIds)->where('inf_carrier_pay_channel.carrier_id',$carrier->id)->where('inf_carrier_pay_channel.prefix',$prefix);
            })
            ->orWhere(function($query) use($carrier,$prefix){
                $query->whereNull('inf_carrier_pay_channel.binded_third_part_pay_id')->where('inf_carrier_pay_channel.carrier_id',$carrier->id)->where('inf_carrier_pay_channel.prefix',$prefix);
            })
            ->orderBy('inf_carrier_pay_channel.status','desc')
            ->orderBy('inf_carrier_pay_channel.sort','desc');
        }

        $total           = $query->count();
        $data            = $query->skip($offset)->take($pageSize)->get();

        //添加当天的成功率及三方价位
        $keyvalue               = [];
        $currnetDepositPaylists = PlayerDepositPayLog::select('log_player_deposit_pay.*','def_pay_channel_list.id as channel_id')->leftJoin('inf_carrier_pay_channel','inf_carrier_pay_channel.id','=','log_player_deposit_pay.carrier_pay_channel')->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')->where('log_player_deposit_pay.created_at','>=',date('Y-m-d').' 00:00:00')->where('log_player_deposit_pay.is_hedging_account',0)->get();

        foreach ($currnetDepositPaylists as $k1 => $v1) {
            if(isset($keyvalue[$v1->channel_id])){
                if($v1->status==1){
                    $keyvalue[$v1->channel_id]['success'] = $keyvalue[$v1->channel_id]['success']+1;
                } else{
                    $keyvalue[$v1->channel_id]['fail']    = $keyvalue[$v1->channel_id]['fail']+1;
                }
            } else{
                if($v1->status==1){
                    $keyvalue[$v1->channel_id]['success']           = 1;
                    $keyvalue[$v1->channel_id]['playeridsuccess'][] = $v1->player_id;
                    $keyvalue[$v1->channel_id]['fail']              = 0;
                    $keyvalue[$v1->channel_id]['playeridfail']      = [];


                } else{
                    $keyvalue[$v1->channel_id]['fail']            = 1;
                    $keyvalue[$v1->channel_id]['playeridfail'][]  = $v1->player_id;
                    $keyvalue[$v1->channel_id]['success']         = 0;
                    $keyvalue[$v1->channel_id]['playeridsuccess'] = [];
                }
            }
        }

        //通道成功率
        foreach ($keyvalue as $k2 => $v2) {
            $totalpreson                    = count(array_unique(array_merge($v2['playeridsuccess'],$v2['playeridfail'])));
            $successpreson                  = count(array_unique($v2['playeridsuccess']));
            $keyvalue[$k2]['totalpreson']   = $totalpreson;
            $keyvalue[$k2]['successpreson'] = $successpreson;

            if($v2['success']>0){
                $t                     = intval($v2['success']+$v2['fail']);
                $keyvalue[$k2]['rate'] = bcdiv($v2['success'], $t*0.01,2);

                //充值成功人数百分比
                $keyvalue[$k2]['presonrate'] = bcdiv($successpreson*100, $totalpreson,2);
            } else{
                $keyvalue[$k2]['rate']       = 0;
                $keyvalue[$k2]['presonrate'] = 0;
            }
        }

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($data as $k => &$v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
            if(isset($keyvalue[$v->channel_id])){
                $v->success        = $keyvalue[$v->channel_id]['success'];
                $v->fail           = $keyvalue[$v->channel_id]['fail'];
                $v->rate           = $keyvalue[$v->channel_id]['rate'];
                $v->totalpreson    = $keyvalue[$v->channel_id]['totalpreson'];
                $v->successpreson  = $keyvalue[$v->channel_id]['successpreson'];
                $v->presonrate     = $keyvalue[$v->channel_id]['presonrate'];
            } else{
                $v->success = 0;
                $v->fail    = 0;
                $v->rate    = 0;
            }
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    static function paychannelListNopage($carrier)
    {
        $data = self::select('def_pay_factory_list.factory_name','def_pay_channel_list.type','def_pay_channel_list.id as channel_id','inf_carrier_pay_channel.*','def_pay_channel_list.name')
            ->where('inf_carrier_pay_channel.carrier_id',$carrier->id)
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->orderBy('inf_carrier_pay_channel.status','desc')
            ->orderBy('inf_carrier_pay_channel.sort','desc')
            ->get();

        return $data;
    }

    public function paychannelAdd($carrier)
    {
        $input     = request()->all();
        $validator = Validator::make($input, $this->rules, $this->messages);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        if(isset($input['show']) && in_array($input['show'], [1,2,3,4,5])){
            $this->show                     = $input['show'];
        }

        if(isset($input['is_recommend']) && in_array($input['is_recommend'], [0,1])){
            $this->is_recommend             = $input['is_recommend'];
        }

        if(isset($input['gift_ratio']) && (!is_numeric($input['gift_ratio']) || intval($input['gift_ratio']) != $input['gift_ratio'])){
            return '对不起，存送比例必须是整数';
        }

        if(isset($input['gift_ratio'])){
            $this->gift_ratio               = $input['gift_ratio'];
        }

        if(isset($input['img']) && !empty($input['img'])){
            $this->img                = $input['img'];
        } else{
            $this->img                = '';
        }
        
        if(isset($input['video_url']) && !empty($input['video_url'])){
            $this->video_url            = $input['video_url'];
        } else{
            $this->video_url            = '';
        }

        $this->carrier_id               = $carrier->id;
        $this->show_name                = $input['show_name'];
        $this->status                   = $input['status'];
        $this->prefix                   = $input['prefix'];
        $this->sort                     = isset($input['sort'])?$input['sort']:1;
        $this->save();

        return true;
    }
}
