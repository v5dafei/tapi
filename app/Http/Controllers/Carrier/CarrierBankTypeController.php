<?php

namespace App\Http\Controllers\Carrier;

use Illuminate\Auth\Authenticatable;
use App\Http\Controllers\Carrier\BaseController;
use App\Models\CarrierBankCardType;
use App\Models\CarrierBankCard;

class CarrierBankTypeController extends BaseController
{
    use Authenticatable;

    public function bankList() 
    {
        $data   = CarrierBankCardType::getList($this->carrier);

        return returnApiJson('操作成功', 1, $data);
    }

    public function bankAdd($bankId=0)
    {
    	if($bankId) {
    		$bank = CarrierBankCardType::where('carrier_id',$this->carrier->id)->where('id',$bankId)->first();
    		if(!$bank) {
    			return returnApiJson('对不起，此银行不存在', 0);
    		}
    	} else {
    		$bank = new CarrierBankCardType();
    	}
        
    	$res = $bank->bankAdd($this->carrier);
    	if($res===true){
    		return returnApiJson('操作成功', 1);
    	} else {
    		return returnApiJson($res, 0);
    	}
    }

    public function bankDel($bankId=0)
    {
    	$bank = CarrierBankCardType::where('carrier_id',$this->carrier->id)->where('id',$bankId)->first();
    	if(!$bank) {
    		return returnApiJson('对不起，此银行不存在', 0);
    	} else {
            $carrierBankCard = CarrierBankCard::where('carrier_id',$this->carrier->id)->where('bank_id',$bankId)->first();
            if($carrierBankCard){
                return returnApiJson('对不起，请先解绑商户收款银行卡', 0);
            }

    		$bank->delete();
    	}
    	return returnApiJson('操作成功', 1);
    }
}
