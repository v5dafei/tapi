<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Carrier\BaseController;
use App\Models\CarrierQuestions;
use App\Models\CarrierFeedback;

class ArticleController extends BaseController
{
    public function questionLists()
    {
        $data = CarrierQuestions::questionLists($this->carrier);
        if(is_array($data)) {
            return returnApiJson('操作成功', 1,$data);
        } else {
            return returnApiJson($data, 0);
        }
    }

    public function questionAdd($questionId=0)
    {
        if($questionId) {
            $carrierQuestions = CarrierQuestions::where('carrier_id',$this->carrier->id)->where('id',$questionId)->first();
            if(!$carrierQuestions) {
                return returnApiJson('对不起, 此问题不存在', 0);
            }
        } else {
            $carrierQuestions = new CarrierQuestions();
        }

        $res = $carrierQuestions->questionSave($this->carrierUser,$this->carrier);

        if($res===true) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function questionDelete($questionId)
    {
        $carrierQuestions = CarrierQuestions::where('id',$questionId)->where('carrier_id',$this->carrier->id)->first();
        if($carrierQuestions){
            $carrierQuestions->delete();
            return returnApiJson('操作成功', 1);
        } else{
            return returnApiJson('对不起，此条数据不存在或您没有权限', 1);
        }
    }

    public function questionTypeList()
    {
        $questiontype = config('main')['questiontype'];
        $data         = [];
        foreach ($questiontype as $key => $value) {
            $row          = [];
            $row['key']   = $key;
            $row['value'] = $value;
            $data[]       = $row;
        }

        return returnApiJson('操作成功', 1,$data);
    }

    public function feedbackList()
    {
        $data = CarrierFeedback::feedbackLists($this->carrier);
        if(is_array($data)) {
            return returnApiJson('操作成功', 1,$data);
        } else {
            return returnApiJson($data, 0);
        }
    }
}
