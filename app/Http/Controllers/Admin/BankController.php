<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Auth\Authenticatable;
use App\Http\Controllers\Admin\BaseController;
use App\Models\Def\Banks;

class BankController extends BaseController
{
    use Authenticatable;

    public function bankList() 
    {
        $data   = Banks::getList();

        return returnApiJson('操作成功', 1, $data);
    }

    public function bankAdd($bankId=0)
    {
    	if($bankId) {
    		$bank = Banks::where('id',$bankId)->first();
    		if(!$bank) {
    			return returnApiJson('对不起，此银行不存在', 0);
    		}
    	} else {
    		$bank = new Banks();
    	}
        
    	$res = $bank->bankAdd();
    	if($res===true){
    		return returnApiJson('操作成功', 1);
    	} else {
    		return returnApiJson($res, 0);
    	}
    }

    public function bankDel($bankId=0)
    {
    	$bank = Banks::where('id',$bankId)->first();
    	if(!$bank) {
    		return returnApiJson('对不起，此银行不存在', 0);
    	} else {
    		$bank->delete();
    	}
    	return returnApiJson('操作成功', 1);
    }
}
