<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Auth\Authenticatable;
use App\Http\Controllers\Admin\BaseController;
use App\Models\Map\CarrierGamePlat;
use App\Models\Bet\Lottery;
use App\Models\Bet\SourceLottery;
use App\Models\Def\SmsPassage;
use App\Models\CarrierServiceTeam;
use App\Models\CarrierUser;
use App\Models\CarrierIps;
use App\Models\Carrier;
use App\Models\CarrierNotice;
use App\Jobs\DeletePlayerJob;
use App\Lib\Cache\CarrierCache;

class CarrierController extends BaseController
{
    use Authenticatable;

    public function carrierAdd ($carrierId = 0) 
    {
        if ( !$carrierId ) {
            $carrier = new Carrier();
        } else {
            $carrier = Carrier::find($carrierId);
            if ( !$carrier ) {
                return returnApiJson("对不起, 此商户不存在!", 0);
            }

            CarrierCache::forgetCarrier($carrier->sign);
        }

        CarrierCache::forgetCarrierIds();
        $res = $carrier->saveItem();
        if ( $res === true ) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function carrierList () 
    {
        $input = request()->all();
        $data  = Carrier::getList($input);

        return returnApiJson('操作成功', 1, $data);
    }

    public function carrierChangeStatus ($carrierId) 
    {
        $carrier = Carrier::find($carrierId);

        if ( !$carrier ) {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        $carrier->carrierChangeStatus();

        return returnApiJson('操作成功', 1);
    }

    public function carrierUserList () 
    {
        $res = Carrier::carrierUserList();

        return returnApiJson('操作成功', 1, $res);
    }

    public function carrierUserAdd () 
    {
        $carrierUserId = request()->get('carrierUserId', 0);

        if ( $carrierUserId ) {
            $carrierUser = CarrierUser::find($carrierUserId);
            if ( !$carrierUser ) {
                return returnApiJson("对不起, 此管理员不存在!", 0);
            }
        } else {
            $carrierUser = new CarrierUser();
        }

        $res = $carrierUser->saveItem();
        if ( $res === true ) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function carrierUserUpdatePassword($carrierUserId = 0)
    {
        if ( $carrierUserId ) {
            $carrierUser = CarrierUser::find($carrierUserId);
            if ( !$carrierUser ) {
                return returnApiJson("对不起, 此管理员不存在!", 0);
            }

            $carrierUser->password = bcrypt(request()->get('password'));
            $carrierUser->save();

            return returnApiJson('操作成功', 1);

        } else {
            return returnApiJson("对不起, 此管理员不存在!", 0);
        }
    }

    public function carrierServiceTeamList ( $carrierId ) 
    {
        if ( $carrierId ) {
            $carrier = Carrier::find($carrierId);
            if ( !$carrier ) {
                return returnApiJson("对不起, 此商户不存在!", 0);
            }
        }

        $data = CarrierServiceTeam::orderBy('id', 'asc')->get();
        return returnApiJson('操作成功', 1, $data);
    }

    public function carrierUserChangeStatus ( $carrierUserId ) 
    {
        if ( $carrierUserId ) {
            $carrierUser = CarrierUser::find($carrierUserId);
            if ( !$carrierUser ) {
                return returnApiJson("对不起, 此管理员不存在!", 0);
            }
        }

        $carrierUser->status = $carrierUser->status ? 0 : 1;
        $carrierUser->save();

        return returnApiJson('操作成功', 1);
    }

    public function carrierGameplats ( $carrierId = 0 ) 
    {

        $carrier = Carrier::find($carrierId);
        if ( !$carrier ) {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        $res = $carrier->carrierGameplats();
        if ( is_array($res) ) {
            return returnApiJson('操作成功', 1, $res);
        } else {
            return returnApiJson($res, 0);
        }

    }

    public function carrierGameplatsSave ( $carrierId ) 
    {
        $carrier = Carrier::find($carrierId);
        if ( !$carrier ) {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        $res = $carrier->carrierGameplatsSave();
        if ( $res === true ) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function carrierPayFactorys ( $carrierId = 0 ) 
    {

        $carrier = Carrier::find($carrierId);
        if ( !$carrier ) {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        $res = $carrier->carrierPayFactorys();
        if ( is_array($res) ) {
            return returnApiJson('操作成功', 1, $res);
        } else {
            return returnApiJson($res, 0);
        }

    }

    public function carrierPayFactorysSave ( $carrierId ) 
    {
        $carrier = Carrier::find($carrierId);
        if ( !$carrier ) {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        $res = $carrier->carrierPayFactorysSave();
        if ( $res === true ) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    /**
     * 商户彩种列表
     *
     * @param int $carrierId
     * @return mixed
     */
    public function carrierLotteryList ( $carrierId = 0 ) 
    {
        $carrierId = $this->getSafeParaByKey('carrier_id', 0);
        $carrier   = Carrier::find($carrierId);
        if ( !$carrier ) {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        # 全部彩种
        $sourceLottery = SourceLottery::getDataList([
            'columns' => 'id, name, title, short_name, is_hot, is_mobile, sort',
            'order'   => 'sort,id'
        ]);

        # 已分配彩种
        $carrierLottery = Lottery::getDataList([
            'data'    => [ 'carrier_id' => $carrierId ],
            'columns' => 'id, carrier_id, lott_id, name, title, short_name, is_hot, is_mobile, sort',
            'order'   => 'sort,id'
        ]);

        $this->success('获取成功', [
            'carrier'        => $carrier,
            'sourceLottery'  => $sourceLottery,
            'carrierLottery' => $carrierLottery,
        ]);

    }


    /**
     * 商户彩种列表分配
     *
     * @param int $carrierId
     * @return mixed
     * @throws \Exception
     */
    public function carrierLotterySave ( $carrierId = 0 ) 
    {
        $params = $this->getSafeParams();

        $carrierId = $this->getSafeParaByKey('carrier_id', 0);
        $carrier   = Carrier::find($carrierId);
        if ( !$carrier ) {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        $res = Carrier::carrierLotterySave($carrierId, $params['lott_ids']);

        $this->success('商户彩种分配成功!');
    }

    public function carrierRemainQuotaAdd ( $carrierId ) 
    {
        $carrier = Carrier::find($carrierId);
        if ( !$carrier ) {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        $res = $carrier->carrierRemainQuotaAdd();
        if ( $res === true ) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function carrierIpsList ( $carrierId ) 
    {
        $carrier = Carrier::find($carrierId);
        if ( !$carrier ) {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        $data = CarrierIps::where('carrier_id', $carrierId)->get();
        return returnApiJson('操作成功', 1, $data);
    }

    public function carrierIpsAdd ( $carrierId ) 
    {
        $input = request()->all();

        $carrier = Carrier::find($carrierId);
        if ( !$carrier ) {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        if ( !isset($input['login_ip']) || !filter_var($input['login_ip'], FILTER_VALIDATE_IP) ) {
            return returnApiJson("对不起, IP不正确!", 0);
        }

        $carrierIps             = new CarrierIps();
        $carrierIps->login_ip   = request()->login_ip;
        $carrierIps->carrier_id = $carrierId;
        $carrierIps->save();

        return returnApiJson('操作成功', 1);
    }

    public function carrierIpsDel ( $carrierIps ) 
    {
        CarrierIps::where('id', $carrierIps)->delete();

        return returnApiJson('操作成功', 1);
    }

    public function carrierCasinoAdd ( $carrierId = 0 ) 
    {
        if ( $carrierId ) {
            $carrier = Carrier::find($carrierId);
            if ( !$carrier ) {
                return returnApiJson("对不起, 此商户不存在!", 0);
            }
        } else {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        $res = $carrier->carrierCasinoAdd();
        
        CarrierCache::flushCarrierConfigure($carrier->sign);
         
        if ( $res == true ) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function pointlist($carrierId = 0)
    {
        if ( $carrierId ) {
            $carrier = Carrier::find($carrierId);
            if ( !$carrier ) {
                return returnApiJson("对不起, 此商户不存在!", 0);
            }
        } else {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        $gamePlats = CarrierGamePlat::select('id','carrier_id','game_plat_id','point')->where('carrier_id',$carrierId)->get();
        return returnApiJson("操作成功", 1,$gamePlats);
    }

    public function carriercasinopointupdate($carrierId = 0)
    {
        $input = request()->all();

        if ( $carrierId ) {
            $carrier = Carrier::find($carrierId);
            if ( !$carrier ) {
                return returnApiJson("对不起, 此商户不存在!", 0);
            }
        } else {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        if(!isset($input['ids'])){
            return returnApiJson("对不起, 平台ID不能为空!", 0);
        }

        $ids = explode(',',$input['ids']);

        $carrierGamePlatCount = CarrierGamePlat::whereIn('id',$ids)->count();

        if(count($ids) != $carrierGamePlatCount){
            return returnApiJson("对不起, 平台Id取值不正确!", 0);
        }

        if(!isset($input['points'])){
            return returnApiJson("对不起, 平台点位不能为空!", 0);
        }

        $points = explode(',',$input['points']);
        if(count($ids) != count($ids)){
            return returnApiJson("对不起, 平台ID与平台点位个数不相等!", 0);
        } 

        for($i=0;$i<count($ids);$i++){
            CarrierGamePlat::where('id',$ids[$i])->update(['point'=>$points[$i]]);
        } 

        return returnApiJson("操作成功", 1);
    }

    public function selfpointupdate($carrierId = 0)
    {
        $input = request()->all();

        if ( $carrierId ) {
            $carrier = Carrier::find($carrierId);
            if ( !$carrier ) {
                return returnApiJson("对不起, 此商户不存在!", 0);
            }
        } else {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        $carrier->save();
        return returnApiJson("操作成功", 1);
    }

    public function carrierNoticeList()
    {
        $carrierNotice = new CarrierNotice();
        $result        = $carrierNotice->carrierNoticeList();
        if(is_array($result)){
            return returnApiJson("操作成功", 1,$result);
        } else {
            return returnApiJson($result, 0);
        }
    }

    public function carrierNoticeAdd($carrierNoticeId=0)
    {
        if($carrierNoticeId){
            $carrierNotice = CarrierNotice::where('id',$carrierNoticeId)->first();
            if(!$carrierNotice){
                return returnApiJson("对不起，此通知不存在", 0);  
            }
        } else {
            $carrierNotice = new CarrierNotice();
        }

        $result = $carrierNotice->carrierNoticeAdd();
        if($result===true){
            return returnApiJson("操作成功", 1);
        } else {
            return returnApiJson($result, 0);
        }
    }

    public function carrierNoticeDelete($carrierNoticeId=0)
    {
        if($carrierNoticeId){
            $carrierNotice = CarrierNotice::where('id',$carrierNoticeId)->first();
            if($carrierNotice){
                $carrierNotice->delete();
                return returnApiJson("操作成功", 1);
            } else {
                return returnApiJson("对不起，此通知ID不正确", 0);  
            }
        } else {
            return returnApiJson("对不起，通知ID不能为空", 0);
        }
    }

    public function allCarrier()
    {
        $data  = Carrier::select('id','name')->orderBy('id','asc')->get();
        return returnApiJson("操作成功", 1,$data);
    }

    public function carriersFlushPlayer($carrierId=0)
    {
        $input = request()->all();

        if ( $carrierId ) {
            $carrier = Carrier::find($carrierId);
            if ( !$carrier ) {
                return returnApiJson("对不起, 此商户不存在!", 0);
            }
        } else {
            return returnApiJson("对不起, 此商户不存在!", 0);
        }

        dispatch(new DeletePlayerJob($this->carrier));

        return returnApiJson("操作成功", 1);
    }

    // 关闭按钮
    public function carrierUserCloseGoogle($carrierUserId)
    {
        $carrierUser = CarrierUser::where('id',$carrierUserId)->first();

        if(!$carrierUser){
            return returnApiJson("对不起, 此商户管理员不存在!", 0);
        }

        $carrierUser->remember_token       = '';
        $carrierUser->google_img           = '';
        $carrierUser->bind_google_status   = 0;
        $carrierUser->save();

        return returnApiJson('操作成功', 1);
    }
}
