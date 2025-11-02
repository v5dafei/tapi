<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Carrier\BaseController;
use App\Models\Conf\ImageCategory;
use App\Models\CarrierImage;
use App\Lib\Cache\CarrierCache;
use App\Models\CarrierActivity;
use App\Lib\S3;

class ImgController extends BaseController
{
    public function fileUpload($directory) 
    {
        $input        = request()->all();
        $directoryArr = ['levelvip','img','lottery','bankicon','appupdate','contact'];

        if(!in_array($directory, $directoryArr)) {
            return returnApiJson('对不起, 目录不正确', 0);
        }

        $arr = [
            'carrier_id'=>$this->carrier->id,
            'directory'=>$directory
        ];

        if($directory=='appupdate'){
            $res = S3::uploadFile($input['file'], $arr);
        } else {
            $res = S3::uploadImage($input['file'], $arr);
        }
        
        if(is_array($res)) {
            return returnApiJson('操作成功', 1,$res);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function categoryList()
    {
        $data = ImageCategory::all();
        return returnApiJson('操作成功', 1, $data);
    }

    public function imgSave($carrierimgid=0)
    {
        $input        = request()->all();
        if(!isset($input['image_category_id']) || trim($input['image_category_id']) == '' ) {
            return returnApiJson('对不起，image_category_id不正确', 0);
        }

        $imageCategory = ImageCategory::where('id',$input['image_category_id'])->first();
        if(!$imageCategory) {
            return returnApiJson('对不起，没有此图片分类', 0);
        }

        if(!isset($input['image_path']) || trim($input['image_path']) == '' ) {
             return returnApiJson('对不起，image_path不正确', 0);
        }

        if(!isset($input['prefix']) || trim($input['prefix']) == '' ) {
             return returnApiJson('对不起，站点取值不正确', 0);
        }

        if(isset($input['sort']) && !is_numeric($input['sort']) ) {
             return returnApiJson('对不起，sort不正确', 0);
        }

        if(!isset($input['language']) || !in_array($input['language'],['en','zh-cn','vi','th','id','hi','tl'])) {
             return returnApiJson('对不起，语言取值不对正确', 0);
        }

        if(!$carrierimgid) {
            $carrierImage                     = new CarrierImage();
        } else {
            $carrierImage                     = CarrierImage::where('id',$carrierimgid)->where('carrier_id',$this->carrier->id)->first();
        }
        
        $carrierImage->image_category_id  = $input['image_category_id'];
        $carrierImage->prefix             = $input['prefix'];
        $carrierImage->carrier_id         = $this->carrier->id;
        $carrierImage->language           = $input['language'];
        $carrierImage->image_path         = $input['image_path'];
        $carrierImage->url                = isset($input['url']) ? $input['url'] : '';
        $carrierImage->sort               = isset($input['sort']) ? $input['sort'] : 1;
        $carrierImage->remark             = isset($input['remark']) ? $input['remark'] : '';
        $carrierImage->admin_id           = $this->carrierUser->id;
        $carrierImage->save();

        return returnApiJson('操作成功', 1);
    }

    public function imgList()
    {
       $carrierImage  = new CarrierImage();
       $data          = $carrierImage->imgList($this->carrier);

       return returnApiJson('操作成功', 1,$data);
    }

    public function imgDel($carrierimgid=0)
    {
        $carrierImage                     = CarrierImage::where('id',$carrierimgid)->where('carrier_id',$this->carrier->id)->first();
        if(!$carrierImage) {
            return returnApiJson('对不起，此图片不存在', 0);
        }

        $carrierImage->delete();

        //同步删除活动图片
        CarrierActivity::where('image_id',$carrierimgid)->update(['image_id'=>NULL]);
        CarrierActivity::where('mobile_image_id',$carrierimgid)->update(['mobile_image_id'=>NULL]);
        CarrierActivity::where('en_image_id',$carrierimgid)->update(['en_image_id'=>NULL]);
        CarrierActivity::where('en_mobile_image_id',$carrierimgid)->update(['en_mobile_image_id'=>NULL]);
        CarrierActivity::where('vi_image_id',$carrierimgid)->update(['vi_image_id'=>NULL]);
        CarrierActivity::where('vi_mobile_image_id',$carrierimgid)->update(['vi_mobile_image_id'=>NULL]);
        CarrierActivity::where('th_image_id',$carrierimgid)->update(['th_image_id'=>NULL]);
        CarrierActivity::where('th_mobile_image_id',$carrierimgid)->update(['th_mobile_image_id'=>NULL]);
        CarrierActivity::where('id_image_id',$carrierimgid)->update(['id_image_id'=>NULL]);
        CarrierActivity::where('id_mobile_image_id',$carrierimgid)->update(['id_mobile_image_id'=>NULL]);
        CarrierActivity::where('hi_image_id',$carrierimgid)->update(['hi_image_id'=>NULL]);
        CarrierActivity::where('hi_mobile_image_id',$carrierimgid)->update(['hi_mobile_image_id'=>NULL]);
        CarrierActivity::where('tl_image_id',$carrierimgid)->update(['tl_image_id'=>NULL]);
        CarrierActivity::where('tl_mobile_image_id',$carrierimgid)->update(['tl_mobile_image_id'=>NULL]);
        
        return returnApiJson('操作成功', 1);
    }

    public function activitiesImgList()
    {
        $input                = request()->all();
        $supportLanguages     = CarrierCache::getCarrierConfigure($this->carrier->id,'supportMemberLangMap');
        $supportLanguagesArrs = explode(',', $supportLanguages);
        $query                = CarrierImage::whereIn('image_category_id',[9,17])->where('carrier_id',$this->carrier->id);

        if(isset($input['language']) && in_array($input['language'],$supportLanguagesArrs)){
            $query->where('language',$input['language']);
        } else{
            return returnApiJson('对不起，语言选项不存在或为空', 0);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        $activitiesImgs = $query->get();

        return returnApiJson('获取成功', 1,$activitiesImgs);
    }

    public function activitiesPopImgList()
    {
        $input                = request()->all();
        
        if(isset($input['prefix']) && !empty($input['prefix'])){
            $activitiesImgs = CarrierImage::whereIn('image_category_id',[22,23])->where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->get();
        } else{
            $activitiesImgs = CarrierImage::whereIn('image_category_id',[22,23])->where('carrier_id',$this->carrier->id)->get();
        }
        
        return returnApiJson('获取成功', 1,$activitiesImgs);
        
    }
}
