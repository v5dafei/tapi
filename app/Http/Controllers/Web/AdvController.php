<?php

namespace App\Http\Controllers\Web;

use App\Models\CarrierImage;

class AdvController extends BaseController
{

    public function advList()
    {
        $input          = request()->all();
        $language       = request()->header('APP-Lang');
        if(!isset($input['image_category_id']) || empty(trim($input['image_category_id']))) {
            return $this->returnApiJson(config('language')[$prefixlanguage]['error21'], 0);
        }

        if($input['image_category_id'] == 11 || $input['image_category_id'] == 12){
            $language ='zh-cn';
        }
        
        $data = CarrierImage::where('image_category_id',$input['image_category_id'])->where('language',$language)->where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->orderBy('sort','desc')->get();

        if(!empty($data)) {
            $data = $data->toArray();
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    } 
}