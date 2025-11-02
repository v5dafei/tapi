<?php

namespace App\Http\Controllers\Carrier;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Carrier\BaseController;
use App\Models\Conf\CarrierThirdPartPay;
use App\Models\Conf\CarrierPayChannel;
use App\Models\Def\PayChannel;
use App\Models\CarrierBankCardType;
use App\Models\CarrierBankCard;
use App\Models\CarrierDigitalAddress;
use App\Models\Def\PayFactory;
use App\Models\CarrierPayFactory;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;
use App\Models\PayChannelGroup;
use App\Models\CarrierPreFixDomain;

class PayChannelController extends BaseController
{
    use Authenticatable;

    public function paychannelList()
    {
    	//返回所有三方支付
      $input = request()->all();
      if(isset($input['prefix']) && !empty($input['prefix'])){
        $res = CarrierPayChannel::paychannelList($this->carrier,$input['prefix']);
      } else{
        $res = CarrierPayChannel::paychannelList($this->carrier);
      }
    	
    	return returnApiJson('操作成功', 1, $res);
    }

    public function paychannelListNopage()
    {
       //返回所有三方支付
       $res = CarrierPayChannel::paychannelListNopage($this->carrier);
      
       return returnApiJson('操作成功', 1, $res);
    }

    public function banktypeList()
    {

      $input = request()->all();
      
      if(!isset($input['currency']) || empty($input['currency'])){
        return returnApiJson('对不起，币种取值不能为空', 0);
      }
      $banks = CarrierBankCardType::where('carrier_id',$this->carrier->id)->where('currency',$input['currency'])->get();

    return returnApiJson('操作成功', 1 ,$banks);
  }

   public function thirdPayList($type)
   {
      //type  1=代收  2=代付
      if(!in_array($type, [1,2])){
         return returnApiJson('对不起,类型取值不正确', 0);
      }
   	  $res = CarrierThirdPartPay::thirdPayList($type,$this->carrier);
      return returnApiJson('操作成功', 1, $res);
   }

   public function thirdPayAdd($thirdPayId = 0)
   {
      $input = request()->all();
   		if(isset($input['id']) && !empty(trim($input['id']))) {
   			$carrierThirdPartPay = CarrierThirdPartPay::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
   			if(!$carrierThirdPartPay) {
   				return returnApiJson("对不起,此支付接口不存在！", 1, $res);
   			}
   		} else {
   			$carrierThirdPartPay = new CarrierThirdPartPay();
   		}

   		$res = $carrierThirdPartPay->thirdPayAdd($this->carrier);
   		if($res === true) {
   			return returnApiJson('操作成功', 1);
   		} else {
   			return returnApiJson($res, 0);
   		}
   }

   public function digitaltype()
   {
      return returnApiJson('操作成功', 1, config('amin')['type']);
   }

   public function digitalChangeStatus($id)
   {
     $existCarrierDigitalAddress = CarrierDigitalAddress::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
     if(!$existCarrierDigitalAddress){
        return returnApiJson('对不起，此条数据不存在', 0);
     } else{
        $existCarrierDigitalAddress->status = $existCarrierDigitalAddress->status ? 0:1;
        $existCarrierDigitalAddress->save();
        return returnApiJson('操作成功', 1);
     }
   }

   public function payFactory($type)
   {
      if(!in_array($type, [1,2])){
         return returnApiJson('对不起,类型取值不正确', 0);
      }
      $payFactorys = CarrierPayFactory::select('def_pay_factory_list.*')->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','inf_carrier_pay_factory.factory_id')->where('def_pay_factory_list.type',$type)->where('inf_carrier_pay_factory.carrier_id',$this->carrier->id)->get();
      return returnApiJson('操作成功', 1,$payFactorys);
   }

   public function allPaychannel($type)
   {

      if(!in_array($type, [1,2])){
         return returnApiJson('对不起,类型取值不正确', 0);
      }

      $factoryIds  = CarrierPayFactory::where('carrier_id',$this->carrier->id)->pluck('factory_id')->where('type',$type)->toArray();

      $query       = PayChannel::select('def_pay_factory_list.factory_name','def_pay_channel_list.*')->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')->whereIn('def_pay_channel_list.factory_id',$factoryIds)->where('def_pay_factory_list.status',1);

      if($type==1){
          $payChannels = $query->where('def_pay_factory_list.type',1)->get();
      } else {
          $payChannels = $query->where('def_pay_factory_list.type',2)->get();
      }

      $data  = [];
      foreach ($payChannels as $key => $value) {
        $row          = [];
        if($value->type==1) {
              $row['value'] =$value->name;
        } else {
          $row['value'] = '代付-'.$value->factory_name;
        }
        $row['id']    = $value->id;
       
        $data[]       = $row;
      }

      return returnApiJson('操作成功', 1, $data);
   }

   public function paychannelAdd()
   {
      $input = request()->all();
      if(isset($input['id']) && !empty(trim($input['id']))) {
          $carrierPayChannel = CarrierPayChannel::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
          if(!$carrierPayChannel) {
            return returnApiJson('对不起,支付渠道不存在', 0);
          }
      } else {
         $carrierPayChannel = new CarrierPayChannel();
      }
      $res = $carrierPayChannel->paychannelAdd($this->carrier);
      if($res === true) {
         return returnApiJson('操作成功', 1);
      } else {
        return returnApiJson($res, 0);
      }
   }

  public function paychannelUnbind($carrierpaychannelId = 0)
  {
    $carrierPayChannel = CarrierPayChannel::where('carrier_id',$this->carrier->id)->where('id',$carrierpaychannelId)->first();
    if(!$carrierPayChannel) {
        return returnApiJson('对不起,支付渠道不存在', 0);
    }
    $carrierPayChannel->binded_third_part_pay_id = null;
    $carrierPayChannel->save();

    return returnApiJson('操作成功', 1);
  }

  public function paychanneChangeStatus($carrierpaychannelId = 0)
  {
      $input = request()->all();
      $carrierPayChannel = CarrierPayChannel::where('carrier_id',$this->carrier->id)->where('id',$carrierpaychannelId)->first();
      if(!$carrierPayChannel) {
         return returnApiJson('对不起,支付渠道不存在', 0);
      }

      $carrierPayChannel->status  = $carrierPayChannel->status == 1 ? 0: 1;
      $carrierPayChannel->save();

      return returnApiJson('操作成功', 1);
  }
  public function paychannelBind($carrierpaychannelId = 0)
  {
      $input = request()->all();
      $carrierPayChannel = CarrierPayChannel::where('carrier_id',$this->carrier->id)->where('id',$carrierpaychannelId)->first();
      if(!$carrierPayChannel) {
         return returnApiJson('对不起,支付渠道不存在', 0);
      }
      if(!isset($input['binded_third_part_pay_id']) && empty(trim($input['binded_third_part_pay_id']))) {
        return returnApiJson('对不起,支付接口不存在', 0);
      }
      $carrierPayChannel->binded_third_part_pay_id = $input['binded_third_part_pay_id'];
      $carrierPayChannel->save();

      return returnApiJson('操作成功', 1);
  }

  public function cashBanklist()
  {
      $res = CarrierBankCard::cashBanklist($this->carrier);

      return returnApiJson('操作成功', 1, $res);
  }

  public function cashbankAdd()
  {
     $input = request()->all();

     if(isset($input['id']) && !empty(trim($input['id']))) {

        $carrierBankCard = CarrierBankCard::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
        if(!$carrierBankCard) {
          return returnApiJson('对不起,此银行卡不存在', 0);
        }

     } else {
        $carrierBankCard = new CarrierBankCard();
     }

     $res = $carrierBankCard->cashbankAdd($this->carrier);

     if($res === true) {
        return returnApiJson('操作成功', 1);
     } else {
        return returnApiJson($res, 0);
     }
  }

  public function changeCashBankStatus($carrierBankcardId=0)
  {
      if($carrierBankcardId){
          $carrierBankCard = CarrierBankCard::where('carrier_id',$this->carrier->id)->where('id',$carrierBankcardId)->first();
          if(!$carrierBankCard){
            return returnApiJson('对不起，此银行卡不存在！', 0);
          }

          $carrierBankCard->status =  $carrierBankCard->status ? 0:1;
          $carrierBankCard->save();

          return returnApiJson('操作成功！', 1);
             
 
      } else {
          return returnApiJson('对不起，此银行卡不存在！', 0);
      }
  }

  public function allThirdpartpayList()
  {

      $query = CarrierThirdPartPay::select('def_pay_factory_list.factory_name','def_pay_channel_list.type','conf_carrier_third_part_pay.id','def_pay_channel_list.name')
        ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
        ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
        ->where('carrier_id',$this->carrier->id);

        $carrierThirdPays = $query->get();
        $data = [];
        foreach ($carrierThirdPays as $key => $value) {
           $row = [];
           $row['binded_third_part_pay_id'] = $value->id;
           $row['type']                    =  $value->type;
           $row['value']                    =  $value->name;
           $data[]                          =   $row;
        }
      return returnApiJson('操作成功', 1 ,$data);
  }

  public function digitalAdd($digitalAddressId=0)
  {
    if($digitalAddressId){
      $carrierDigitalAddress = CarrierDigitalAddress::where('carrier_id',$this->carrier->id)->where('id',$digitalAddressId)->first();
      if(!$carrierDigitalAddress){
          return returnApiJson('对不起，此数字币数据不存在！', 0);
      }
    } else {
      $carrierDigitalAddress = new CarrierDigitalAddress();
    }
     
    $res = $carrierDigitalAddress->digitalAdd($this->carrierUser,$this->carrier);

    if($res===true){
      return returnApiJson('操作成功', 1);
    } else {
      return returnApiJson($res, 0);
    }
  }

  public function digitalList()
  {
    $carrierDigitalAddresses = CarrierDigitalAddress::select('inf_carrier_digital_address.*','inf_carrier_user.username')->leftJoin('inf_carrier_user','inf_carrier_user.id','=','inf_carrier_digital_address.adminId')->where('inf_carrier_digital_address.carrier_id',$this->carrier->id)->orderBy('created_at','desc')->get();
    return returnApiJson('操作成功', 1,$carrierDigitalAddresses);
  }

  public function allCarrierPaychannel()
  {
    $input = request()->all();
    if(!isset($input['prefix']) || empty($input['prefix'])){
       return returnApiJson('对不起，站点不能为空', 0);
    }
    $carrierPayChannels = CarrierPayChannel::select('inf_carrier_pay_channel.id','inf_carrier_pay_channel.show_name')->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')->where('inf_carrier_pay_channel.carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('def_pay_channel_list.type',1)->get();
    return returnApiJson('操作成功', 1,$carrierPayChannels);
  }

  public function payChannelGroupList()
  {
    $input                  = request()->all();
    if(!isset($input['prefix']) || empty($input['prefix'])){
        return returnApiJson('对不起，参数错误', 0);
    }

    $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
    $carrierPreFixDomainArr = [];
    foreach ($carrierPreFixDomain as $k => $v) {
        $carrierPreFixDomainArr[$v->prefix] = $v->name;
    }

    $payChannelGroups  = PayChannelGroup::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->orderBy('sort','desc')->get();
    $carrierPayChannels = CarrierPayChannel::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->get();

    $data = [];

    foreach ($carrierPayChannels as $key => $value) {
      $data[$value->id] = $value->show_name;
    }

    foreach ($payChannelGroups as $key => &$value) {
      $value->multiple_name = $carrierPreFixDomainArr[$value->prefix];
      if(!empty($value->carrier_pay_channel_ids)){
        $ids = explode(',',$value->carrier_pay_channel_ids);
         foreach ($ids as $k => $v) {
           if(isset($data[$v])){
              if(isset($value->carrier_pay_channel)){
                $value->carrier_pay_channel = $value->carrier_pay_channel.','.$data[$v];
              } else {
                $value->carrier_pay_channel = $data[$v];
              }
           }
         }
      } else{
        $value->carrier_pay_channel = '';
      }
    }
    return returnApiJson('操作成功', 1,$payChannelGroups);
  }

  public function payChannelGroupAdd($id=0)
  {
    if($id){
      $payChannelGroup = PayChannelGroup::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
      if(!$payChannelGroup){
        return returnApiJson('对不起,此条数据不存在', 0);
      }
    } else{
      $payChannelGroup = new PayChannelGroup();
    }

    $res = $payChannelGroup->payChannelGroupAdd($this->carrier);
    if($res===true){
      return returnApiJson('操作成功', 1);
    } else {
      return returnApiJson($res, 0);
    }
  }

  public function payChannelGroupDel($id=0)
  {
    if(!$id){
      return returnApiJson('对不起,此条数据不存在', 0);
    } else {
      $payChannelGroup = PayChannelGroup::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
      if($payChannelGroup){
          $payChannelGroup->delete();
          return returnApiJson('操作成功', 1);
      } else{
        return returnApiJson('对不起,此条数据不存在', 0);
      }
    }
  }

  public function payChannelGroupChangeStatus($id=0)
  {
    if(!$id){
      return returnApiJson('对不起,此条数据不存在', 0);
    } else {
      $payChannelGroup = PayChannelGroup::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
      if($payChannelGroup){
        $payChannelGroup->status = $payChannelGroup->status==0 ? 1:0;
        $payChannelGroup->save();
        return returnApiJson('操作成功', 1);
      } else{
        return returnApiJson('对不起,此条数据不存在', 0);
      }
    }
  }
}
