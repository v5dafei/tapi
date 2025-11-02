<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Auth\Authenticatable;
use App\Http\Controllers\Admin\BaseController;
use App\Models\Def\SmsPassage;
use App\Sms\Sms;

class SmsController extends BaseController
{
    use Authenticatable;

    public function smsPassageList() 
    {
        $data   = SmsPassage::all();

        return returnApiJson('操作成功', 1, $data);
    }

    public function smsPassageAdd($smsPassageId = 0)
    {
        if($smsPassageId) {
            $smsPassage    = SmsPassage::find($smsPassageId);
            if(!$smsPassage) {
                 return returnApiJson("对不起, 此短信通道不存在!", 0);
            }
        } else {
            $smsPassage    = new SmsPassage();
        }

        $res = $smsPassage->saveItem();
        if($res === true) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function smspassageCallback($type='')
    {
        $input = request()->all();
        if(!empty($type)){
            $smspassage = SmsPassage::where('filename',$type)->first();
            if($smspassage){
                $sms = new Sms($smspassage);
                $res = $sms->callback($input);

                return $res;
            }
        }
    }
}
