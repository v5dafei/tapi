<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Authenticatable;
use App\Models\Conf\CarrierWebSite;
use App\Models\Language;
use App\Models\Currency;
use App\Lib\Cache\CarrierCache;
use App\Models\CarrierPreFixDomain;

use App\Models\Conf\CarrierPayChannel;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\Conf\CarrierThirdPartPay;
use App\Models\CarrierActivity;
use App\Models\CarrierCapitationFeeSetting;
use App\Models\CarrierGuaranteed;
use App\Models\CarrierHorizontalMenu;
use App\Models\CarrierImage;
use App\Models\CarrierPlayerGrade;

class SystemController extends BaseController
{
    use Authenticatable;

    public function parameEdit($id) 
    {
        $input          = request()->all();
        $carrierWebSite = CarrierWebSite::where('carrier_id',0)->where('id',$id)->first();
        if(!$carrierWebSite){
           return returnApiJson('对不起，这条数据不存在', 0);
        }

        if(!isset($input['value']) || empty($input['value'])){
          return returnApiJson('对不起，值不能为空', 0);
        }

        $carrierWebSite->value = $input['value'];
        $carrierWebSite->save();

        CarrierCache::flushCarrierConfigure(0);

        return returnApiJson("操作成功!", 1);
    }

    public function parameList()
    {
        $data  = CarrierWebSite::where('carrier_id',0)->orderBy('id','asc')->get();

        foreach ($data as $key => &$value) {
            if($value->sign=='yellowpassword'){
                $value->value = '';
            }
        }
 
        return returnApiJson('操作成功',1,$data);
    }

    public function allLanguages()
    {
        $languages = Language::select('name','zh_name')->orderBy('id','asc')->get();
        return returnApiJson('操作成功',1,$languages);
    }

    public function allCurrencys()
    {
        $currencys = Currency::select('name','zh_name')->orderBy('id','asc')->get();
        return returnApiJson('操作成功',1,$currencys);
    }

    public function prefixDomain($id=0)
    {
        if($id){
            $carrierPreFixDomain = CarrierPreFixDomain::where('id',$id)->first();
            if(!$carrierPreFixDomain){
                return returnApiJson('对不起，此条数据不存在',0);
            }
        } else{
            $carrierPreFixDomain = new CarrierPreFixDomain();
        }

        $res                 = $carrierPreFixDomain->prefixDomain();

        if($res===true){
            return returnApiJson('操作成功',1);
        } else{
            return returnApiJson($res,0);
        }
    }

    public function prefixDomainDel($id)
    {
        $carrierPreFixDomain = CarrierPreFixDomain::where('id',$id)->first();
        if(!$carrierPreFixDomain){
            return returnApiJson('对不起，此条数据不存在',0);
        }

        $carrierPreFixDomain->delete();

        CarrierCache::forgetPreFix();
        return returnApiJson('操作成功',1);
    }

    public function prefixDomainList($carrierId)
    {
        $res = CarrierPreFixDomain::where('carrier_id',$carrierId)->get();
        return returnApiJson('操作成功',1,$res);
    }
}