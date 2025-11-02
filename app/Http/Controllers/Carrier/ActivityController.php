<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Carrier\BaseController;
use App\Models\PlayerActivityAudit;
use App\Models\CarrierActivity;
use App\Models\CarrierActivityPlayerLuckDraw;
use App\Models\CarrierActivityLuckDraw;
use App\Models\CarrierImage;
use App\Models\PlayerTransfer;
use App\Models\PlayerBreakThrough;
use App\Models\Player;
use App\Models\Log\PlayerSignIn;
use App\Models\CarrierActivityGiftCode;
use App\Models\Log\PlayerGiftCode;
use App\Models\Map\CarrierGamePlat;
use App\Models\Conf\CarrierPayChannel;
use App\Models\PlayerReceiveGiftCenter;
use App\Models\Log\RankingList;
use App\Models\PlayerCommission;
use App\Models\CarrierPreFixDomain;
use App\Models\TaskSetting;
use App\Models\Log\PlayerCapitationFee;
use App\Lib\Cache\Lock;
use App\Models\PlayerRealCommission;
use App\Models\Log\PlayerRealCommissionTongbao;

class ActivityController extends BaseController
{
    public function activityList() 
    {
        $input = request()->all();
        if(!isset($input['prefix']) || empty($input['prefix'])){
            return returnApiJson('对不起，站点信息不能为空', 0);
        }

        $carrierActivitys = CarrierActivity::select('id','name')->where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->get()->toArray();
        
        return returnApiJson('操作成功', 1,$carrierActivitys);
    }

    public function activitySaveOne()
    {
    	$input = request()->all();

    	if(!isset($input['act_type_id']) || !in_array($input['act_type_id'], [1,2,3,4,5,6,7])) {
    		return returnApiJson('对不起，活动类型不正确', 0);
    	}

        if(!isset($input['is_agent_activity']) || !in_array($input['is_agent_activity'], [1,0])) {
            return returnApiJson('对不起，请选择代理或会员活动类型', 0);
        }

    	if(!isset($input['sort']) || !is_numeric($input['sort'])) {
    		return returnApiJson('对不起，排序取值不正确', 0);
    	}

    	if(isset($input['id'])) {
    		$carrierActivity = CarrierActivity::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
            if(!$carrierActivity){
            	return returnApiJson('对不起，此活动不存在', 0);
            }

            if($input['act_type_id']==1 || $input['act_type_id']==2){
                $search = array('[','"',']');
                $apply_rule_string     = str_replace($search,'',$carrierActivity->apply_rule_string);
                $apply_rule_arr        = explode(',',$apply_rule_string);

                if(!in_array($apply_rule_arr[0], ['userfirstdepositamount','todayfirstdepositamount','singledepositamount'])){
                    return returnApiJson('对不起，首充与充送活动申请条件必须是首存与今日首存及单笔存款', 0);
                }
            }

            if($carrierActivity->censor_way ==2){
                if($input['act_type_id'] !=1 && $input['act_type_id'] != 2 && $input['act_type_id'] != 6 && $input['act_type_id'] != 7){
                    return returnApiJson('对不起，只有首充与每日首存及充送活动才能自动审核', 0);
                }
            }

            if(in_array($carrierActivity->apply_rule_string[0], ['userfirstdepositamount','todayfirstdepositamount','singledepositamount'])){
                if($input['act_type_id'] != 1 && $input['act_type_id'] != 2){
                    return returnApiJson('对不起，首存与今日首存及单笔存款条件仅能用于首存，充送活动', 0);
                }
            }
    	} else {
    		$carrierActivity = new CarrierActivity();
    	}

        $carrierActivity->carrier_id         = $this->carrier->id;
    	$carrierActivity->act_type_id        = $input['act_type_id'];
    	$carrierActivity->sort               = $input['sort'];
        $carrierActivity->name               = isset($input['name']) ? $input['name'] :'';
        $carrierActivity->prefix             = isset($input['prefix']) ? $input['prefix'] :'A';
        $carrierActivity->vi_name            = isset($input['vi_name']) ? $input['vi_name'] :'';
        $carrierActivity->hi_name            = isset($input['hi_name']) ? $input['hi_name'] :'';
        $carrierActivity->id_name            = isset($input['id_name']) ? $input['id_name'] :'';
        $carrierActivity->hi_name            = isset($input['hi_name']) ? $input['hi_name'] :'';
        $carrierActivity->en_name            = isset($input['en_name']) ? $input['en_name'] :'';
    	$carrierActivity->image_id           = isset($input['image_id']) ? $input['image_id'] :null;
        $carrierActivity->mobile_image_id    = isset($input['mobile_image_id']) ? $input['mobile_image_id'] :null;
    	$carrierActivity->content            = isset($input['content'])?$input['content']:null;
        $carrierActivity->en_image_id        = isset($input['en_image_id']) ? $input['en_image_id'] :null;
        $carrierActivity->en_mobile_image_id = isset($input['en_mobile_image_id']) ? $input['en_mobile_image_id'] :null;
        $carrierActivity->en_content         = isset($input['en_content'])?$input['en_content']:null;
        $carrierActivity->vi_image_id        = isset($input['vi_image_id']) ? $input['vi_image_id'] :null;
        $carrierActivity->vi_mobile_image_id = isset($input['vi_mobile_image_id']) ? $input['vi_mobile_image_id'] :null;
        $carrierActivity->vi_content         = isset($input['vi_content'])?$input['vi_content']:null;
        $carrierActivity->id_image_id        = isset($input['id_image_id']) ? $input['id_image_id'] :null;
        $carrierActivity->id_mobile_image_id = isset($input['id_mobile_image_id']) ? $input['id_mobile_image_id'] :null;
        $carrierActivity->id_content         = isset($input['id_content'])?$input['id_content']:null;
        $carrierActivity->th_image_id        = isset($input['th_image_id']) ? $input['th_image_id'] :null;
        $carrierActivity->th_mobile_image_id = isset($input['th_mobile_image_id']) ? $input['th_mobile_image_id'] :null;
        $carrierActivity->th_content         = isset($input['th_content'])?$input['th_content']:null;
        $carrierActivity->hi_image_id        = isset($input['hi_image_id']) ? $input['hi_image_id'] :null;
        $carrierActivity->hi_mobile_image_id = isset($input['hi_mobile_image_id']) ? $input['hi_mobile_image_id'] :null;
        $carrierActivity->hi_content         = isset($input['hi_content'])?$input['hi_content']:null;
        $carrierActivity->is_agent_activity  = isset($input['is_agent_activity'])?$input['is_agent_activity']:0;
        
    	$carrierActivity->save();

    	return returnApiJson('保存成功', 1,['id'=>$carrierActivity->id]);
    }

    public function activitySaveTwo()
    {
    	$input = request()->all();

        $gamePlatIds = CarrierGamePlat::where('carrier_id',$this->carrier->id)->pluck('game_plat_id')->toArray();
        array_push($gamePlatIds,0);

        if(!array_key_exists('betflow_limit_main_game_plat_id',$input)) {
            return returnApiJson('对不起，限制游戏平台取值不正确', 0);
        }

        if(isset($input['betflow_limit_category']) && !empty($input['betflow_limit_category'])) {
            $betflowLimitCategorys = explode(',',$input['betflow_limit_category']);
            foreach($betflowLimitCategorys as $key => $value){
                if(!in_array($value,[1,2,3,4,5,6,7])){
                    return returnApiJson('对不起，限制游戏分类取值不正确', 0);
                }
            }
        }    

    	if(!isset($input['id']) || empty(trim($input['id']))) {
    		return returnApiJson('对不起，此活动不存在', 0);
    	}

        $carrierActivity = CarrierActivity::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
        if(!$carrierActivity) {
            return returnApiJson('对不起，此活动不存在', 0);
        }

    	if(!isset($input['game_category']) || !in_array(intval($input['game_category']), [0,1,2,3,4,5,6,7])) {
    		return returnApiJson('对不起，限红类型不存在或取值不正确', 0);
    	}

    	if(!isset($input['bonuses_type']) || !in_array($input['bonuses_type'], [1,2,3])) {
    		return returnApiJson('对不起，红利类型不存在或取值不正确', 0);
    	}

        if(in_array($input['bonuses_type'], [1,2])){
            if(!isset($input['rebate_financial_bonuses_step_rate_json']) || empty(trim(json_encode($input['rebate_financial_bonuses_step_rate_json'])))) {
                return returnApiJson('对不起，红利阶梯不能为空', 0);
            }
        }

        if(!isset($input['startTime']) || empty($input['startTime']) || !strtotime($input['startTime'])) {
            return returnApiJson('对不起，活动开始时间取值不正确', 0);
        }

        if(!isset($input['endTime']) || empty($input['endTime']) || !strtotime($input['endTime'])) {
            return returnApiJson('对不起，活动结束时间取值不正确', 0);
        }

    	if(!isset($input['apply_times']) || !in_array($input['apply_times'], [0,1,2,3,4])) {
    		return returnApiJson('对不起，申请次数不存在或取值不正确', 0);
    	}

    	if(!isset($input['censor_way'])|| !in_array($input['censor_way'], [1,2,3])) {
    		return returnApiJson('对不起，处理方式不存在或取值不正确', 0);
    	}

    	if(!isset($input['apply_rule_string'])|| !in_array(count($input['apply_rule_string']),[3,7])) {
    		return returnApiJson('对不起，申请规则不存在或取值不正确', 0);
    	}

        if(!isset($input['apply_way'])|| !in_array($input['apply_way'], [1,2,3])) {
            return returnApiJson('对不起，申请不存在或取值不正确', 0);
        }

        if($carrierActivity->act_type_id==1 || $carrierActivity->act_type_id==2){
            if(!in_array($input['apply_rule_string'][0], ['userfirstdepositamount','todayfirstdepositamount','singledepositamount'])){
                return returnApiJson('对不起，首充与充送活动申请条件必须是首存与今日首存及单笔存款', 0);
            }
        }

        if($input['censor_way']==2){
            if($carrierActivity->act_type_id !=1 && $carrierActivity->act_type_id != 2 && $carrierActivity->act_type_id != 6 && $carrierActivity->act_type_id != 7){
                return returnApiJson('对不起，只有首充与每日首存及充送活动才能自动审核', 0);
            }
        }

        if(!isset($input['gift_limit_method'])|| !in_array($input['gift_limit_method'],[1,2])) {
            return returnApiJson('对不起，流水限制方式不正确', 0);
        }

        if($carrierActivity->act_type_id ==4){
            if($input['bonuses_type'] !=2){
                return returnApiJson('对不起，闯关类型活动红利类型必须为固定金额', 0);
            }

            if(count($input['apply_rule_string']) !=3 || !in_array($input['apply_rule_string'][0],['todaybetflow','todaylottbetflow','todaycasinobetflow','todayelectronicbetflow','todayesportbetflow','todayfishbetflow','todaycardbetflow','todaysportbetflow','weekbetflow','weeklottbetflow','weekcasinobetflow','weekelectronicbetflow','weekesportbetflow','weekfishbetflow','weekcardbetflow','weeksportbetflow']) || $input['apply_rule_string'][1] != '>='){
                return returnApiJson('对不起，闯关类型活动申请规则必须为有效投注额', 0);
            }
        }

        if(isset($input['rebate_financial_bonuses_step_rate_json']) && count($input['rebate_financial_bonuses_step_rate_json'])){
            $rebateFinancialBonusesStepRateArr =  $input['rebate_financial_bonuses_step_rate_json'];
            if(is_null($rebateFinancialBonusesStepRateArr[0]['money'])){
                $flag = [];
                foreach ($rebateFinancialBonusesStepRateArr as $key => $value) {
                   $flag[] = $value['todaybetflow']; 
                }
                array_multisort($flag, SORT_DESC, $rebateFinancialBonusesStepRateArr);
                $input['rebate_financial_bonuses_step_rate_json'] = $rebateFinancialBonusesStepRateArr;

            } else{
                $flag = [];
                foreach ($rebateFinancialBonusesStepRateArr as $key => $value) {
                   $flag[] = $value['money']; 
                }
                array_multisort($flag, SORT_DESC, $rebateFinancialBonusesStepRateArr);
                $input['rebate_financial_bonuses_step_rate_json'] = $rebateFinancialBonusesStepRateArr;
            }
        }
    	$carrierActivity->betflow_limit_main_game_plat_id                = is_null($input['betflow_limit_main_game_plat_id']) ? '' : $input['betflow_limit_main_game_plat_id'];
        $carrierActivity->betflow_limit_category                         = isset($input['betflow_limit_category']) ? $input['betflow_limit_category'] : '';
        $carrierActivity->gift_limit_method                              = $input['gift_limit_method'];
    	$carrierActivity->startTime                                      = strtotime($input['startTime']);
        $carrierActivity->endTime                                        = strtotime($input['endTime']);
        $carrierActivity->game_category                                  = $input['game_category'];
    	$carrierActivity->bonuses_type                                   = $input['bonuses_type'];
    	$carrierActivity->rebate_financial_bonuses_step_rate_json        = isset($input['rebate_financial_bonuses_step_rate_json'])? $input['rebate_financial_bonuses_step_rate_json']:'';
    	$carrierActivity->apply_times                                    = $input['apply_times'];
    	$carrierActivity->censor_way                                     = $input['censor_way'];
        $carrierActivity->apply_way                                      = $input['apply_way'];
    	$carrierActivity->apply_rule_string                              = $input['apply_rule_string'];
    	$carrierActivity->save();

    	return returnApiJson('保存成功', 1,['id'=>$carrierActivity->id]);
    }

    public function activitiesList()
    {
        $res = CarrierActivity::activitiesList($this->carrier);
        if(is_array($res)){
            return returnApiJson('获取成功', 1,$res);
        } else{
            return returnApiJson($res, 0);
        }
    }

    public function activityInfo($activity_Id)
    {
        $carrierActivities                                          = CarrierActivity::where('carrier_id',$this->carrier->id)->where('id',intval($activity_Id))->first();
        $carrierActivities->apply_rule_string                       = json_decode($carrierActivities->apply_rule_string,true);
        $carrierActivities->rebate_financial_bonuses_step_rate_json = json_decode($carrierActivities->rebate_financial_bonuses_step_rate_json,true);

        return returnApiJson('获取成功', 1,$carrierActivities);
    }

    public function activitiesReport()
    {
        $input             = request()->all();
        if(isset($input['prefix']) && !empty($input['prefix'])){
            $carrierActivities = CarrierActivity::select('id','name','person_account','account','gift_amount','status','created_at','prefix','withdraw_amount','withdraw_account')->where('prefix',$input['prefix'])->where('carrier_id',$this->carrier->id)->get();
        } else{
            $carrierActivities = CarrierActivity::select('id','name','person_account','account','gift_amount','status','created_at','prefix','withdraw_amount','withdraw_account')->where('carrier_id',$this->carrier->id)->get();
        }

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($carrierActivities as $k => &$v) {
            $v->multiple_name   = $carrierPreFixDomainArr[$v->prefix];
            $v->gift_amount     = bcdiv($v->gift_amount,10000,0);
            $v->withdraw_amount = bcdiv($v->withdraw_amount,10000,0);
        }

    	return returnApiJson('获取成功', 1,$carrierActivities);
    }

    public function changeActivityStatus()
    {
    	$input = request()->all();

    	if(!isset($input['id']) || empty(trim($input['id']))) {
    		return returnApiJson('对不起，此活动不存在', 0);
    	}

    	$carrierActivity = CarrierActivity::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();

       if(!$carrierActivity) {
            return returnApiJson('对不起，此活动不存在', 0);
        }

       $carrierActivity->status =  $carrierActivity->status ? 0:1;
       $carrierActivity->save();

       return returnApiJson('操作成功', 1);
    }

    public function activitiesAuthList()
    {
    	$input                = request()->all();
    	$playerActivityAudits = PlayerActivityAudit::select('inf_carrier_activity.name','inf_player_activity_audit.*')
    	    ->leftJoin('inf_carrier_activity','inf_carrier_activity.id','=','inf_player_activity_audit.act_id')
    		->where('inf_player_activity_audit.carrier_id',$this->carrier->id)
            ->where('inf_player_activity_audit.status',0)
            ->get();

    	return returnApiJson('操作成功', 1,$playerActivityAudits);
    }

    public function activitiesAuthHistory()
    {
    	$input          = request()->all();
    	$currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

    	$query = PlayerActivityAudit::select('inf_carrier_activity.name','inf_player_activity_audit.*')
    	    ->leftJoin('inf_carrier_activity','inf_carrier_activity.id','=','inf_player_activity_audit.act_id')
    		->where('inf_player_activity_audit.carrier_id',$this->carrier->id)
            ->whereIn('inf_player_activity_audit.status',[1,2]);

    	if(isset($input['startDate']) && !empty(trim($input['startDate'])) && strtotime($input['startDate'])) {
    		$query->where('inf_player_activity_audit.created_at','>=',$input['startDate']);
    	} else{
            $query->where('inf_player_activity_audit.created_at','>=',date('Y-m-d').' 00:00:00');
        }

    	if(isset($input['endDate']) && !empty(trim($input['endDate'])) && strtotime($input['endDate'])) {
    		$query->where('inf_player_activity_audit.created_at','<',$input['endDate']);
    	}

    	if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
    		$query->where('inf_player_activity_audit.user_name','like','%'.$input['user_name'].'%');
    	}

    	$total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        return returnApiJson('操作成功',1, ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function activitiesAuth()
    {
        $input          = request()->all();

        if(!isset($input['status']) || !in_array($input['status'], [1,2])) {
            return returnApiJson('对不起，状态取值不正确', 0);
        }

        if($input['status']==1){
            if(!isset($input['gift_amount']) || !is_numeric($input['gift_amount']) || $input['gift_amount'] <= 0 ) {
                return returnApiJson('对不起，礼金金额取值错误', 0);
            }

            if(!isset($input['withdraw_flow_limit']) || !is_numeric($input['withdraw_flow_limit']) || $input['withdraw_flow_limit'] <= 0) {
                return returnApiJson('对不起，流水限制取值不正确', 0);
            }
        }

        if(!isset($input['id']) || empty($input['id'])) {
            return returnApiJson('对不起，id不存在或者为空', 0);
        }

        $playerActivityAudit = PlayerActivityAudit::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
        if(!$playerActivityAudit) {
             return returnApiJson('对不起，对不起此条数据不存在', 0);
        }

        if($playerActivityAudit->status != 0) {
             return returnApiJson('对不起，对不起此条数据已被处理', 0);
        }

        $res = $playerActivityAudit->activitiesAuth($this->carrierUser,$this->carrier);
        if($res === true) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }

    }

    public function activitiesLuckdrawList(){
        $carrierActivityLuckDraw = CarrierActivityLuckDraw::where('carrier_id',$this->carrier->id)->get();
        return returnApiJson('操作成功', 1,$carrierActivityLuckDraw);
    }

    public function activitiesLuckdrawAdd($id=0){
        if($id) {
            $carrierActivityLuckDraw = CarrierActivityLuckDraw::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
            if(!$carrierActivityLuckDraw){
                return returnApiJson('对不起，对不起此幸运轮盘不存在', 0);
            }   
        } else {
            $carrierActivityLuckDraw = new CarrierActivityLuckDraw(); 
        }

        $res = $carrierActivityLuckDraw->saveItem($this->carrierUser,$this->carrier);
        if($res===true){
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function activitiesLuckdrawEdit($id)
    {
        $carrierActivityLuckDraw = CarrierActivityLuckDraw::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if(!$carrierActivityLuckDraw){
            return returnApiJson('对不起，对不起此幸运轮盘不存在', 0);
        } 
        return returnApiJson('操作成功', 1,$carrierActivityLuckDraw);
    }

    public function activitiesLuckdrawStatus($id){
        $carrierActivityLuckDraw =  CarrierActivityLuckDraw::where('id',$id)->where('carrier_id',$this->carrier->id)->first();
        $openActivityLuckDraw    =  CarrierActivityLuckDraw::where('carrier_id',$this->carrier->id)->where('status',1)->first();

        if(!$carrierActivityLuckDraw){
            return returnApiJson('对不起，此幸运轮盘不存在', 0);
        }

        if($openActivityLuckDraw && !$carrierActivityLuckDraw->status ){
            return returnApiJson('对不起，您只能开启一个幸运轮盘活动', 0);
        }

        $carrierActivityLuckDraw->status = $carrierActivityLuckDraw->status?0:1;
        $carrierActivityLuckDraw->save();

        return returnApiJson('操作成功', 1);
    }

    public function activityPlayerLuckDrawList() 
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = CarrierActivityPlayerLuckDraw::select('inf_carrier_activity_player_luck_draw.*','inf_carrier_activity_luck_draw.name')->leftJoin('inf_carrier_activity_luck_draw','inf_carrier_activity_luck_draw.id','=','inf_carrier_activity_player_luck_draw.luck_draw_id')->where('inf_carrier_activity_player_luck_draw.carrier_id',$this->carrier->id)->orderBy('inf_carrier_activity_player_luck_draw.id','desc');

        if(isset($input['player_id']) && !empty($input['player_id'])) {
            $query->where('inf_carrier_activity_player_luck_draw.player_id',$input['player_id']);
        }
        if(isset($input['user_name']) && !empty($input['user_name'])) {
            $query->where('inf_carrier_activity_player_luck_draw.user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['name']) && !empty($input['name'])) {
            $query->where('inf_carrier_activity_luck_draw.name',$input['name']);
        }

        if(isset($input['startDate']) && strtotime($input['startDate'].' 00:00:00')) {
            $query->where('inf_carrier_activity_player_luck_draw.created_at','>=',$input['startDate']);
        } else {
            $query->where('inf_carrier_activity_player_luck_draw.created_at','>=',date('Y-m-d').' 00:00:00');
        }

        if(isset($input['endDate']) && strtotime($input['endDate'].' 23:59:59')) {
            $query->where('inf_carrier_activity_player_luck_draw.created_at','<=',$input['endDate'].' 23:59:59');
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        return returnApiJson('操作成功', 1, ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function giftcodeList()
    {
        $carrierActivityGiftCode = CarrierActivityGiftCode::giftCodeList($this->carrier);
        return returnApiJson('操作成功', 1,$carrierActivityGiftCode);
    }

    public function giftcodeSave()
    {
        $input = request()->all();

        if(!isset($input['startTime']) || !strtotime($input['startTime'])){
            return returnApiJson('对不起，开始时间不正确', 0);
        }

        if(!isset($input['endTime']) || !strtotime($input['endTime'])){
             return returnApiJson('对不起，结束时间不正确', 0);
        }

        if(!isset($input['prefix']) || empty($input['prefix'])){
             return returnApiJson('对不起，站点取值不正确', 0);
        }

        if(!isset($input['gift_probability']) || !is_array($input['gift_probability'])){
            return returnApiJson('对不起，生成梯度取值不正确', 0);
        } 

        $preProbabilityArr               = 0;
        $allprobability                  = 0;

        foreach ($input['gift_probability'] as $k => &$v) {
            $allprobability                    += $v['probability'];
            $preProbabilityArr                  = $v['probability']+$preProbabilityArr;
            $v['probability']                   = $preProbabilityArr;
        }

        if($allprobability!=100){
            return returnApiJson('对不起，总概率必须等于100', 0);
        }

        if(!isset($input['betflowmultiple']) || !is_numeric($input['betflowmultiple']) || $input['betflowmultiple'] < 0){
            return returnApiJson('对不起，流水倍数取值不正确', 0);
        }

        if(!isset($input['number']) || !is_numeric($input['number']) || intval($input['number']) != $input['number'] || $input['number'] < 1 || $input['number']>1000){
            return returnApiJson('对不起，数量取值不正确', 0);
        }

        $existGiftCodes = CarrierActivityGiftCode::where('carrier_id',$this->carrier->id)->pluck('gift_code')->toArray();
        $giftCodes      = [];

        for($i=1;$i<=$input['number'];){
            $giftCode = randGiftCode();
            if(!in_array($giftCode,$existGiftCodes) && !in_array($giftCode,$giftCodes)){
                $giftCodes[] = $giftCode;
                $i++;
            }
        }

        $insertRows  = [];

        foreach ($giftCodes as $key => $value) {
            $row                                       = [];
            $row['carrier_id']                         = $this->carrier->id;
            $row['name']                               = '系统发放体验券';
            $row['startTime']                          = strtotime($input['startTime']);
            $row['endTime']                            = strtotime($input['endTime'].' 23:59:59');
            $seedProbability                           = rand(1,100);

            foreach ($input['gift_probability'] as $key1 => $value1) {
                if($seedProbability<=$value1['probability']){
                    $row['money'] = rand($value1['giftamount'],$value1['giftmaxamount']);
                    break;
                }
            }

            $row['betflowmultiple']                    = $input['betflowmultiple'];
            $row['type']                               = 1;
            $row['prefix']                             = $input['prefix'];
            $row['gift_code']                          = $value;
            $row['betflow_limit_category']             = isset($input['betflow_limit_category']) && !is_null($input['betflow_limit_category']) ? $input['betflow_limit_category']:'';
            $row['betflow_limit_main_game_plat_id']    = isset($input['betflow_limit_main_game_plat_id']) && !is_null($input['betflow_limit_main_game_plat_id']) ? $input['betflow_limit_main_game_plat_id']:'';
            $row['created_at']                         = date('Y-m-d H:i:s');
            $row['updated_at']                         = date('Y-m-d H:i:s');
            $insertRows[]                              = $row;
        }

        \DB::table('inf_carrier_activity_gift_code')->insert($insertRows);

        return returnApiJson('操作成功', 1);
    }

    public function giftcodeDel($id=0)
    {
        $carrierActivityGiftCode = CarrierActivityGiftCode::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if($carrierActivityGiftCode->status==1){
            return returnApiJson('对不起，此礼品券已被使用无法删除', 0);
        } else{
            $carrierActivityGiftCode->delete();
            return returnApiJson('操作成功', 1);
        }
    }

    public function giftcodeDistribute()
    {
        $input = request()->all();
        if(!isset($input['number']) || !is_numeric($input['number']) || $input['number'] < 1 || $input['number']> 1000){
            return returnApiJson('对不起，提取的数据必须在1至500之间', 0);
        }

        if(!isset($input['amount']) || !is_numeric($input['amount']) || $input['amount'] < 0){
            return returnApiJson('对不起，提取的金额取值不正确', 0);
        }

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return returnApiJson('对不起，站点取值不正确', 0);
        }

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return returnApiJson('对不起，用户ID取值不正确', 0);
        }
        
        $player = Player::where('player_id',$input['player_id'])->where('prefix',$input['prefix'])->first();

        if(!$player){
            return returnApiJson('对不起，此用户不存在', 0);
        }

        if($input['amount']>0){
            $carrierActivityGiftCodes = CarrierActivityGiftCode::select('id','gift_code','carrier_id','money','betflowmultiple','endTime')->where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('distributestatus',0)->where('money',$input['amount'])->where('type',1)->limit($input['number'])->get();
        } else{
            $carrierActivityGiftCodes = CarrierActivityGiftCode::select('id','gift_code','carrier_id','money','betflowmultiple','endTime')->where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('distributestatus',0)->where('type',1)->limit($input['number'])->get();
        }
        
        if(count($carrierActivityGiftCodes) != $input['number']){
            return returnApiJson('对不起，注册体验券的数量仅存'.count($carrierActivityGiftCodes), 0);
        }

        $carrierActivityGiftCodeIds = [];
        $insertData                 = [];
        foreach ($carrierActivityGiftCodes as $key => $value) {
            $row                          = [];
            $row['carrier_id']            = $value->carrier_id;
            $row['player_id']             = $input['player_id'];
            $row['gift_code']             = $value->gift_code;
            $row['money']                 = $value->money;
            $row['betflowmultiple']       = $value->betflowmultiple;
            $row['endTime']               = $value->endTime;
            $row['status']                = 0;
            $row['prefix']                = $input['prefix'];
            $row['created_at']            = date('Y-m-d H:i:s');
            $row['updated_at']            = date('Y-m-d H:i:s');
            $insertData[]                 = $row;

            $carrierActivityGiftCodeIds[] = $value->id;
        }

        \DB::table('inf_player_hold_gift_code')->insert($insertData);
        CarrierActivityGiftCode::whereIn('id',$carrierActivityGiftCodeIds)->update(['distributestatus'=>1,'player_id'=>$input['player_id']]);

        return returnApiJson('操作成功', 1);
    }


    public function activityOfflineGiftcode()
    {
        $input = request()->all();
        if(!isset($input['number']) || !is_numeric($input['number']) || $input['number'] < 1 || $input['number']> 1000){
            return returnApiJson('对不起，提取的数据必须在1至500之间', 0);
        }

        if(!isset($input['amount']) || !is_numeric($input['amount']) || $input['amount'] < 0){
            return returnApiJson('对不起，提取的金额取值不正确', 0);
        }

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return returnApiJson('对不起，站点取值不正确', 0);
        }

        if($input['amount']>0){
            $carrierActivityGiftCodes = CarrierActivityGiftCode::select('id','gift_code','carrier_id','money','betflowmultiple','endTime')->where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('distributestatus',0)->where('status',0)->where('money',$input['amount'])->where('type',1)->limit($input['number'])->get();
        } else{
            $carrierActivityGiftCodes = CarrierActivityGiftCode::select('id','gift_code','carrier_id','money','betflowmultiple','endTime')->where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('distributestatus',0)->where('status',0)->where('type',1)->limit($input['number'])->get();
        }
        
        if(count($carrierActivityGiftCodes) != $input['number']){
            return returnApiJson('对不起，注册体验券的数量仅存'.count($carrierActivityGiftCodes), 0);
        }

        $carrierActivityGiftCodeIds = [];
        $data                       = [];
        foreach ($carrierActivityGiftCodes as $key => $value) {
            $carrierActivityGiftCodeIds[] = $value->id;
            $data[]                       = $value->gift_code;
        }
        
        CarrierActivityGiftCode::whereIn('id',$carrierActivityGiftCodeIds)->update(['distributestatus'=>1]);

        return returnApiJson('操作成功', 1,$data);
    }

    public function giftcodePersonPersonList()
    {
        $res = PlayerGiftCode::giftcodePersonPersonList($this->carrier);

        if(is_array($res)){
            return returnApiJson('操作成功', 1,$res);
        } else{
            return returnApiJson($res, 0);
        }
    }

    public function activitiesBreakThroughPlayerList()
    {
        $result = PlayerBreakThrough::activitiesBreakThroughPlayerList($this->carrier);

        if(is_array($result)){
            return returnApiJson('操作成功', 1,$result);
        } else {
            return returnApiJson($result, 0);
        }
    }

    public function activitySignInList()
    {
        $res = PlayerSignIn::getList($this->carrier);
        if(is_array($res)){
            return returnApiJson('操作成功', 1,$res);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function activityPlayerRegisterGiftList()
    {
        $res = PlayerTransfer::registerGiftList($this->carrier);
        if(is_array($res)){
            return returnApiJson('操作成功', 1,$res);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function activitiesReceiveGiftCenter()
    {
        $res  = PlayerReceiveGiftCenter::receiveList($this->carrier);
        if(is_array($res)){
            return returnApiJson('操作成功', 1,$res);
        } else {
             return returnApiJson($res, 0);
        }
    }

    public function rankList()
    {
        $res = RankingList::getList($this->carrier);

        if(is_array($res)){
            return returnApiJson('操作成功', 1,$res);
        } else {
             return returnApiJson($res, 0);
        }
    }

    public function addRank($id=0)
    {
        if($id==0){
            $rankingList = new RankingList();
        } else{
            $rankingList = RankingList::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        }

        $res = $rankingList->addRank($this->carrier);

        if($res===true){
            return returnApiJson('操作成功', 1);
        } else{
            return returnApiJson($res, 0);
        }
    }

    public function changeRankStatus($id)
    {
        $input       = request()->all();

        $rankingList = RankingList::where('carrier_id',$this->carrier->id)->where('id',$id)->first();

        if(!$rankingList){
            return $this->returnApiJson("对不起，此条数据不存在", 0);
        }

        $rankingList->status = $rankingList->status ? 0:1;
        $rankingList->save();

        return $this->returnApiJson("操作成功", 1);
    }

    public function taskList()
    {
        $res = TaskSetting::taskList($this->carrier);
        if(is_array($res)){
            return $this->returnApiJson("操作成功", 1,$res);
        } else{
            return $this->returnApiJson($res, 0);
        }
    }

    public function taskAdd($id=0)
    {
        if($id){
            $taskSetting = TaskSetting::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
            if(!$taskSetting){
                return $this->returnApiJson('对不起此任务不存在', 0);
            }
        } else{
            $taskSetting = new TaskSetting();
        }
        $res      = $taskSetting->taskAdd($this->carrier);
        if($res===true){
            return $this->returnApiJson("操作成功", 1);
        } else{
            return $this->returnApiJson($res, 0);
        }
    }

    public function taskChangeStatus($id)
    {
        $taskSetting = TaskSetting::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if(!$taskSetting){
            return $this->returnApiJson('对不起此任务不存在', 0);
        }

        $taskSetting->status = $taskSetting->status ? 0 : 1;
        $taskSetting->save();
        
        return $this->returnApiJson("操作成功", 1);
    }

    public function taskDel($id)
    {
        $taskSetting = TaskSetting::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if(!$taskSetting){
            return $this->returnApiJson('对不起此任务不存在', 0);
        }

        $taskSetting->delete();

        return $this->returnApiJson("操作成功", 1);
    }

    public function flowcommissionlist()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerCommission::where('carrier_id',$this->carrier->id)->orderBy('id','desc');
        $query1         = PlayerCommission::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id);

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $query->where('user_name','like','%'.$input['user_name'].'%');
            $query1->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('player_id',$input['player_id']);
            $query1->where('player_id',$input['player_id']);
        }

        if(isset($input['parent_id']) && !empty($input['parent_id'])){
            $query->where('parent_id',$input['parent_id']);
            $query1->where('parent_id',$input['parent_id']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
            $query1->where('prefix',$input['prefix']);
        }

        if(isset($input['status']) && in_array($input['status'],[0,1])){
            $query->where('status',$input['status']);
            $query1->where('status',$input['status']);
        }

        if(isset($input['startDate']) && strtotime($input['startDate']) ){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $query1->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate']) ){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $query1->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $total                = $query->count();
        $data                 = $query->skip($offset)->take($pageSize)->get();
        $playerCommissionStat = $query1->first();

        if(is_null($playerCommissionStat->amount)){
            $playerCommissionStat->amount = 0;
        }

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }

        foreach ($data as $key => &$value) {
            if($value->send_time){
                $value->send_time = date('Y-m-d H:i:s',$value->send_time);
            } else{
                $value->send_time = '';
            }

            $value->multiple_name = $carrierPreFixDomainArr[$value->prefix];
        }

        return $this->returnApiJson("操作成功", 1,['playerCommissionStat'=> $playerCommissionStat,'data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }


    public function realFlowcommissionList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerRealCommission::where('carrier_id',$this->carrier->id)->orderBy('id','desc');
        $query1         = PlayerRealCommission::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id);

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $query->where('user_name','like','%'.$input['user_name'].'%');
            $query1->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('player_id',$input['player_id']);
            $query1->where('player_id',$input['player_id']);
        }

        if(isset($input['parent_id']) && !empty($input['parent_id'])){
            $query->where('parent_id',$input['parent_id']);
            $query1->where('parent_id',$input['parent_id']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
            $query1->where('prefix',$input['prefix']);
        }

        $total                = $query->count();
        $data                 = $query->skip($offset)->take($pageSize)->get();
        $playerCommissionStat = $query1->first();

        if(is_null($playerCommissionStat->amount)){
            $playerCommissionStat->amount = 0;
        }

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }

        foreach ($data as $key => &$value) {
            $value->multiple_name = $carrierPreFixDomainArr[$value->prefix];
        }

        return $this->returnApiJson("操作成功", 1,['playerCommissionStat'=> $playerCommissionStat,'data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function realFlowcommissionDesc($id)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query                = PlayerRealCommissionTongbao::where('carrier_id',$this->carrier->id)->where('receive_player_id',$id)->orderBy('player_id','desc');

        $total                = $query->count();
        $data                 = $query->skip($offset)->take($pageSize)->get();

        foreach ($data as $key => &$value) {
            $value->scale = bcdiv($value->scale*100,1,2);
        }

        return $this->returnApiJson("操作成功", 1,['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function capitationFeeList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerCapitationFee::where('carrier_id',$this->carrier->id)->orderBy('id','desc');

        if(isset($input['status']) && !empty($input['status'])){
            $query->where('status',$input['status']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('created_at','>=',date('Y-m-d',strtotime($input['startDate'])).' 00:00:00');
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('created_at','<=',date('Y-m-d',strtotime($input['startDate'])).' 23:59:59');
        }

        $total    = $query->count();
        $items    = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }
        
        foreach ($items as $key => &$value) {
            $value->multiple_name = $carrierPreFixDomainArr[$value->prefix];
        }

        return $this->returnApiJson("操作成功", 1,['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function capitationFeelChangeStatus($id)
    {
        $input                    = request()->all();
        $existPlayerCapitationFee = PlayerCapitationFee::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if(!$existPlayerCapitationFee){
            return $this->returnApiJson("对不起，此条数据不存在", 0);
        }

        if(!isset($input['type']) || !in_array($input['type'],[-1,1])){
            return $this->returnApiJson("对不起，状态取值不正确", 0);
        }

        $existPlayerCapitationFee->status = $input['type'];
        $existPlayerCapitationFee->save();

        return $this->returnApiJson("操作成功", 1);
    }
}
