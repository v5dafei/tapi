<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Auth\Authenticatable;
use App\Http\Controllers\Admin\BaseController;
use App\Models\Def\PayChannel;
use App\Models\Def\PayFactory;
use App\Models\Def\Banks;
use App\Models\Map\PayFactoryBankCode;

class PayChannelController extends BaseController
{
    use Authenticatable;

    public function payChannelList() 
    {
        $data   = PayChannel::getList();

        return returnApiJson('操作成功', 1, $data);
    }

    public function payChannelAdd($payChannelId = 0)
    {
        if($payChannelId) {
            $payChannel    = PayChannel::find($payChannelId);
            if(!$payChannel) {
                 return returnApiJson("对不起, 此游戏平台不存在!", 0);
            }
        } else {
            $payChannel    = new PayChannel();
        }

        $res = $payChannel->saveItem();
        if($res === true) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function payFactoryAdd($payFactoryId=0)
    {
        if($payFactoryId) {
            $payFactory    = PayFactory::find($payFactoryId);
            if(!$payFactory) {
                return returnApiJson("对不起, 此游戏平台不存在!", 0);
            }
        } else {
            $payFactory    = new PayFactory();
        }

        $res = $payFactory->saveItem();
        if($res === true) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function payFactorylist()
    {
        $data   = PayFactory::getList();

        return returnApiJson('操作成功', 1, $data);
    }

    public function payFactorylistChangeStatus($id)
    {
        $payFactory = PayFactory::where('id',$id)->first();
        if($payFactory){
            $payFactory->status = $payFactory->status ? 0:1;
            $payFactory->save();

            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson('对不起,该支付厂商不存在！', 0);
        }
    }

    public function payFactoryBankcode($id)
    {
       $payFactory = PayFactory::where('id',$id)->first();
       if(!$payFactory){
           return returnApiJson('对不起,该支付厂商不存在！', 0);
       }

       $banks               = Banks::where('currency',$payFactory->currency)->get();
       $payFactoryBankCodes = PayFactoryBankCode::where('pay_factory_id',$id)->where('currency',$payFactory->currency)->get();

       $payFactoryBankCodesArr = [];
       foreach ($payFactoryBankCodes as $key => $value) {
           $payFactoryBankCodesArr[$value->bank_code] =  $value->third_bank_code;
       }

       $data = [];
       foreach ($banks as $key => $value) {

           $row['bank_code']      = $value->bank_code;
           $row['pay_factory_id'] = $id;
           $row['currency']       = $payFactory->currency;
           $row['bank_name']      = $value->bank_name;
           if(isset($payFactoryBankCodesArr[$value->bank_code])){
                $row['third_bank_code'] = $payFactoryBankCodesArr[$value->bank_code];
           } else {
                $row['third_bank_code'] = '';
           }

           $data[] = $row;
       }

       return returnApiJson('操作成功', 1,$data);
    }

    public function payFactoryBankcodeSave($id)
    {
       $input      = request()->all();
       $payFactory = PayFactory::where('id',$id)->first();

       if(!$payFactory){
           return returnApiJson('对不起,该支付厂商不存在！', 0);
       }
       
       if(!isset($input['bank_code']) || !isset($input['currency']) || !isset($input['third_bank_code'])){
            return returnApiJson('对不起,参数不正确！', 0);
       }

       if(empty($input['third_bank_code'])){
            return returnApiJson('对不起,银行卡编码不能为空！', 0);
       }

       $payFactoryBankCode = PayFactoryBankCode::where('pay_factory_id',$id)->where('currency',$input['currency'])->where('bank_code',$input['bank_code'])->first();

       if($payFactoryBankCode){
            $payFactoryBankCode->third_bank_code = $input['third_bank_code'];
            $payFactoryBankCode->save();
       } else {
            $payFactoryBankCode                  = new PayFactoryBankCode();
            $payFactoryBankCode->third_bank_code = $input['third_bank_code'];
            $payFactoryBankCode->currency        = $input['currency'];
            $payFactoryBankCode->bank_code       = $input['bank_code'];
            $payFactoryBankCode->pay_factory_id  = $id;
            $payFactoryBankCode->save();
       }

       return returnApiJson('操作成功', 1);
    }
}
