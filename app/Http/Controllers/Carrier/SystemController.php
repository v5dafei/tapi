<?php

namespace App\Http\Controllers\Carrier;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\ErrMsg;
use App\Models\Log\AdminSession;
use App\Models\PlayerAccount;
use App\Utils\Date\DateHelper;
use App\Utils\File\FileHelper;
use App\Utils\Helper;
use App\Http\Controllers\Carrier\BaseController;
use App\Models\RolesModel\PermissionGroup;
use App\Models\RolesModel\PermissionServiceTeam;
use App\Models\RolesModel\Permission;
use App\Models\CarrierBankCardType;
use App\Models\Conf\CarrierPayChannel;
use App\Models\PlayerInviteCode;
use App\Models\Conf\SysTelegramChannel;
use App\Models\Conf\CarrierWebSite;
use App\Models\Conf\PlayerSetting;
use App\Models\Map\CarrierGamePlat;
use App\Models\Map\CarrierGame;
use App\Models\Def\MainGamePlat;
use App\Models\CarrierServiceTeam;
use App\Models\Log\PlayerWithdraw;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\PlayerActivityAudit;
use App\Models\Lottery\SscLottery;
use App\Models\CarrierUser;
use App\Models\CarrierBankCard;
use App\Models\Player;
use App\Models\Carrier;
use App\Models\PlayerBankCard;
use App\Models\PlayerIpBlack;
use App\Models\Language;
use App\Jobs\TelegramJob;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;
use App\Lib\Telegram;
use App\Lib\Oss;
use App\Models\Def\ThirdWallet;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use App\Models\PlayerCommission;
use App\Models\CarrierHorizontalMenu;
use App\Models\PlayerTransfer;
use App\Models\CarrierGuaranteed;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\CarrierPreFixDomain;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\CarrierCapitationFeeSetting;
use App\Models\Log\PlayerGiftCode;
use App\Lib\DevidendMode1;
use App\Lib\DevidendMode2;
use App\Lib\DevidendMode3;
use App\Lib\DevidendMode5;
use App\Lib\DevidendMode4;
use App\Models\Log\CarrierAdminLog;
use App\Models\Map\CarrierPreFixGamePlat;
use App\Models\CarrierPop;
use App\Models\Log\PlayerBetFlow;
use App\Models\Def\Game;
use App\Game\Game as Games;
use App\Models\Def\DigitalAddressLib;
use App\Models\Def\Domain;
use App\Models\ArbitrageBank;
use App\Lib\Cache\Lock;
use App\Models\Log\PlayerLogin;
use App\Lib\Clog;
use App\Models\CarrierNotice;
use App\Models\Currency;
use App\Models\Conf\CurrencyWebSite;
use App\Models\PlayerAlipay;
use App\Models\PlayerDigitalAddress;
use App\Models\GameLine;


class SystemController extends BaseController
{
    use Authenticatable;

    // 登录
    public function addReduceList()
    {
        $input = request()->all();
        $data  = config('main')['addReduceList'];
        $data['add']['agent_reimbursement'] = '代理报销 (计入充值)';
        $data['add']['game_score_add'] = '游戏补分 (不计入库存)';
        $data['add']['reimbursement_gift'] = '报销礼金 (不计入充值)';
        return returnApiJson('操作成功', 1, $data);
    }

    public function init()
    {
        return returnApiJson('操作成功', 1,['gameImgResourseUrl'=>config('main')['backcloudstore']]);
    }

    public function menus() {
    	if($this->carrierUser->is_super_admin) {
            //超管，有所有权限
            $permissionGroups = PermissionGroup::where('parent_id',0)->orderBy('sort','asc')->get();
            foreach($permissionGroups as $permissionGroup) {
                $childs                  = PermissionGroup::where('parent_id',$permissionGroup)->orderBy('sort','asc')->get();
                $permissionGroup->childs = $childs;
            }

            $permissions = Permission::orderBy('id','desc')->distinct('group_id')->get();

            $permissionArr = [];
            foreach ($permissions as $value) {
                $permissionArr[$value->group_id]= $value;
            }

            $data['permissionGroups'] = $permissionGroups;
            $data['options']          = $permissionArr;

            return returnApiJson('操作成功', 1, $data);
        }
    }

    public function fileUpload($directory)
    {
        $input        = request()->all();
        $directoryArr = ['levelvip','img','lottery'];

        if(!in_array($directory, $directoryArr)) {
            return returnApiJson('目录不正确', 0);
        }

        $arr = [
            'carrier_id' => $this->carrier->id,
            'directory'  => $directory
        ];

        $res = Oss::uploadImage($input['file'], $arr);

        if(is_array($res)) {
             return returnApiJson('操作成功', 1,$res);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function allCurrencys()
    {
        $currencys = Currency::select('name','zh_name')->orderBy('id','asc')->get();
        return returnApiJson('操作成功',1,$currencys);
    }

    public function platList()
    {
        $data = CarrierGamePlat::select('map_carrier_game_plats.id','def_main_game_plats.alias','map_carrier_game_plats.game_plat_id','map_carrier_game_plats.sort','map_carrier_game_plats.status','map_carrier_game_plats.point')
            ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','map_carrier_game_plats.game_plat_id')
            ->where('map_carrier_game_plats.carrier_id',$this->carrier->id)
            ->orderBy('map_carrier_game_plats.sort','desc')
            ->orderBy('map_carrier_game_plats.id','asc')->get();

        return returnApiJson('操作成功', 1, $data);
    }

    public function platSave($platid)
    {
        $input = request()->all();
        if(!isset($input['sort']) || trim($input['sort']) == '') {
            return returnApiJson('对不起,sort参数不正确', 0);
        }

        if(!isset($input['status']) || !in_array($input['status'],[0,1,2])) {
            return returnApiJson('对不起,status参数不正确', 0);
        }

        $carrierGamePlat = CarrierGamePlat::where('game_plat_id',$platid)->where('carrier_id',$this->carrier->id)->first();
        if(!$carrierGamePlat) {
            return returnApiJson('对不起,平台不存在', 0);
        }

        $carrierGamePlat->sort   = $input['sort'];
        if(isset($input['point']) && is_numeric($input['point']) && $input['point']>=0){
            $carrierGamePlat->point  = $input['point'];
        }
        
        $carrierGamePlat->status = $input['status'];
        $carrierGamePlat->save();

        GameCache::flushCarrierGame($this->carrier->id);

        return returnApiJson('操作成功', 1);
    }

    public function gameList($platid)
    {

        $carrierGamePlat = CarrierGamePlat::where('game_plat_id',$platid)->first();
        if(!$carrierGamePlat) {
            return returnApiJson('对不起,平台不存在', 0);
        }

        $input = request()->all();
        $query = CarrierGame::select('map_carrier_games.*')->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')->orderBy('map_carrier_games.sort','desc')->orderBy('map_carrier_games.id','asc');

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['status']) && in_array($input['status'],[0,1,2])) {
            $query->where('map_carrier_games.status',$input['status']);
        }

        if(isset($input['is_recommend']) && trim($input['is_recommend']) != '') {
            $query->where('map_carrier_games.is_recommend',$input['is_recommend']);
        }

        if(isset($input['is_hot']) && trim($input['is_hot']) != '') {
            $query->where('map_carrier_games.is_hot',$input['is_hot']);
        }

        if(isset($input['is_fish']) && in_array($input['is_fish'], [0,1])) {
            if($input['is_fish']==1){
                $query->where('map_carrier_games.game_category',7);
            } else {
                $query->where('map_carrier_games.game_category','<>',7);
            }
        }

        if(isset($input['display_name']) && trim($input['display_name']) != '') {
            $query->where('map_carrier_games.display_name','like','%'.$input['display_name'].'%');
        }

        $query->where('map_carrier_games.game_plat_id',$platid)->where('map_carrier_games.carrier_id',$this->carrier->id);

        $total  = $query->count();
        $data   = $query->skip($offset)->take($pageSize)->get();

        return returnApiJson('操作成功', 1,['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function changeStatus($carriergameid)
    {
        $input       = request()->all();
        $carriergame = CarrierGame::where('carrier_id',$this->carrier->id)->where('id',$carriergameid)->first();
        if(!$carriergame) {
            return returnApiJson('对不起,游戏不存在', 0);
        }

        if(!isset($input['status']) || !in_array($input['status'],[0,1,2])){
            return returnApiJson('对不起,游戏状态取值不正确', 0);
        }

        if(!$carriergame->status) {
            $carrierGamePlat = CarrierGamePlat::where('game_plat_id',$carriergame->game_plat_id)->first();
            if(!$carrierGamePlat->status){
                 return returnApiJson('对不起平台已关闭，游戏无法开启', 0);
            }
        }

        $carriergame->status = $input['status'];
        $carriergame->save();

        return returnApiJson('操作成功', 1);
    }

    public function changeRecommend($carriergameid)
    {
        $carriergame = CarrierGame::where('carrier_id',$this->carrier->id)->where('id',$carriergameid)->first();
        if(!$carriergame) {
            return returnApiJson('对不起,游戏不存在', 0);
        }

        $carriergame->is_recommend = $carriergame->is_recommend ? 0 : 1;
        $carriergame->save();

        return returnApiJson('操作成功', 1);
    }

    public function changeHot($carriergameid)
    {
        $carriergame = CarrierGame::where('carrier_id',$this->carrier->id)->where('id',$carriergameid)->first();
        if(!$carriergame) {
            return returnApiJson('对不起,游戏不存在', 0);
        }

        $carriergame->is_hot = $carriergame->is_hot ? 0 : 1;
        $carriergame->save();

        return returnApiJson('操作成功', 1);
    }

    public function gameSave($carriergameid)
    {
        $input = request()->all();
        if(!isset($input['display_name']) || trim($input['display_name']) == '') {
            return returnApiJson('对不起,display_name参数不正确', 0);
        }

        if(!isset($input['sort']) || !preg_match("/^[1-9][0-9]*$/" ,$input['sort'])) {
            return returnApiJson('对不起,sort参数不正确', 0);
        }

        $carriergame = CarrierGame::where('carrier_id',$this->carrier->id)->where('id',$carriergameid)->first();
        if(!$carriergame) {
            return returnApiJson('对不起,游戏不存在', 0);
        }

        if(isset($input['status']) && in_array($input['status'],[0,1,2])){
            $carriergame->status       = $input['status'];
        }

        $carriergame->display_name = $input['display_name'];
        $carriergame->sort         = $input['sort'];
        $carriergame->save();

        return returnApiJson('操作成功', 1);
    }

    public function websiteInfo()
    {
        $carrierWebSite = CarrierWebSite::where('carrier_id',$this->carrier->id)->get();

        $data = [];
        foreach ($carrierWebSite as $key => $value) {
            if($value->sign=='sign_in_day_gift'){
                $data[$value->sign] = json_decode($value->value,true);
            } else {
                $data[$value->sign] = $value->value;
            }
        }
        return returnApiJson('操作成功', 1, $data);
    }

    public function getAllLanguage()
    {
        $langages    = CarrierCache::getCarrierConfigure($this->carrier->id,'supportMemberLangMap');
        $langagesArr = explode(',',$langages);
        $data        = [];
        foreach ($langagesArr as $key => $value) {
            switch ($value) {
                case 'zh-cn':
                    $row['key']   ='zh-cn';
                    $row['value'] ='中文简体';
                    $data[]       = $row;
                    break;
                case 'en':
                    $row['key']   ='en';
                    $row['value'] ='英语';
                    $data[]       = $row;
                    break;
                case 'vi':
                    $row['key']   ='vi';
                    $row['value'] ='越南语';
                    $data[]       = $row;
                    break;
                case 'th':
                    $row['key']   ='th';
                    $row['value'] ='泰语';
                    $data[]       = $row;
                    break;
                case 'hi':
                    $row['key']   ='hi';
                    $row['value'] ='印地语';
                    $data[]       = $row;
                    break;
                case 'id':
                    $row['key']   ='id';
                    $row['value'] ='印尼语';
                    $data[]       = $row;
                    break;
                case 'tl':
                    $row['key']   ='tl';
                    $row['value'] ='他加禄语';
                    $data[]       = $row;
                    break;
                default:
                    # code...
                    break;
            }
        }
        return returnApiJson('操作成功', 1, $data);
    }

    public function allLanguages()
    {
        $languages = Language::select('name','zh_name')->orderBy('id','asc')->get();
        return returnApiJson('操作成功',1,$languages);
    }

    public function websiteMultipleSave()
    {
        $input             = request()->all();
        $defaultAgent      =  CarrierCache::getDefaultAgent($this->carrier->id);


        if(!isset($input['prefix']) || empty($input['prefix'])){
            return returnApiJson('对不起前辍不能为空',0);
        }

        if(isset($input['third_wallet']) && is_array($input['third_wallet'])){
            if(count($input['third_wallet'])){
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','third_wallet')->update(['value'=>json_encode($input['third_wallet'])]);
            } else{
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','third_wallet')->update(['value'=>json_encode([])]);
            }
        }elseif(isset($input['third_wallet']) && $input['third_wallet']=='[]'){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','third_wallet')->update(['value'=>json_encode([])]);
        }

        if(isset($input['disable_withdraw_channel']) && is_array($input['disable_withdraw_channel'])){
            if(count($input['disable_withdraw_channel'])){
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','disable_withdraw_channel')->update(['value'=>$input['disable_withdraw_channel']]);
            } else{
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','disable_withdraw_channel')->update(['value'=>array()]);
            }
        }elseif(isset($input['disable_withdraw_channel']) && $input['disable_withdraw_channel']=='[]'){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','disable_withdraw_channel')->update(['value'=>array()]);
        }

        if(isset($input['voucher_need_recharge_amount']) && is_numeric($input['voucher_need_recharge_amount']) && $input['voucher_need_recharge_amount']>=100){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','voucher_need_recharge_amount')->update(['value'=>$input['voucher_need_recharge_amount']]);
        } 

        if(isset($input['voucher_withdraw_max_money']) && is_numeric($input['voucher_withdraw_max_money']) && $input['voucher_withdraw_max_money']>=100){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','voucher_withdraw_max_money')->update(['value'=>$input['voucher_withdraw_max_money']]);
        }

        if(isset($input['finance_min_recharge']) && $input['finance_min_recharge']>0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','finance_min_recharge')->update(['value'=>$input['finance_min_recharge']]);
        } 

        if(isset($input['enable_eidt_telehone_verification']) && in_array($input['enable_eidt_telehone_verification'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_eidt_telehone_verification')->update(['value'=>$input['enable_eidt_telehone_verification']]);
        } 

        if(isset($input['finance_max_recharge']) && $input['finance_max_recharge']>0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','finance_max_recharge')->update(['value'=>$input['finance_max_recharge']]);
        }  

        if(isset($input['in_r_out_u']) && is_numeric($input['in_r_out_u']) && $input['in_r_out_u']>0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','in_r_out_u')->update(['value'=>$input['in_r_out_u']]);
        }

        if(isset($input['in_t_out_u']) && is_numeric($input['in_t_out_u']) && $input['in_t_out_u']>0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','in_t_out_u')->update(['value'=>$input['in_t_out_u']]);
        }

        if(isset($input['enable_voucher_recharge']) && is_numeric($input['enable_voucher_recharge']) && in_array($input['enable_voucher_recharge'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_voucher_recharge')->update(['value'=>$input['enable_voucher_recharge']]);
        }

        if(isset($input['digital_rate']) && is_numeric($input['digital_rate']) && $input['digital_rate']>0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','digital_rate')->update(['value'=>$input['digital_rate']]);
        }

        if(isset($input['withdraw_digital_rate']) && is_numeric($input['withdraw_digital_rate']) && $input['withdraw_digital_rate']>0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','withdraw_digital_rate')->update(['value'=>$input['withdraw_digital_rate']]);
        }

        if(isset($input['short_link_no_register']) && is_numeric($input['short_link_no_register']) && $input['short_link_no_register']>2) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','short_link_no_register')->update(['value'=>$input['short_link_no_register']]);
        }

        if(isset($input['no_delete_short_link']) && is_numeric($input['no_delete_short_link']) && $input['no_delete_short_link']>=1000) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','no_delete_short_link')->update(['value'=>$input['no_delete_short_link']]);
        }

        if(isset($input['sms_passage_id']) && !empty($input['sms_passage_id'])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','withdraw_digital_rate')->update(['value'=>$input['withdraw_digital_rate']]);
        }

        if(isset($input['language']) && !empty($input['language'])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','sms_passage_id')->update(['value'=>$input['language']]);
        }

        if(isset($input['currency']) && !empty($input['currency'])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','sms_passage_id')->update(['value'=>$input['currency']]);
        }

        if(isset($input['open_sign_in']) && in_array($input['open_sign_in'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','open_sign_in')->update(['value'=>$input['open_sign_in']]);
        }

        if(isset($input['stop_exchange_rate']) && is_numeric($input['stop_exchange_rate']) && $input['stop_exchange_rate']>=5 && $input['stop_exchange_rate']<=100 && intval($input['stop_exchange_rate'])==$input['stop_exchange_rate']) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','stop_exchange_rate')->update(['value'=>$input['stop_exchange_rate']]);
        }
        

        if(isset($input['sign_in_category']) && in_array($input['sign_in_category'],[1,2,3])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','sign_in_category')->update(['value'=>$input['sign_in_category']]);
        }

        if(isset($input['not_included_exchange_rate']) && is_numeric($input['not_included_exchange_rate']) && intval($input['not_included_exchange_rate']) == $input['not_included_exchange_rate'] && $input['not_included_exchange_rate']>0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','not_included_exchange_rate')->update(['value'=>$input['not_included_exchange_rate']]);
        }

        if(array_key_exists('materialIds',$input)) {
            if(is_null($input['materialIds'])){
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','materialIds')->update(['value'=>$input['materialIds']]);
            } else{
                $input['materialIds'] = rtrim($input['materialIds'],',');
                $arr = explode(',',$input['materialIds']);
                $tem = Player::whereIn('player_id',$arr)->count();

                if(count($arr)!= $tem || !$tem){
                    return returnApiJson('对不起,素材号取值不正确',0);
                }
                CarrierMultipleFront::where('prefix',$input['prefix'])->where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','materialIds')->update(['value'=>$input['materialIds']]);
            }
        }

        if(array_key_exists('skip_abrbitrageurs_judge_channel',$input)) {
            if(is_null($input['skip_abrbitrageurs_judge_channel'])){
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','skip_abrbitrageurs_judge_channel')->update(['value'=>$input['skip_abrbitrageurs_judge_channel']]);
            } else{
                $input['skip_abrbitrageurs_judge_channel'] = rtrim($input['skip_abrbitrageurs_judge_channel'],',');
                $arr = explode(',',$input['skip_abrbitrageurs_judge_channel']);
                $tem = Player::whereIn('player_id',$arr)->count();

                if(count($arr)!= $tem || !$tem){
                    return returnApiJson('对不起,渠道帐号取值不正确',0);
                }
                CarrierMultipleFront::where('prefix',$input['prefix'])->where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','skip_abrbitrageurs_judge_channel')->update(['value'=>$input['skip_abrbitrageurs_judge_channel']]);
            }
        }

        if(array_key_exists('disable_voucher_channel',$input)) {
            if(is_null($input['disable_voucher_channel'])){
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','disable_voucher_channel')->update(['value'=>$input['disable_voucher_channel']]);
            } else{
                $input['disable_voucher_channel'] = rtrim($input['disable_voucher_channel'],',');
                $arr = explode(',',$input['disable_voucher_channel']);
                $tem = Player::whereIn('player_id',$arr)->count();

                if(count($arr)!= $tem || !$tem){
                    return returnApiJson('对不起,禁止兑换体验券上级代理ID取值不正确',0);
                }
                CarrierMultipleFront::where('prefix',$input['prefix'])->where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','disable_voucher_channel')->update(['value'=>$input['disable_voucher_channel']]);
            }
        }

        if(array_key_exists('disable_voucher_team_channel',$input)) {
            if(is_null($input['disable_voucher_team_channel'])){
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','disable_voucher_team_channel')->update(['value'=>$input['disable_voucher_team_channel']]);
            } else{
                $input['disable_voucher_team_channel'] = rtrim($input['disable_voucher_team_channel'],',');
                $arr = explode(',',$input['disable_voucher_team_channel']);
                $tem = Player::whereIn('player_id',$arr)->count();

                if(count($arr)!= $tem || !$tem){
                    return returnApiJson('对不起,禁止兑换体验券团队ID取值不正确',0);
                }
                CarrierMultipleFront::where('prefix',$input['prefix'])->where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','disable_voucher_team_channel')->update(['value'=>$input['disable_voucher_team_channel']]);
            }
        }

        if(array_key_exists('dividend_enumerate',$input)){
            if(is_null($input['dividend_enumerate'])){
                $input['dividend_enumerate'] = '';
            }
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','dividend_enumerate')->update(['value'=>$input['dividend_enumerate']]);
        }

        if(isset($input['sign_in_need_recharge_amount']) && is_numeric($input['sign_in_need_recharge_amount']) && intval($input['sign_in_need_recharge_amount']) == $input['sign_in_need_recharge_amount'] && $input['sign_in_need_recharge_amount']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','sign_in_need_recharge_amount')->update(['value'=>$input['sign_in_need_recharge_amount']]);
        }

        if(isset($input['sign_in_need_bet_flow']) && is_numeric($input['sign_in_need_bet_flow']) && intval($input['sign_in_need_bet_flow']) == $input['sign_in_need_bet_flow'] && $input['sign_in_need_bet_flow']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','sign_in_need_bet_flow')->update(['value'=>$input['sign_in_need_bet_flow']]);
        }

        if(isset($input['sign_in_flow_limit_multiple']) && is_numeric($input['sign_in_flow_limit_multiple']) && intval($input['sign_in_flow_limit_multiple']) == $input['sign_in_flow_limit_multiple'] && $input['sign_in_flow_limit_multiple']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','sign_in_flow_limit_multiple')->update(['value'=>$input['sign_in_flow_limit_multiple']]);
        }

        if(array_key_exists('sign_in_day_gift',$input)){
            if(is_array($input['sign_in_day_gift'])){
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','sign_in_day_gift')->update(['value'=>json_encode($input['sign_in_day_gift'])]);
            } else{
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','sign_in_day_gift')->update(['value'=>json_encode(array())]);
            }
        }

        if(isset($input['android_down_url']) && !empty($input['android_down_url'])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','android_down_url')->update(['value'=>$input['android_down_url']]);
        }

        if(isset($input['enable_auto_guaranteed_upgrade']) && in_array($input['enable_auto_guaranteed_upgrade'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_auto_guaranteed_upgrade')->update(['value'=>$input['enable_auto_guaranteed_upgrade']]);
        }  

        if(isset($input['h5url']) && !empty($input['h5url'])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','h5url')->update(['value'=>$input['h5url']]);
        }

        if(isset($input['directlyunder_commission_dividends_rate']) && is_numeric($input['directlyunder_commission_dividends_rate']) && $input['directlyunder_commission_dividends_rate'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','directlyunder_commission_dividends_rate')->update(['value'=>$input['directlyunder_commission_dividends_rate']]);
        }

        if(isset($input['enable_rankings']) && in_array($input['enable_rankings'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_rankings')->update(['value'=>$input['enable_rankings']]);
        }

        if(isset($input['rankings_performance_low']) && is_numeric($input['rankings_performance_low']) && $input['rankings_performance_low']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','rankings_performance_low')->update(['value'=>$input['rankings_performance_low']]);
        }

        if(isset($input['rankings_type']) && in_array($input['rankings_type'],[1,2])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','rankings_type')->update(['value'=>$input['rankings_type']]);
        }

        if(isset($input['rankings_cycle']) && in_array($input['rankings_cycle'],[1,2])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','rankings_cycle')->update(['value'=>$input['rankings_cycle']]);
        }

        if(isset($input['enabele_setting_dividends']) && in_array($input['enabele_setting_dividends'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enabele_setting_dividends')->update(['value'=>$input['enabele_setting_dividends']]);
        }

        if(isset($input['enabele_setting_guaranteed']) && in_array($input['enabele_setting_guaranteed'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enabele_setting_guaranteed')->update(['value'=>$input['enabele_setting_guaranteed']]);
        }

        if(isset($input['app_down_url']) && !empty($input['app_down_url'])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','app_down_url')->update(['value'=>$input['app_down_url']]);
        }

        if(array_key_exists('kefu_link',$input)){
            if(is_null($input['kefu_link'])){
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','kefu_link')->update(['value'=>'']);
            } else{
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','kefu_link')->update(['value'=>$input['kefu_link']]);
            }
        }

        if(isset($input['site_title']) && !empty($input['site_title'])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','site_title')->update(['value'=>$input['site_title']]);
        }

        if(isset($input['official_url']) && !empty($input['official_url'])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','official_url')->update(['value'=>$input['official_url']]);
        }

        if(array_key_exists('forcibly_joinfakegame_activityid',$input)){
            if(is_null($input['forcibly_joinfakegame_activityid'])){
                $input['forcibly_joinfakegame_activityid'] = '';
            }
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','forcibly_joinfakegame_activityid')->update(['value'=>$input['forcibly_joinfakegame_activityid']]);
        }

        if(isset($input['live_broadcast_awards']) && !empty($input['live_broadcast_awards'])){

            $liveBroadcastAwardsArr = explode(',', $input['live_broadcast_awards']);
            $gameCount              = Game::whereIn('game_id',$liveBroadcastAwardsArr)->count();
            if(count($liveBroadcastAwardsArr) == $gameCount){
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','live_broadcast_awards')->update(['value'=>$input['live_broadcast_awards']]);
            }
        }

        //默认昵称
        if(array_key_exists('default_nick_name',$input)){
            if(is_null($input['default_nick_name'])){
                $input['default_nick_name'] = '';
            }
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','default_nick_name')->update(['value'=>$input['default_nick_name']]);
        }

        //注册是否需要手机号
        if(isset($input['carrier_register_telehone']) && is_numeric($input['carrier_register_telehone']) && in_array($input['carrier_register_telehone'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','carrier_register_telehone')->update(['value'=>$input['carrier_register_telehone']]);
        }

        //注册是否需要真实姓名
        if(isset($input['register_real_name']) && is_numeric($input['register_real_name']) && in_array($input['register_real_name'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','register_real_name')->update(['value'=>$input['register_real_name']]);
        }

        //运营费比例
        if(isset($input['operating_expenses']) && is_numeric($input['operating_expenses']) && $input['operating_expenses']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','operating_expenses')->update(['value'=>$input['operating_expenses']]);
        }

        //保底级差
        if(isset($input['guaranteed_level_difference']) && is_numeric($input['guaranteed_level_difference']) && $input['guaranteed_level_difference'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','guaranteed_level_difference')->update(['value'=>$input['guaranteed_level_difference']]);
        }

        //审核保底
        if(isset($input['limit_highest_guaranteed'])  && is_numeric($input['limit_highest_guaranteed']) && $input['limit_highest_guaranteed'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','limit_highest_guaranteed')->update(['value'=>$input['limit_highest_guaranteed']]);
        }

        //分红极差
        if(isset($input['dividend_level_difference'])  && is_numeric($input['dividend_level_difference']) && $input['dividend_level_difference'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','dividend_level_difference')->update(['value'=>$input['dividend_level_difference']]);
        }

        //审核保底
        if(isset($input['limit_highest_dividend'])  && is_numeric($input['limit_highest_dividend']) && $input['limit_highest_dividend'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','limit_highest_dividend')->update(['value'=>$input['limit_highest_dividend']]);
        }

        //真人流水计算百分比
        if(isset($input['casino_betflow_calculate_rate']) && is_numeric($input['casino_betflow_calculate_rate']) && $input['casino_betflow_calculate_rate']>=0 and $input['casino_betflow_calculate_rate']<=100){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','casino_betflow_calculate_rate')->update(['value'=>$input['casino_betflow_calculate_rate']]);
        }

        //电子流水计算百分比
        if(isset($input['electronic_betflow_calculate_rate']) && is_numeric($input['electronic_betflow_calculate_rate']) && $input['electronic_betflow_calculate_rate']>=0 and $input['electronic_betflow_calculate_rate']<=100){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','electronic_betflow_calculate_rate')->update(['value'=>$input['electronic_betflow_calculate_rate']]);
        }

        //电竞流水计算百分比
        if(isset($input['esport_betflow_calculate_rate']) && is_numeric($input['esport_betflow_calculate_rate']) && $input['esport_betflow_calculate_rate']>=0 and $input['esport_betflow_calculate_rate']<=100){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','esport_betflow_calculate_rate')->update(['value'=>$input['esport_betflow_calculate_rate']]);
        }

        //捕鱼流水计算百分比
        if(isset($input['fish_betflow_calculate_rate']) && is_numeric($input['fish_betflow_calculate_rate']) && $input['fish_betflow_calculate_rate']>=0 and $input['fish_betflow_calculate_rate']<=100){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','fish_betflow_calculate_rate')->update(['value'=>$input['fish_betflow_calculate_rate']]);
        }

        //棋牌流水计算百分比
        if(isset($input['card_betflow_calculate_rate']) && is_numeric($input['card_betflow_calculate_rate']) && $input['card_betflow_calculate_rate']>=0 and $input['card_betflow_calculate_rate']<=100){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','card_betflow_calculate_rate')->update(['value'=>$input['card_betflow_calculate_rate']]);
        }

        //彩票流水计算百分比
        if(isset($input['lottery_betflow_calculate_rate']) && is_numeric($input['lottery_betflow_calculate_rate']) && $input['lottery_betflow_calculate_rate']>=0 and $input['lottery_betflow_calculate_rate']<=100){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','lottery_betflow_calculate_rate')->update(['value'=>$input['lottery_betflow_calculate_rate']]);
        }

        //体育流水计算百分比
        if(isset($input['sport_betflow_calculate_rate']) && is_numeric($input['sport_betflow_calculate_rate']) && $input['sport_betflow_calculate_rate']>=0 and $input['sport_betflow_calculate_rate']<=100){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','sport_betflow_calculate_rate')->update(['value'=>$input['sport_betflow_calculate_rate']]);
        }

        //开启发放体验券
        if(isset($input['enable_send_voucher']) && is_numeric($input['enable_send_voucher']) && in_array($input['enable_send_voucher'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_send_voucher')->update(['value'=>$input['enable_send_voucher']]);
        }

        //充值发放体验券金额
        if(isset($input['voucher_money']) && is_numeric($input['voucher_money']) && $input['voucher_money']>0 && intval($input['voucher_money']) == $input['voucher_money']){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','voucher_money')->update(['value'=>$input['voucher_money']]);
        }

        //充值发放体验券流水限制倍数
        if(isset($input['voucher_betflow_multiple']) && is_numeric($input['voucher_betflow_multiple']) && $input['voucher_betflow_multiple']>0 && intval($input['voucher_betflow_multiple']) == $input['voucher_betflow_multiple']){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','voucher_betflow_multiple')->update(['value'=>$input['voucher_betflow_multiple']]);
        }

        //新增1个充值发放X张体验券
        if(isset($input['voucher_recharge_amount']) && is_numeric($input['voucher_recharge_amount']) && $input['voucher_recharge_amount']>0 && intval($input['voucher_recharge_amount']) == $input['voucher_recharge_amount']){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','voucher_recharge_amount')->update(['value'=>$input['voucher_recharge_amount']]);
        }

        //充值发放体验券有效天数
        if(isset($input['voucher_valid_day']) && is_numeric($input['voucher_valid_day']) && $input['voucher_valid_day']>0 && intval($input['voucher_valid_day']) == $input['voucher_valid_day']){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','voucher_valid_day')->update(['value'=>$input['voucher_valid_day']]);
        }

        //分红结算周期2=一周，3=3天，4=1天,5=半月,1=5天
        if(isset($input['player_dividends_day']) && in_array($input['player_dividends_day'],[1,2,3,4,5])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','player_dividends_day')->update(['value'=>$input['player_dividends_day']]);
        }

        //分红结算方式
        if(isset($input['player_dividends_method']) && in_array($input['player_dividends_method'],[1,2,3,4,5])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','player_dividends_method')->update(['value'=>$input['player_dividends_method']]);
        }

        if(isset($input['player_dividends_start_day']) && strtotime($input['player_dividends_start_day'])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','player_dividends_start_day')->update(['value'=>$input['player_dividends_start_day']]);
        }

        //网站公告
        if(isset($input['carrier_marquee_notice']) && !empty($input['carrier_marquee_notice'])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','carrier_marquee_notice')->update(['value'=>$input['carrier_marquee_notice']]);
        }

        //是否独立后台
        if(isset($input['agent_single_background']) && in_array($input['agent_single_background'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','agent_single_background')->update(['value'=>$input['agent_single_background']]);
        }

        //允许注册总代
        if(isset($input['is_allow_general_agent']) && in_array($input['is_allow_general_agent'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','is_allow_general_agent')->update(['value'=>$input['is_allow_general_agent']]);
        }

        //注册是否开启体验券
        if(isset($input['enable_register_gift_code']) && in_array($input['enable_register_gift_code'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_register_gift_code')->update(['value'=>$input['enable_register_gift_code']]);
        }

        //是否开启充值
        if(isset($input['enable_recharge']) && in_array($input['enable_recharge'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_recharge')->update(['value'=>$input['enable_recharge']]);
        }

        //是否允许注册
        if(isset($input['is_allow_player_register']) && in_array($input['is_allow_player_register'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','is_allow_player_register')->update(['value'=>$input['is_allow_player_register']]);
        }

        //是否维护
        if(isset($input['is_maintain']) && in_array($input['is_maintain'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','is_maintain')->update(['value'=>$input['is_maintain']]);
        }

        //是否开启注册即送
        if(isset($input['is_registergift']) && in_array($input['is_registergift'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','is_registergift')->update(['value'=>$input['is_registergift']]);
        }

        //是否绑定银行卡或三方钱包
        if(isset($input['is_bindbankcardorthirdwallet']) && in_array($input['is_bindbankcardorthirdwallet'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','is_bindbankcardorthirdwallet')->update(['value'=>$input['is_bindbankcardorthirdwallet']]);
        }

        //流水倍数
        if(isset($input['giftmultiple']) && is_numeric($input['giftmultiple']) && intval($input['giftmultiple']) == $input['giftmultiple'] && $input['giftmultiple']>=1){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','giftmultiple')->update(['value'=>$input['giftmultiple']]);
        }

        //限制人数
        if(isset($input['registergift_limit_day_number']) && is_numeric($input['registergift_limit_day_number']) && $input['registergift_limit_day_number'] >= 1 && intval($input['registergift_limit_day_number']) == $input['registergift_limit_day_number']){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','registergift_limit_day_number')->update(['value'=>$input['registergift_limit_day_number']]);
        }

        //限制周期
        if(isset($input['registergift_limit_cycle']) && is_numeric($input['registergift_limit_cycle']) && in_array($input['registergift_limit_cycle'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','registergift_limit_cycle')->update(['value'=>$input['registergift_limit_cycle']]);
        }

        //实时分红计算的超始日
        if(isset($input['player_realtime_dividends_start_day']) && strtotime($input['player_realtime_dividends_start_day'])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','player_realtime_dividends_start_day')->update(['value'=>$input['player_realtime_dividends_start_day']]);
        }

        //是否显示合营计划
        if(isset($input['is_show_joint_venture']) && in_array($input['is_show_joint_venture'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','is_show_joint_venture')->update(['value'=>$input['is_show_joint_venture']]);
        }

        //会员是否启用流水折算
        if(isset($input['is_bet_flow_convert']) && in_array($input['is_bet_flow_convert'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','is_bet_flow_convert')->update(['value'=>$input['is_bet_flow_convert']]);
        }

        //返水方式(1=自助领取，0=系统发放)
        if(isset($input['rebate_method']) && in_array($input['rebate_method'],[1,0])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','rebate_method')->update(['value'=>$input['rebate_method']]);
        }


        //系统最小提款金额
        if(isset($input['finance_min_withdraw']) && is_numeric($input['finance_min_withdraw']) && $input['finance_min_withdraw'] >0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','finance_min_withdraw')->update(['value'=>$input['finance_min_withdraw']]);
        }

        //USDT最小提币金额
        if(isset($input['min_withdrawal_usdt']) && is_numeric($input['min_withdrawal_usdt']) && $input['min_withdrawal_usdt'] > 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','min_withdrawal_usdt')->update(['value'=>$input['min_withdrawal_usdt']]);
        }

        //显示前台兑换
        if(isset($input['is_show_front_exchange']) && in_array($input['is_show_front_exchange'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','is_show_front_exchange')->update(['value'=>$input['is_show_front_exchange']]);
        }

        //开启人头费
        if(isset($input['enable_capitation_fee']) && in_array($input['enable_capitation_fee'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_capitation_fee')->update(['value'=>$input['enable_capitation_fee']]);
        }

        //包括负盈利代理(0=否,1=是)
        if(isset($input['capitation_fee_rule']) && in_array($input['capitation_fee_rule'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','capitation_fee_rule')->update(['value'=>$input['capitation_fee_rule']]);
        }

        //人头费是否需要审核
        if(isset($input['capitation_fee_type']) && in_array($input['capitation_fee_type'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','capitation_fee_type')->update(['value'=>$input['capitation_fee_type']]);
        }

        //人头费要求存款金额
        if(isset($input['capitation_fee_recharge_amount']) && is_numeric($input['capitation_fee_recharge_amount']) && $input['capitation_fee_recharge_amount'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','capitation_fee_recharge_amount')->update(['value'=>$input['capitation_fee_recharge_amount']]);
        }

        //人头费要求有效流水
        if(isset($input['capitation_fee_bet_flow']) && is_numeric($input['capitation_fee_bet_flow']) && $input['capitation_fee_bet_flow'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','capitation_fee_bet_flow')->update(['value'=>$input['capitation_fee_bet_flow']]);
        }

        //人头费累积充值天数
        if(isset($input['capitation_fee_deposit_days']) && is_numeric($input['capitation_fee_deposit_days']) && $input['capitation_fee_deposit_days'] >= 1 && intval($input['capitation_fee_deposit_days']) == $input['capitation_fee_deposit_days']){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','capitation_fee_deposit_days')->update(['value'=>$input['capitation_fee_deposit_days']]);
        }

        //人头费奖励金额
        if(isset($input['capitation_fee_gift_amount']) && is_numeric($input['capitation_fee_gift_amount']) && $input['capitation_fee_gift_amount'] >= 0 ){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','capitation_fee_gift_amount')->update(['value'=>$input['capitation_fee_gift_amount']]);
            CarrierCapitationFeeSetting::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->update(['amount'=>$input['capitation_fee_gift_amount']]);
        }

        //代理后台跑马灯
        if(array_key_exists('carrier_agent_marquee_notice', $input)){
            if(is_null($input['carrier_agent_marquee_notice'])){
                $input['carrier_agent_marquee_notice'] = '';
            }
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','carrier_agent_marquee_notice')->update(['value'=>$input['carrier_agent_marquee_notice']]);
        }

        //是否启用注册图形验证码
        if(isset($input['enable_register_img_verification']) && in_array($input['enable_register_img_verification'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_register_img_verification')->update(['value'=>$input['enable_register_img_verification']]);
        }

        //是否启用登录图形验证码
        if(isset($input['enable_login_img_verification']) && in_array($input['enable_login_img_verification'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_login_img_verification')->update(['value'=>$input['enable_login_img_verification']]);
        }

        //站点类型(1=长期，0=短期)
        if(isset($input['prefix_type']) && in_array($input['prefix_type'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','prefix_type')->update(['value'=>$input['prefix_type']]);
        }

        //有效活跃定义:周期累积金额
        if(isset($input['player_cycle_deposit_amount']) && is_numeric($input['player_cycle_deposit_amount']) && $input['player_cycle_deposit_amount'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','player_cycle_deposit_amount')->update(['value'=>$input['player_cycle_deposit_amount']]);
        }

        //有效活跃定义:周期续存金额
        if(isset($input['player_cycle_continue_deposit']) && is_numeric($input['player_cycle_continue_deposit']) && $input['player_cycle_continue_deposit'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','player_cycle_continue_deposit')->update(['value'=>$input['player_cycle_continue_deposit']]);
        }

        //有效活跃定义:有效流水
        if(isset($input['player_cycle_betflow']) && is_numeric($input['player_cycle_betflow']) && $input['player_cycle_betflow'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','player_cycle_betflow')->update(['value'=>$input['player_cycle_betflow']]);
        }

        //专享佣金:比例
        if(isset($input['prefix_exclusive_rate']) && is_numeric($input['prefix_exclusive_rate']) && $input['prefix_exclusive_rate'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','prefix_exclusive_rate')->update(['value'=>$input['prefix_exclusive_rate']]);
        }

        //专享佣金:要求活跃数
        if(isset($input['prefix_exclusive_active']) && is_numeric($input['prefix_exclusive_active']) && $input['prefix_exclusive_active'] == intval($input['prefix_exclusive_active']) && $input['prefix_exclusive_active'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','prefix_exclusive_active')->update(['value'=>$input['prefix_exclusive_active']]);
        }

        //专享佣金:未达标递减比例
        if(isset($input['prefix_decreasing_rate']) && is_numeric($input['prefix_decreasing_rate']) && $input['prefix_decreasing_rate'] == intval($input['prefix_decreasing_rate']) && $input['prefix_decreasing_rate'] >=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','prefix_decreasing_rate')->update(['value'=>$input['prefix_decreasing_rate']]);
        }

        //提现手续费
        if(isset($input['withdraw_ratefee']) &&  is_numeric($input['withdraw_ratefee']) && intval($input['withdraw_ratefee'])== $input['withdraw_ratefee'] && $input['withdraw_ratefee']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','withdraw_ratefee')->update(['value'=>$input['withdraw_ratefee']]);
        }

        //支付宝提现手续费
        if(isset($input['alipay_withdraw_ratefee']) &&  is_numeric($input['alipay_withdraw_ratefee']) && intval($input['alipay_withdraw_ratefee'])== $input['alipay_withdraw_ratefee'] && $input['alipay_withdraw_ratefee']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','alipay_withdraw_ratefee')->update(['value'=>$input['alipay_withdraw_ratefee']]);
        }

        //领取体验券未充值是否入库
        if(isset($input['enable_coupons_bank_store']) &&  in_array($input['enable_coupons_bank_store'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_coupons_bank_store')->update(['value'=>$input['enable_coupons_bank_store']]);
        }

        //代理佣金流水单独结算
        if(isset($input['enabel_agent_commissionflow_single']) && in_array($input['enabel_agent_commissionflow_single'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enabel_agent_commissionflow_single')->update(['value'=>$input['enabel_agent_commissionflow_single']]);
        }

        //允放站内转帐
        if(isset($input['site_transfer_method']) && in_array($input['site_transfer_method'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','site_transfer_method')->update(['value'=>$input['site_transfer_method']]);
        }

        //启用保底通宝模式
        if(isset($input['enable_tongbao_method']) && in_array($input['enable_tongbao_method'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_tongbao_method')->update(['value'=>$input['enable_tongbao_method']]);
        }

        //保底通宝分红比例
        if(isset($input['tongbao_rate']) &&  is_numeric($input['tongbao_rate']) &&  $input['tongbao_rate']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','tongbao_rate')->update(['value'=>$input['tongbao_rate']]);
        }

        //启用分红通宝模式
        if(isset($input['enable_dividends_tongbao_method']) && in_array($input['enable_dividends_tongbao_method'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_dividends_tongbao_method')->update(['value'=>$input['enable_dividends_tongbao_method']]);
        }

        //保底分红通宝分红比例
        if(isset($input['tongbao_dividends_rate']) &&  is_numeric($input['tongbao_dividends_rate']) &&  $input['tongbao_dividends_rate']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','tongbao_dividends_rate')->update(['value'=>$input['tongbao_dividends_rate']]);
        }

        //彩金费率
        if(isset($input['bonus_rate']) &&  is_numeric($input['bonus_rate']) &&  $input['bonus_rate']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','bonus_rate')->update(['value'=>$input['bonus_rate']]);
        }

        //显示热门游戏数量
        if(isset($input['show_hot_game_number']) &&  is_numeric($input['show_hot_game_number']) && intval($input['show_hot_game_number']) == $input['show_hot_game_number'] &&  $input['show_hot_game_number']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','show_hot_game_number')->update(['value'=>$input['show_hot_game_number']]);
        }

        //是否仅亏损计入流水
        if(isset($input['is_loss_write_betflow']) &&  in_array($input['is_loss_write_betflow'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','is_loss_write_betflow')->update(['value'=>$input['is_loss_write_betflow']]);
        }

        //是否开启快杀
        if(isset($input['enable_fast_kill']) &&  in_array($input['enable_fast_kill'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_fast_kill')->update(['value'=>$input['enable_fast_kill']]);
        }

        //刷水游戏列表逗号分隔
        if(isset($input['arbitrage_game_list']) &&  !empty($input['arbitrage_game_list'])){
            $gameCodeArr = explode(',',$input['arbitrage_game_list']);
            $gameCount   = Game::whereIn('game_id',$gameCodeArr)->count();
            if($gameCount != count($gameCodeArr)){
                return returnApiJson('对不起,游戏ID取值不正确',0);
            }

            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','arbitrage_game_list')->update(['value'=>$input['arbitrage_game_list']]);
        }

        //刷水游戏代理流水折算
        if(isset($input['arbitrage_game_flow_convert']) && is_numeric($input['arbitrage_game_flow_convert']) && $input['arbitrage_game_flow_convert'] >= 0 ){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','arbitrage_game_flow_convert')->update(['value'=>$input['arbitrage_game_flow_convert']]);
        }

        //玩家ID长度(5到7位)
        if(isset($input['id_length']) && is_numeric($input['id_length']) && $input['id_length'] >= 5 && $input['id_length'] <= 7){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','id_length')->update(['value'=>$input['id_length']]);
        }

        //人头费首存金额是否记入充值
        if(isset($input['is_capitation_first_deposit_calculate']) &&  in_array($input['is_capitation_first_deposit_calculate'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','is_capitation_first_deposit_calculate')->update(['value'=>$input['is_capitation_first_deposit_calculate']]);
        }

        //人头费首存金额不计入活动ID
        if(isset($input['capitation_first_deposit_calculate_activityid']) &&  is_numeric($input['capitation_first_deposit_calculate_activityid']) &&  $input['capitation_first_deposit_calculate_activityid']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','capitation_first_deposit_calculate_activityid')->update(['value'=>$input['capitation_first_deposit_calculate_activityid']]);
        }

        //注册送活动ID
        if(array_key_exists('register_receive_activityid',$input)){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','register_receive_activityid')->update(['value'=>$input['register_receive_activityid']]);
        }

        //代理体育保底比例
        if(isset($input['agent_sport_betflow_calculate_rate']) &&  is_numeric($input['agent_sport_betflow_calculate_rate']) &&  $input['agent_sport_betflow_calculate_rate']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','agent_sport_betflow_calculate_rate')->update(['value'=>$input['agent_sport_betflow_calculate_rate']]);
        }

        //代理彩票保底比例
        if(isset($input['agent_lottery_betflow_calculate_rate']) &&  is_numeric($input['agent_lottery_betflow_calculate_rate']) &&  $input['agent_lottery_betflow_calculate_rate']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','agent_lottery_betflow_calculate_rate')->update(['value'=>$input['agent_lottery_betflow_calculate_rate']]);
        }

        //代理棋牌保底比例
        if(isset($input['agent_card_betflow_calculate_rate']) &&  is_numeric($input['agent_card_betflow_calculate_rate']) &&  $input['agent_card_betflow_calculate_rate']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','agent_card_betflow_calculate_rate')->update(['value'=>$input['agent_card_betflow_calculate_rate']]);
        }

        //代理捕鱼保底比例
        if(isset($input['agent_fish_betflow_calculate_rate']) &&  is_numeric($input['agent_fish_betflow_calculate_rate']) &&  $input['agent_fish_betflow_calculate_rate']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','agent_fish_betflow_calculate_rate')->update(['value'=>$input['agent_fish_betflow_calculate_rate']]);
        }

        //代理电竞保底比例
        if(isset($input['agent_esport_betflow_calculate_rate']) &&  is_numeric($input['agent_esport_betflow_calculate_rate']) &&  $input['agent_esport_betflow_calculate_rate']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','agent_esport_betflow_calculate_rate')->update(['value'=>$input['agent_esport_betflow_calculate_rate']]);
        }

        //代理电子保底比例
        if(isset($input['agent_electronic_betflow_calculate_rate']) &&  is_numeric($input['agent_electronic_betflow_calculate_rate']) &&  $input['agent_electronic_betflow_calculate_rate']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','agent_electronic_betflow_calculate_rate')->update(['value'=>$input['agent_electronic_betflow_calculate_rate']]);
        }

        //代理真人保底比例
        if(isset($input['agent_casino_betflow_calculate_rate']) &&  is_numeric($input['agent_casino_betflow_calculate_rate']) &&  $input['agent_casino_betflow_calculate_rate']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','agent_casino_betflow_calculate_rate')->update(['value'=>$input['agent_casino_betflow_calculate_rate']]);
        }

        //是否开启清亏损数据
        if(isset($input['enable_clean_loss']) &&  in_array($input['enable_clean_loss'],[0,1])){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_clean_loss')->update(['value'=>$input['enable_clean_loss']]);
        }

        //是否开启清亏损数据
        if(isset($input['clean_loss_amount_cycle']) &&  is_numeric($input['clean_loss_amount_cycle']) &&  $input['clean_loss_amount_cycle']>=0 && intval($input['clean_loss_amount_cycle']) == $input['clean_loss_amount_cycle']){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','clean_loss_amount_cycle')->update(['value'=>$input['clean_loss_amount_cycle']]);
        }

        //清亏损负金额
        if(isset($input['clean_loss_amount']) &&  is_numeric($input['clean_loss_amount']) &&  $input['clean_loss_amount']>=0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','clean_loss_amount')->update(['value'=>$input['clean_loss_amount']]);
        }

        //开启投注返水
        if(isset($input['enable_bet_gradient_rebate']) && in_array($input['enable_bet_gradient_rebate'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_bet_gradient_rebate')->update(['value'=>$input['enable_bet_gradient_rebate']]);
        }

        //视讯投注梯度返水
        if(isset($input['video_bet_gradient_rebate']) && is_array($input['video_bet_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','video_bet_gradient_rebate')->update(['value'=>json_encode($input['video_bet_gradient_rebate'])]);
        }

        //电子投注梯度返水
        if(isset($input['ele_bet_gradient_rebate']) && is_array($input['ele_bet_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','ele_bet_gradient_rebate')->update(['value'=>json_encode($input['ele_bet_gradient_rebate'])]);
        }

        //电竞投注梯度返水
        if(isset($input['esport_bet_gradient_rebate']) && is_array($input['esport_bet_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','esport_bet_gradient_rebate')->update(['value'=>json_encode($input['esport_bet_gradient_rebate'])]);
        }

        //棋牌投注梯度返水
        if(isset($input['card_bet_gradient_rebate']) && is_array($input['card_bet_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','card_bet_gradient_rebate')->update(['value'=>json_encode($input['card_bet_gradient_rebate'])]);
        }

        //体育投注梯度返水
        if(isset($input['sport_bet_gradient_rebate']) && is_array($input['sport_bet_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','sport_bet_gradient_rebate')->update(['value'=>json_encode($input['sport_bet_gradient_rebate'])]);
        }

        //捕鱼投注梯度返水
        if(isset($input['fish_bet_gradient_rebate']) && is_array($input['fish_bet_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','fish_bet_gradient_rebate')->update(['value'=>json_encode($input['fish_bet_gradient_rebate'])]);
        }

        //启用保险箱
        if(isset($input['enable_safe_box']) && in_array($input['enable_safe_box'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_safe_box')->update(['value'=>$input['enable_safe_box']]);
        }

        //人头费计算周期(1=同分红周期,2=永久)
        if(isset($input['capitation_fee_cycle']) && in_array($input['capitation_fee_cycle'],[1,2])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','capitation_fee_cycle')->update(['value'=>$input['capitation_fee_cycle']]);
        }

        //彩票投注梯度返水
        if(isset($input['lott_bet_gradient_rebate']) && is_array($input['lott_bet_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','lott_bet_gradient_rebate')->update(['value'=>json_encode($input['lott_bet_gradient_rebate'])]);
        }

        //开启直属投注梯度返佣
        if(isset($input['enable_invite_gradient_rebate']) && in_array($input['enable_invite_gradient_rebate'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_invite_gradient_rebate')->update(['value'=>$input['enable_invite_gradient_rebate']]);
        }

        //直属视讯投注梯度返佣
        if(isset($input['video_invite_gradient_rebate']) && is_array($input['video_invite_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','video_invite_gradient_rebate')->update(['value'=>json_encode($input['video_invite_gradient_rebate'])]);
        }

        //直属电子投注梯度返佣
        if(isset($input['ele_invite_gradient_rebate']) && is_array($input['ele_invite_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','ele_invite_gradient_rebate')->update(['value'=>json_encode($input['ele_invite_gradient_rebate'])]);
        }

        //直属电竞投注梯度返佣
        if(isset($input['esport_invite_gradient_rebate']) && is_array($input['esport_invite_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','esport_invite_gradient_rebate')->update(['value'=>json_encode($input['esport_invite_gradient_rebate'])]);
        }

        //直属棋牌投注梯度返佣
        if(isset($input['card_invite_gradient_rebate']) && is_array($input['card_invite_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','card_invite_gradient_rebate')->update(['value'=>json_encode($input['card_invite_gradient_rebate'])]);
        }

        //直属体育投注梯度返佣
        if(isset($input['sport_invite_gradient_rebate']) && is_array($input['sport_invite_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','sport_invite_gradient_rebate')->update(['value'=>json_encode($input['sport_invite_gradient_rebate'])]);
        }

        //直属捕鱼投注梯度返佣
        if(isset($input['fish_invite_gradient_rebate']) && is_array($input['fish_invite_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','fish_invite_gradient_rebate')->update(['value'=>json_encode($input['fish_invite_gradient_rebate'])]);
        }

        //直属彩票投注梯度返佣
        if(isset($input['lott_invite_gradient_rebate']) && is_array($input['lott_invite_gradient_rebate'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','lott_invite_gradient_rebate')->update(['value'=>json_encode($input['lott_invite_gradient_rebate'])]);
        }

        //注册赚送金额概率梯度
        if(isset($input['register_probability']) && is_array($input['register_probability'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','register_probability')->update(['value'=>json_encode($input['register_probability'])]);
        }

        //取款需要手机验证码
        if(isset($input['withdrawal_need_sms']) && in_array($input['withdrawal_need_sms'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','withdrawal_need_sms')->update(['value'=>$input['withdrawal_need_sms']]);
        }

        //体验券有效充值金额
        if(isset($input['register_gift_code_amount']) && is_numeric($input['register_gift_code_amount']) &&  $input['register_gift_code_amount'] >= 0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','register_gift_code_amount')->update(['value'=>$input['register_gift_code_amount']]);
        }

        //开启亏损代理进入游戏限制
        if(isset($input['enable_agent_game_limit']) && in_array($input['enable_agent_game_limit'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_agent_game_limit')->update(['value'=>$input['enable_agent_game_limit']]);
        }

        //站点上线时间
        if(isset($input['site_online_time']) && strtotime($input['site_online_time'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','site_online_time')->update(['value'=>$input['site_online_time']]);
        }

        //首存1加1活动ID
        if(array_key_exists('first_deposit_activity_plus',$input)) {
            if(is_null($input['first_deposit_activity_plus'])){
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','first_deposit_activity_plus')->update(['value'=>'']);
            } else{
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','first_deposit_activity_plus')->update(['value'=>$input['first_deposit_activity_plus']]);
            }
        }

        //批量注册自动冻结
        if(isset($input['enable_batch_register_froze']) && in_array($input['enable_batch_register_froze'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_batch_register_froze')->update(['value'=>$input['enable_batch_register_froze']]);
        }

        //批量注册同IP注册个数
        if(isset($input['batch_register_ip_number']) && is_numeric($input['batch_register_ip_number']) && $input['batch_register_ip_number'] >= 3) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','batch_register_ip_number')->update(['value'=>$input['batch_register_ip_number']]);
        }

        //提现号ID(多个逗号间隔)
        if(array_key_exists('fake_withdraw_player_ids',$input)) {
            if(is_null($input['fake_withdraw_player_ids']) || empty($input['fake_withdraw_player_ids'])){
                CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','fake_withdraw_player_ids')->update(['value'=>'']);
            } else{
                $fakeWithdrawPlayerIds = explode(',',$input['fake_withdraw_player_ids']);
                $playerCount           = Player::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->whereIn('player_id',$fakeWithdrawPlayerIds)->count();
                if($playerCount== count($fakeWithdrawPlayerIds)){
                    CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','fake_withdraw_player_ids')->update(['value'=>$input['fake_withdraw_player_ids']]);
                }
            }
        }

        //提现号限制金额
        if(isset($input['fake_withdraw_limit']) && is_numeric($input['fake_withdraw_limit']) && $input['fake_withdraw_limit'] >= 100) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','fake_withdraw_limit')->update(['value'=>$input['fake_withdraw_limit']]);
        }

        //启用固定分红
        if(isset($input['enable_fixed_earnings']) && in_array($input['enable_fixed_earnings'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_fixed_earnings')->update(['value'=>$input['enable_fixed_earnings']]);
        }

        //启用固定保底
        if(isset($input['enable_fixed_guaranteed']) && in_array($input['enable_fixed_guaranteed'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_fixed_guaranteed')->update(['value'=>$input['enable_fixed_guaranteed']]);
        }

        //固定分红
        if(isset($input['default_earnings']) && is_numeric($input['default_earnings']) && $input['default_earnings'] >= 0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','default_earnings')->update(['value'=>$input['default_earnings']]);
            if(isset($input['enable_fixed_earnings']) && $input['enable_fixed_earnings']==1){
                PlayerSetting::where('user_name','like','%_'.$input['prefix'])->where('player_id','!=',$defaultAgent->player_id)->update(['earnings'=>$input['default_earnings']]);
                PlayerInviteCode::where('prefix',$input['prefix'])->where('player_id','!=',$defaultAgent->player_id)->update(['earnings'=>$input['default_earnings']]);
            }
        }

        //固定保底
        if(isset($input['default_guaranteed']) && is_numeric($input['default_guaranteed']) && $input['default_guaranteed'] >= 0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','default_guaranteed')->update(['value'=>$input['default_guaranteed']]);
            if(isset($input['enable_fixed_guaranteed']) && $input['enable_fixed_guaranteed']==1){
                PlayerSetting::where('user_name','like','%_'.$input['prefix'])->where('player_id','!=',$defaultAgent->player_id)->update(['guaranteed'=>$input['default_guaranteed']]);
            }
            
        }

        //分红领取方式
        if(isset($input['dividends_receive_method']) && in_array($input['dividends_receive_method'],[1,2])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','dividends_receive_method')->update(['value'=>$input['dividends_receive_method']]);
        }

        //非钱包通道费代理扣费
        if(isset($input['no_wallet_passage_rate']) && is_numeric($input['no_wallet_passage_rate']) && $input['no_wallet_passage_rate']>=0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','no_wallet_passage_rate')->update(['value'=>$input['no_wallet_passage_rate']]);
        }

        //钱包通道费代理扣费
        if(isset($input['wallet_passage_rate']) && is_numeric($input['wallet_passage_rate']) && $input['wallet_passage_rate'] >= 0){
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','wallet_passage_rate')->update(['value'=>$input['wallet_passage_rate']]);
        }

        //人头费单笔充值金额
        if(isset($input['capitation_fee_single_recharge_amount']) && is_numeric($input['capitation_fee_single_recharge_amount']) && $input['capitation_fee_single_recharge_amount']>=0) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','capitation_fee_single_recharge_amount')->update(['value'=>$input['capitation_fee_single_recharge_amount']]);
        }

        //联系方式数组
        if(isset($input['marketing_contact']) && is_array($input['marketing_contact'])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','marketing_contact')->update(['value'=>json_encode($input['marketing_contact'])]);
        }
        
        //单IP注册数
        if(isset($input['player_max_register_one_ip_minute']) && is_numeric($input['player_max_register_one_ip_minute']) && $input['player_max_register_one_ip_minute'] >= 0 && intval($input['player_max_register_one_ip_minute'])== $input['player_max_register_one_ip_minute']) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','player_max_register_one_ip_minute')->update(['value'=>$input['player_max_register_one_ip_minute']]);
        }

        //注册/登录IP黑名单
        if(array_key_exists('ip_blacklist',$input)) {
            if(is_null($input['ip_blacklist'])){
                $input['ip_blacklist'] ='';
            }
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','ip_blacklist')->update(['value'=>$input['ip_blacklist']]);
        }
        
        //注册启用形为验证码
        if(isset($input['enable_register_behavior_verification']) && in_array($input['enable_register_behavior_verification'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_register_behavior_verification')->update(['value'=>$input['enable_register_behavior_verification']]);
        }

        //登录启用形为验证码
        if(isset($input['enable_login_behavior_verification']) && in_array($input['enable_login_behavior_verification'],[0,1])) {
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','enable_login_behavior_verification')->update(['value'=>$input['enable_login_behavior_verification']]);
        }

        //不进假PG名单(逗号分隔)
        if(array_key_exists('no_fake_pg_playerids',$input)){
            if(is_null($input['no_fake_pg_playerids'])){
                $input['no_fake_pg_playerids'] ='';
            }
            CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('sign','no_fake_pg_playerids')->update(['value'=>$input['no_fake_pg_playerids']]);
        }
        
        CarrierCache::flushCarrierMultipleConfigure($this->carrier->id,$input['prefix']);

        //更新点位表
        foreach ($input as $key => $value) {
            if(str_replace('game_plat_id_','',$key) != $key){
                CarrierPreFixGamePlat::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('game_plat_id',str_replace('game_plat_id_','',$key))->update(['point'=>$value]);
            }
        }
        //

        $routeName             = request()->route()->getName();
        $path                  = explode(config('main')['carrier_base_url'],request()->url());
        $carrierAdminLog       = new CarrierAdminLog();

        $permission            = Permission::select('permissions.group_id','permissions.name','permissions.id','permissions.description','a.group_name as sub_group_name','b.group_name')->where('name',$routeName)
                ->leftJoin('permission_group as a','a.id','=','permissions.group_id')
                ->leftJoin('permission_group as b','b.id','=','a.parent_id')
                ->first();

        $carrierAdminLog->action           = $permission->group_name.'|'.$permission->sub_group_name.'|'.$permission->description;
        $carrierAdminLog->group_id         = $permission->group_id;
        $carrierAdminLog->carrieruser_id   = $this->carrierUser->id;
        $carrierAdminLog->carrier_id       = $this->carrier->id;
        $carrierAdminLog->user_name        = $this->carrierUser->username;
        $carrierAdminLog->day              = date('Ymd');
        $carrierAdminLog->routename        = $path[1];
        $carrierAdminLog->permissionsid    = $permission->id;
        $carrierAdminLog->actionTime       = time();
        $carrierAdminLog->actionIP         = ip2long(real_ip());
        $carrierAdminLog->params           = json_encode($input);
        $carrierAdminLog->save();

        return returnApiJson('操作成功',1);
    }

    public function websiteSave()
    {
        $input             = request()->all();
        $data              = [];

        if(isset($input['enable_auto_pay']) && in_array($input['enable_auto_pay'],[0,1]) && $this->carrierUser->is_super_admin){

            $data['enable_auto_pay'] = $input['enable_auto_pay'];
        }

        if(isset($input['auto_pay_single_limit']) && is_numeric($input['auto_pay_single_limit']) && $input['auto_pay_single_limit']>=100 && $this->carrierUser->is_super_admin){
            $data['auto_pay_single_limit'] = $input['auto_pay_single_limit'];
        }

        if(isset($input['auto_pay_day_limit']) && is_numeric($input['auto_pay_day_limit']) && $input['auto_pay_day_limit'] > 100 && $this->carrierUser->is_super_admin){
            $data['auto_pay_day_limit'] = $input['auto_pay_day_limit'];
        }

        if(isset($input['enable_limit_one_withdrawal']) && in_array($input['enable_limit_one_withdrawal'],[0,1])){
            $data['enable_limit_one_withdrawal'] = $input['enable_limit_one_withdrawal'];
        }

        if(isset($input['mostalk']) && !empty($input['mostalk'])){
            $data['mostalk'] = $input['mostalk'];
        }

        if(isset($input['clearbetflowlimitamount']) && is_numeric($input['clearbetflowlimitamount']) && intval($input['clearbetflowlimitamount']) == $input['clearbetflowlimitamount']){
            $data['clearbetflowlimitamount'] = $input['clearbetflowlimitamount'];
        }

        if(isset($input['small_group_withdraw_wallet']) && is_array($input['small_group_withdraw_wallet'])){
            if(count($input['small_group_withdraw_wallet'])){
                $data['small_group_withdraw_wallet'] = json_encode($input['small_group_withdraw_wallet']);
            } else{
                $data['small_group_withdraw_wallet'] = json_encode([]);
            }
        }elseif(isset($input['small_group_withdraw_wallet']) && $input['small_group_withdraw_wallet']=='[]'){
            $data['small_group_withdraw_wallet'] = json_encode([]);
        }

        if(isset($input['default_lottery_odds']) && is_numeric($input['default_lottery_odds']) && $input['default_lottery_odds'] >= 1900 && $input['default_lottery_odds'] <= 2000){
            $data['default_lottery_odds'] = $input['default_lottery_odds'];
            PlayerInviteCode::where('carrier_id',$this->carrier->id)->update(['lottoadds'=>$input['default_lottery_odds']]);
            PlayerSetting::where('carrier_id',$this->carrier->id)->update(['lottoadds'=>$input['default_lottery_odds']]);
        }

        if(isset($input['recharge_rate_activity_rate']) && is_numeric($input['recharge_rate_activity_rate']) && $input['recharge_rate_activity_rate']>0){
            $data['recharge_rate_activity_rate'] = $input['recharge_rate_activity_rate'];
        }
        
        if(isset($input['calculate_returnwater']) && in_array($input['calculate_returnwater'],[0,1])){
            $data['calculate_returnwater'] = $input['calculate_returnwater'];
        }

        if(array_key_exists('disable_phone_number_segment',$input)){
            if(empty($input['disable_phone_number_segment'])){
                $data['disable_phone_number_segment'] = $input['disable_phone_number_segment'];
            } else{
                $data['disable_phone_number_segment'] = $input['disable_phone_number_segment'];
            }
        }

        if(isset($input['unpay_frequency_hidden']) && !empty($input['unpay_frequency_hidden']) && is_numeric($input['unpay_frequency_hidden']) && intval($input['unpay_frequency_hidden']) == $input['unpay_frequency_hidden'] && $input['unpay_frequency_hidden'] > 0) {
            $data['unpay_frequency_hidden'] = $input['unpay_frequency_hidden'];
        }

        if(isset($input['carrier_usdt_gift']) && is_numeric($input['carrier_usdt_gift']) && intval($input['carrier_usdt_gift']) == $input['carrier_usdt_gift'] && $input['carrier_usdt_gift'] >= 0) {
            $data['carrier_usdt_gift'] = $input['carrier_usdt_gift'];
        }

        if(isset($input['delunpaidday']) && is_numeric($input['delunpaidday']) && intval($input['delunpaidday']) == $input['delunpaidday'] && $input['delunpaidday'] >= 0) {
            $data['delunpaidday'] = $input['delunpaidday'];
        }

        if(isset($input['continuous_unpaid_froze']) && is_numeric($input['continuous_unpaid_froze']) && intval($input['continuous_unpaid_froze']) == $input['continuous_unpaid_froze'] && $input['continuous_unpaid_froze'] >= 0) {
            $data['continuous_unpaid_froze'] = $input['continuous_unpaid_froze'];
        }

        if(isset($input['carrier_bank_gift']) && !empty($input['carrier_bank_gift']) && is_numeric($input['carrier_bank_gift']) && intval($input['carrier_bank_gift']) == $input['carrier_bank_gift'] && $input['carrier_bank_gift'] >= 0) {
            $data['carrier_bank_gift'] = $input['carrier_bank_gift'];
        }

        if(isset($input['continuous_unpaid']) && !empty($input['continuous_unpaid']) && is_numeric($input['continuous_unpaid']) && intval($input['continuous_unpaid']) == $input['continuous_unpaid'] && $input['continuous_unpaid']>=0) {
            $continuousUnpaid          = CarrierCache::getCarrierConfigure($this->carrier->id,'continuous_unpaid');
            $data['continuous_unpaid'] = $input['continuous_unpaid'];
            if($continuousUnpaid != $data['continuous_unpaid']){
                $lockTag='orderLock';
                cache()->tags($lockTag)->flush();
            }
        }

        if(isset($input['ban_hour']) && !empty($input['ban_hour']) && is_numeric($input['ban_hour']) && intval($input['ban_hour']) == $input['ban_hour'] && $input['ban_hour']>=0) {
            $banHour          = CarrierCache::getCarrierConfigure($this->carrier->id,'ban_hour');
            $data['ban_hour'] = $input['ban_hour'];
            if($banHour != $input['ban_hour']){
                $lockTag='orderLock';
                cache()->tags($lockTag)->flush();
            }
        }   

        if(isset($input['okpay_down'])) {
            $data['okpay_down'] = $input['okpay_down'];
        }

        if(isset($input['okpay_tutorial'])) {
            $data['okpay_tutorial'] = $input['okpay_tutorial'];
        }

        if(isset($input['topay_down'])) {
            $data['topay_down'] = $input['topay_down'];
        }

        if(isset($input['topay_tutorial'])) {
            $data['topay_tutorial'] = $input['topay_tutorial'];
        }

        if(isset($input['bobipay_down'])) {
            $data['bobipay_down'] = $input['bobipay_down'];
        }

        if(isset($input['bobipay_tutorial'])) {
            $data['bobipay_tutorial'] = $input['bobipay_tutorial'];
        }

        if(isset($input['ebpay_down'])) {
            $data['ebpay_down'] = $input['ebpay_down'];
        }

        if(isset($input['ebpay_tutorial'])) {
            $data['ebpay_tutorial'] = $input['ebpay_tutorial'];
        }

        if(isset($input['gopay_down'])) {
            $data['gopay_down'] = $input['gopay_down'];
        }

        if(isset($input['gopay_tutorial'])) {
            $data['gopay_tutorial'] = $input['gopay_tutorial'];
        }

        if(isset($input['wanb_down'])) {
            $data['wanb_down'] = $input['wanb_down'];
        }

        if(isset($input['wanb_tutorial'])) {
            $data['wanb_tutorial'] = $input['wanb_tutorial'];
        }

        if(isset($input['jdpay_down'])) {
            $data['jdpay_down'] = $input['jdpay_down'];
        }

        if(isset($input['jdpay_tutorial'])) {
            $data['jdpay_tutorial'] = $input['jdpay_tutorial'];
        }

        if(isset($input['kdpay_down'])) {
            $data['kdpay_down'] = $input['kdpay_down'];
        }

        if(isset($input['kdpay_tutorial'])) {
            $data['kdpay_tutorial'] = $input['kdpay_tutorial'];
        }

        if(isset($input['nopay_down'])) {
            $data['nopay_down'] = $input['nopay_down'];
        }

        if(isset($input['nopay_tutorial'])) {
            $data['nopay_tutorial'] = $input['nopay_tutorial'];
        }

        if(isset($input['casino_venue_rate']) && is_numeric($input['casino_venue_rate']) && $input['casino_venue_rate'] >= 0 ){
            $data['casino_venue_rate'] = $input['casino_venue_rate'];
        }

        if(isset($input['electronic_venue_rate']) && is_numeric($input['electronic_venue_rate']) && $input['electronic_venue_rate'] >= 0 ){
            $data['electronic_venue_rate'] = $input['electronic_venue_rate'];
        }

        if(isset($input['esport_venue_rate']) && is_numeric($input['esport_venue_rate']) && $input['esport_venue_rate'] >= 0 ){
            $data['esport_venue_rate'] = $input['esport_venue_rate'];
        }

        if(isset($input['fish_venue_rate']) && is_numeric($input['fish_venue_rate']) && $input['fish_venue_rate'] >= 0 ){
            $data['fish_venue_rate'] = $input['fish_venue_rate'];
        }

        if(isset($input['card_venue_rate']) && is_numeric($input['card_venue_rate']) && $input['card_venue_rate'] >= 0 ){
            $data['card_venue_rate'] = $input['card_venue_rate'];
        }

        if(isset($input['lottery_venue_rate']) && is_numeric($input['lottery_venue_rate']) && $input['lottery_venue_rate'] >= 0 ){
            $data['lottery_venue_rate'] = $input['lottery_venue_rate'];
        }

        if(isset($input['sport_venue_rate']) && is_numeric($input['sport_venue_rate']) && $input['sport_venue_rate'] >= 0 ){
            $data['sport_venue_rate'] = $input['sport_venue_rate'];
        }

        if(isset($input['agent_transfer_gradient']) && is_array($input['agent_transfer_gradient'])) {

            $flag = [];
            $agentTransferGradient = $input['agent_transfer_gradient'];
            foreach ($agentTransferGradient as $key => $value) {
                $flag[] = $value['agentnumber']; 
            }
            array_multisort($flag, SORT_ASC, $agentTransferGradient);

            $data['agent_transfer_gradient'] = json_encode($agentTransferGradient);
        }

        if(isset($input['enable_agent_wallet']) && in_array($input['enable_agent_wallet'],[0,1])) {
            $data['enable_agent_wallet'] = $input['enable_agent_wallet'];
        }

        if(isset($input['admin_white_ip_list']) && !empty($input['admin_white_ip_list']))
        {
            $str = '';
            $ips = explode(',',$input['admin_white_ip_list']);

            foreach ($ips as $key => $value) {
                if(filter_var($value, FILTER_VALIDATE_IP)){
                    $str .= $value.',';
                }
            }

            $str = rtrim($str,',');
            $data['admin_white_ip_list'] = $str;
        } else if(isset($input['admin_white_ip_list']) && !empty($input['admin_white_ip_list'])) {
            $data['admin_white_ip_list'] = '';
        }

        if(isset($input['supportMemberLangMap'])) {
            $data['supportMemberLangMap'] = $input['supportMemberLangMap'];
            $languageArrs = explode(',',$input['supportMemberLangMap']);

            $insertArticle  = [];

            $webname = CarrierCache::getCarrierConfigure($this->carrier->id,'site_title');
            foreach ($languageArrs as $key => $value) {
                if($value=='zh-cn'){
                    $currlanguage = 'zh';
                } else {
                    $currlanguage = $value;
                }
            }
        } 

        if(isset($input['default_language_code']) && !empty($input['default_language_code'])){
            $data['default_language_code'] = $input['default_language_code'];
        }

        if(isset($input['effective_member_depositamount']) && is_numeric($input['effective_member_depositamount']) && $input['effective_member_depositamount']>=0) {
            $data['effective_member_depositamount'] = $input['effective_member_depositamount'];
        }

        if(isset($input['effective_member_availablebet']) && is_numeric($input['effective_member_availablebet']) && $input['effective_member_availablebet']>=0) {
            $data['effective_member_availablebet'] = $input['effective_member_availablebet'];
        }

        if(isset($input['withdraw_first_audit']) && !empty($input['withdraw_first_audit'])) {
            $carrierServiceTeam               = CarrierServiceTeam::where('id',$input['withdraw_first_audit'])->first();
            if($carrierServiceTeam){
                $data['withdraw_first_audit'] = $input['withdraw_first_audit'];
            }
        }

        if(isset($input['withdraw_second_audit']) && !empty($input['withdraw_second_audit'])) {
            $carrierServiceTeam                     = CarrierServiceTeam::where('id',$input['withdraw_second_audit'])->first();
            if($carrierServiceTeam){
                $data['withdraw_second_audit']      = $input['withdraw_second_audit'];
            }
        }

        if(isset($input['skype'])) {
            $data['skype'] = $input['skype'];
        }

        if(isset($input['telegram'])) {
            $data['telegram'] = $input['telegram'];
        }

        if(isset($input['email'])) {
            $data['email'] = $input['email'];
        }

        if(isset($input['digital_finance_min_recharge']) && is_numeric($input['digital_finance_min_recharge']) && $input['digital_finance_min_recharge']>=0 ){
            $data['digital_finance_min_recharge'] = $input['digital_finance_min_recharge'];
        }

        if(isset($input['digital_finance_max_recharge']) && is_numeric($input['digital_finance_max_recharge']) && $input['digital_finance_max_recharge']>=$input['digital_finance_min_recharge'] ){
            $data['digital_finance_max_recharge'] = $input['digital_finance_max_recharge'];
        }

        if(isset($input['carrier_register_telehone']) && in_array($input['carrier_register_telehone'], [0,1])) {
            $data['carrier_register_telehone'] = $input['carrier_register_telehone'];
        }

        $defaultUserName = CarrierWebSite::where('carrier_id',$this->carrier->id)->where('sign','default_user_name')->first();

        foreach ($data as $key => $value) {
            $carrierWebSite = CarrierWebSite::where('carrier_id',$this->carrier->id)->where('sign',$key)->first();
            if(!$carrierWebSite){
                return returnApiJson('对不起，此变量不存在', 0);
            }
            $carrierWebSite->value = $value;
            $carrierWebSite->save();
        }

        CarrierCache::flushCarrierConfigure($this->carrier->id);
        return returnApiJson('操作成功', 1);
    }

    public function telegramChannel()
    {
        $data               = [];
        $sysTelegramChannel = SysTelegramChannel::where('carrier_id',$this->carrier->id)->where('channel_sign','send_code')->first();
        if(!$sysTelegramChannel){
            $data['channel_group_name'] = '';
        } else {
            $data['channel_group_name'] = $sysTelegramChannel->channel_id;
        }

        $data['web_send_boot_token'] = CarrierCache::getCarrierConfigure($this->carrier->id, 'web_send_boot_token');

        return returnApiJson('操作成功', 1,$data);
    }

    public function telegramChannelSave()
    {
        $input   = request()->all();
        if(!isset($input['channel_group_name']) || trim($input['channel_group_name']) == '') {
            return returnApiJson('对不起,群组不能为空', 0);
        }

        $webSendBootToken   = CarrierCache::getCarrierConfigure($this->carrier->id, 'web_send_boot_token');

        if(empty($webSendBootToken)){
             return returnApiJson('对不起,机器token不能为空', 0);
        }

        $sysTelegramChannel = SysTelegramChannel::where('carrier_id',$this->carrier->id)->where('channel_sign','send_code')->first();

        $channelId          = Telegram::findChannelId($input['channel_group_name'],$webSendBootToken);

        if(!$channelId) {
            return returnApiJson('对不起,更新失败', 0);
        } else {
            if(!$sysTelegramChannel){
                $sysTelegramChannel               = new SysTelegramChannel();
                $sysTelegramChannel->carrier_id   = $this->carrier->id;
                $sysTelegramChannel->channel_sign = 'send_code';
            }

            $sysTelegramChannel->channel_group_name = $input['channel_group_name'];
            $sysTelegramChannel->channel_id         = $channelId;
            $sysTelegramChannel->save();

            return returnApiJson('更新成功', 1);
        }
    }

    public function telegramBotsave()
    {
        $input   = request()->all();

        if(!isset($input['web_send_boot_token']) || trim($input['web_send_boot_token']) == '') {
            return returnApiJson('对不起,机器人token不能为空', 0);
        }

        CarrierWebSite::where('carrier_id',$this->carrier->id)->where('sign','web_send_boot_token')->update(['value'=>trim($input['web_send_boot_token'])]);
        CarrierCache::flushCarrierCache($this->carrier->id,'conf_carrier_web_site');

        return returnApiJson('操作成功', 1);
    }

    public function remainquota()
    {
        $userTotalBalance = PlayerAccount::where('carrier_id',$this->carrier->id)->where('is_tester',0)->sum('balance');
        $onlineCount      = Player::where('carrier_id',$this->carrier->id)->whereIn('is_tester',[0,2])->where('is_online',1)->count();
        $onlineGuestCount = Player::where('carrier_id',$this->carrier->id)->where('is_tester',1)->where('is_online',1)->count();

        $info = [
            'realBalance'      => $this->carrier->remain_quota,
            'userTotalBalance' => $userTotalBalance / 10000,
            'onlineCount'      => $onlineCount,
            'onlineGuestCount' => $onlineGuestCount,
        ];


        return returnApiJson('获取成功', 1 ,$info);
    }

    public function serviceTeamAdd($id=0)
    {
        if($id==0){
            $carrierServiceTeam  = new CarrierServiceTeam();
        } else {
            $carrierServiceTeam  = CarrierServiceTeam::where('id',$id)->first();
            if(!$carrierServiceTeam){
                return returnApiJson('对不起，此角色不存在', 0);
            }
        }

        $output = $carrierServiceTeam->saveItem($this->carrier->id);
        if($output===true){
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($output, 0);
        }
    }

    public function serviceTeamStatus($serviceTeamId)
    {
        $carrierServiceTeam  = CarrierServiceTeam::where('id',$serviceTeamId)->first();
        if(!$carrierServiceTeam){
            return returnApiJson('对不起，此角色不存在', 0);
        }

        if($carrierServiceTeam->team_name == '超级管理员'){
            return returnApiJson('对不起，超级管理员不允许变更状态', 0);
        }

        $carrierServiceTeam->status = $carrierServiceTeam->status? 0:1;
        $carrierServiceTeam->save();

        return returnApiJson('操作成功', 1);
    }

    public function serviceTeamList()
    {
        $carrierServiceTeamList = CarrierServiceTeam::where('is_administrator',0)->get();

        return returnApiJson('操作成功', 1, $carrierServiceTeamList);
    }

    public function carrierUserList()
    {
        $carrierUsers      = CarrierUser::where('is_super_admin','<>','1')->get();
        return returnApiJson('操作成功', 1, $carrierUsers);
    }

    /**
     * 后台管理员 - 增删改查接口
     * @author benjamin
     * @since  1.0.0
     */
    public function adminLog() {

        # 搜索条件构造
        $condition = AdminLog::getCriteria(
            $this->getSafeData(), [
            'columns'    => AdminLog::$COLUMNS['adminPageList'],
            'order'      => AdminLog::TABLE_PK . ' DESC',
            'formatFunc' => 'formatList'
        ]);

        # 类型映射
        $this->response->setExtra('dataMap', [
            'typeMap'   => AdminLog::TYPE_MAP,
        ]);

        $this->success(AdminLog::TABLE_TITLE . '列表获取成功',
            AdminLog::getPageList($condition)
        );
    }

    /**
     * 会员管理 - 登录日志列表
     * @since  1.0.0
     * @author benjamin
     */
    public function adminLoginList () {

        # 搜索条件构造
        $condition = AdminSession::getCriteria(
            $this->getSafeParams(), [
            'columns'    => AdminSession::COLUMNS['adminPageList'],
            'order'      => AdminSession::TABLE_PK . ' DESC',
            'formatFunc' => 'formatList',
        ]);

        # 类型映射
//        $this->response->setExtra('dataMap', [
//            'deviceMap' => \Utils\Enum\ClientEnum::DEVICE_MAP,
//        ]);

        $this->success(AdminSession::TABLE_TITLE . ' - 列表获取成功',
            AdminSession::getPageList($condition)
        );

    }

    /**
     * 登录日志
     * @return mixed
     */
    public function adminLoginList2()
    {

        $carrierServiceTeamList = CarrierServiceTeam::where('carrier_id',$this->carrier->id)->where('is_administrator',0)->get();

        return returnApiJson('操作成功', 1, $carrierServiceTeamList);
    }

    public function carrierUserStatus($carrierUserId)
    {
        $carrierUsers  = CarrierUser::where('username','<>','super_admin')->where('id',$carrierUserId)->first();
        if(!$carrierUsers){
            return returnApiJson('对不起，此员工不存在', 0);
        }

        if($carrierUsers->is_super_admin == 1){
            return returnApiJson('对不起，超级管理员不允许变更状态', 0);
        }

        $carrierUsers->status = $carrierUsers->status? 0:1;
        $carrierUsers->save();

        return returnApiJson('操作成功', 1);
    }

    public function carrierUserAdd()
    {
        $carrierUser  = new CarrierUser();
        $output       = $carrierUser->carrierSaveItem($this->carrier);

        if($output===true){
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($output, 0);
        }
    }

    public function carrierEditItem($carrierUserId)
    {

        $carrierUser  = CarrierUser::where('username','<>','super_admin')->where('id',$carrierUserId)->first();
        if(!$carrierUser){
            return returnApiJson('对不起，此员工不存在', 0);
        }

        $output = $carrierUser->carrierEditItem();
        if($output===true){
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($output, 0);
        }
    }

    public function groupPermission($serviceteamId)
    {
         $carrierServiceTema = CarrierServiceTeam::where('id',$serviceteamId)->first();
         if(!$carrierServiceTema){
            return returnApiJson('对不起，此角色不存在', 0);
         }
         $parentGroups       = PermissionGroup::select('id','group_name')->where('parent_id',0)->orderBy('sort','asc')->get();

         foreach ($parentGroups as $key => $v) {
            $sonGroupsArr['id']         = $v->id;
            $sonGroupsArr['group_name'] = $v->group_name;
            $sonGroups                  = PermissionGroup::where('parent_id',$sonGroupsArr['id'])->orderBy('sort','asc')->get();
            foreach ($sonGroups as $k => $t) {
                $premissionsArr['id']         = $t->id;
                $premissionsArr['group_name'] = $t->group_name;
                $premissions    = Permission::select('name','description','id')->where('group_id',$premissionsArr['id'])->get();
                foreach ($premissions as $a => $b) {
                    $rowPremissions['name']         = $b->name;
                    $rowPremissions['description']  = $b->description;
                    $rowPremissions['id']           = $b->id;
                    $premissions[$a]                = $rowPremissions;
                }
                $premissionsArr['permissions'] = $premissions;
                $sonGroups[$k]                = $premissionsArr;
            }
            $sonGroupsArr['sonGroups']       =  $sonGroups;
            $parentGroups[$key]               = $sonGroupsArr;
         }

        $permissions = PermissionServiceTeam::where('service_team_id',$serviceteamId)->pluck('permission_id')->toArray();

        return returnApiJson('操作成功', 1,['premissions'=>$parentGroups,'permissions'=>$permissions]);
    }

    public function serviceTeamPermissionSave($serviceteamId)
    {
        $input = request()->all();
        if(!isset($input['permissionids']) || !is_array($input['permissionids'])){
            return returnApiJson('对不起，参数错误', 0);
        }

        $rowCount                 = Permission::whereIn('id',$input['permissionids'])->count();
        if($rowCount!= count($input['permissionids'])){
            return returnApiJson('对不起，部分权限不存在', 0);
        }

        $permissionServiceTeamIds = PermissionServiceTeam::where('service_team_id',$serviceteamId)->pluck('permission_id')->toArray();
        $addPermission            = array_diff($input['permissionids'],$permissionServiceTeamIds);
        $delPermission            = array_diff($permissionServiceTeamIds,$input['permissionids']);

        PermissionServiceTeam::where('service_team_id',$serviceteamId)->whereIn('permission_id',$delPermission)->delete();

        $insert                   = [];
        foreach ($addPermission as $key => $value) {
            $row                    = [];
            $row['permission_id']   = $value;
            $row['service_team_id'] = $serviceteamId;
            $insert[]               = $row;
        }

        \DB::table('permission_service_team')->insert($insert);

        return returnApiJson('操作成功', 1);
    }

    public function carrierUserResetPassword($id)
    {
        $carrierUser = CarrierUser::where('id',$id)->first();

        if(!$carrierUser){
            return returnApiJson('对不起，此员工不存在', 0);
        }

        if($carrierUser->is_super_admin==1){
            return returnApiJson('对不起，超级用户不能重置密码', 0);
        }

        $carrierUser->password =bcrypt(md5('123456'));
        $carrierUser->save();

        return returnApiJson('操作成功', 1);
    }

    public function playerIpblack()
    {
        $data['playeripblack'] = PlayerIpBlack::where('carrier_id',$this->carrier->id)->first();
        return returnApiJson('操作成功', 1, $data);
    }

    public function playerIpblackUpdate()
    {
        $playerIpBlack = PlayerIpBlack::where('carrier_id',$this->carrier->id)->first();
        $result        = $playerIpBlack->playerIpblackUpdate();

        if($result===true){
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($result, 0);
        }
    }

    public function systemNoticeList()
    {
        $otherCarriers            = Carrier::where('is_forbidden',0)->where('id','!=',$this->carrier->id)->orderBy('id','desc')->get();
        $notices                  = [];

        $playerWithdrawCount      = PlayerWithdraw::where('carrier_id',$this->carrier->id)->where('status',0)->where('is_suspend',0)->count();
        $playerDepositPayLogCount = PlayerDepositPayLog::where('carrier_id',$this->carrier->id)->where('status',2)->count();
        $playerActivityAuditCount = PlayerActivityAudit::where('carrier_id',$this->carrier->id)->where('status',0)->count();

        foreach ($otherCarriers as $key => $value) {
            $otherPlayerWithdrawCount      = PlayerWithdraw::where('carrier_id',$value->id)->where('status',0)->where('is_suspend',0)->count();
            $otherPlayerDepositPayLogCount = PlayerDepositPayLog::where('carrier_id',$value->id)->where('status',2)->count();
            $otherPlayerActivityAuditCount = PlayerActivityAudit::where('carrier_id',$value->id)->where('status',0)->count();

            if($otherPlayerWithdrawCount || $otherPlayerDepositPayLogCount || $otherPlayerActivityAuditCount ){
                $rows                             = [];
                $rows['playerWithdrawCount']      = $otherPlayerWithdrawCount;
                $rows['playerDepositPayLogCount'] = $otherPlayerDepositPayLogCount;
                $rows['playerActivityAuditCount'] = $otherPlayerActivityAuditCount;
                $rows['carrier_id']               = $value->id;
                $rows['carrier_name']             = $value->name;
                $notices[]                        = $rows;
            }
        }

        $onlineCount              = Player::where('carrier_id',$this->carrier->id)->where('is_online',1)->count();

        return returnApiJson('操作成功', 1,['playerWithdrawCount'=>$playerWithdrawCount,'playerDepositPayLogCount'=>$playerDepositPayLogCount,'playerActivityAuditCount'=>$playerActivityAuditCount,'onlinenumber'=>$onlineCount,'otherNotices'=>$notices]);
    }

    public function changeLottRewater()
    {
        $input  = request()->all();
        $update = [];

        if(count($update)){
            PlayerSetting::where('carrier_id',$this->carrier->id)->where('is_tester',0)->update($update);
            PlayerInviteCode::where('carrier_id',$this->carrier->id)->where('is_tester',0)->update($update);

            PlayerCache::forgetAllPlayerSetting();
        }

        return  returnApiJson('操作成功', 1);
    }

    public function allSites()
    {
        $carriers   = Carrier::select('name','sign','id')->where('is_forbidden',0)->get();
        foreach ($carriers as $key => &$value) {
            $value->sign = strtolower($value->sign);
        }
        return  returnApiJson('操作成功', 1,$carriers);
    }

    public function horizontalMenusList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = CarrierHorizontalMenu::where('carrier_id',$this->carrier->id);

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        $total                     = $query->count();
        $carrierHorizontalMenus    = $query->orderBy('sort','desc')->skip($offset)->take($pageSize)->get()->toArray();


        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($carrierHorizontalMenus as $key => &$value) {
            if($value['type']=='fish'){
                $value['typename'] = '捕鱼游戏';
            } elseif ($value['type']=='lottery') {
                $value['typename'] = '彩票投注';
            } elseif ($value['type']=='sport') {
                $value['typename'] = '体育赛事';
            } elseif ($value['type']=='card') {
                $value['typename'] = '棋牌游戏';
            } elseif ($value['type']=='esport') {
                $value['typename'] = '电竞游戏';
            } elseif ($value['type']=='electronic') {
               $value['typename'] = '电子游戏';
            } elseif ($value['type']=='live') {
                $value['typename'] = '真人视讯';
            } elseif ($value['type']=='hotgamelist') {
                $value['typename'] = '热门游戏';
            }
            $value['multiple_name'] = $carrierPreFixDomainArr[$value['prefix']];
        }
        return  returnApiJson('操作成功', 1,['data' => $carrierHorizontalMenus, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function changeHorizontalMenusStatus($id)
    {
        $carrierHorizontalMenu = CarrierHorizontalMenu::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if(!$carrierHorizontalMenu){
            return  returnApiJson('对不起，此条数据不存在', 0);
        }

        $carrierHorizontalMenu->status = $carrierHorizontalMenu->status ? 0:1;
        $carrierHorizontalMenu->save();

        return  returnApiJson('操作成功', 1);
    }

    public function updateHorizontalMenus($id=0)
    {
        if($id){
            $carrierHorizontalMenu = CarrierHorizontalMenu::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
            if(!$carrierHorizontalMenu){
                return  returnApiJson('对不起，此条数据不存在', 0);
            }
        } else{
            $carrierHorizontalMenu = new CarrierHorizontalMenu();
        }

        $res = $carrierHorizontalMenu->updateHorizontalMenus($this->carrier,$this->carrierUser);
        if($res===true){
            return  returnApiJson('操作成功', 1);
        } else{
            return  returnApiJson($res, 0);
        }
    }

    public function horizontalMenuType()
    {
        $horizontalMenus = config('main')['horizontalmenu'];
        return  returnApiJson('操作成功', 1,$horizontalMenus);
    }

    public function guaranteedAdd($id=0)
    {
        if($id){
            $carrierGuaranteed =  CarrierGuaranteed::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
            if(!$carrierGuaranteed){
                return $this->returnApiJson('对不起，此条数据不存在', 0);
            }
        } else{
            $carrierGuaranteed = new CarrierGuaranteed();
        }

        $result = $carrierGuaranteed->guaranteedAdd($this->carrier);
        if($result===true){
            return $this->returnApiJson('操作成功', 1);
        } else{
            return $this->returnApiJson($result, 0);
        }
    }

    public function guaranteedDel($id)
    {
       $carrierGuaranteed =  CarrierGuaranteed::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if(!$carrierGuaranteed){
            return $this->returnApiJson('对不起，此条数据不存在', 0);
        }

        $carrierGuaranteed->delete();

        return $this->returnApiJson('操作成功', 1);
    }

    public function guaranteedList()
    {
       $res = CarrierGuaranteed::guaranteedList($this->carrier);
       if(is_array($res)){
            return $this->returnApiJson('操作成功', 1,$res);
       } else{
            return $this->returnApiJson($res, 0);
       }
    }

    public function allPrefix()
    {
        $carrierPreFixDomain = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->pluck('prefix')->toArray();
        return $this->returnApiJson('操作成功', 1,$carrierPreFixDomain);
    }

    public function prefixList()
    {
        $carrierPreFixDomain = CarrierPreFixDomain::select('name','prefix')->where('carrier_id',$this->carrier->id)->get();
        return $this->returnApiJson('操作成功', 1,$carrierPreFixDomain);
    }

    public function allPrefixSetting()
    {
        $input          = request()->all();

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return returnApiJson('对不起，前辍取值不正确', 0);
        }
        $carrierPreFixGamePlats = CarrierPreFixGamePlat::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->get();

        $carrierMultipleFront   = CarrierMultipleFront::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->get();

        $arbitrageGameNameList = '';
        foreach ($carrierMultipleFront as $k => &$v) {
            if($v->sign=='arbitrage_game_list' &&!empty($v->value)){
                $gameIds        = explode(',',$v->value);
                $arbitrageGames = Game::select('game_name','main_game_plat_code')->whereIn('game_id',$gameIds)->get();
                foreach ($arbitrageGames as $k1 => $v1) {
                    $arbitrageGameNameList.=$v1->main_game_plat_code.'-'.$v1->game_name.' | ';
                }
                $arbitrageGameNameList=rtrim($arbitrageGameNameList,'| ');
            }
        }

        $liveBroadcastAwardsList = '';
        foreach ($carrierMultipleFront as $k => &$v) {
            if($v->sign=='live_broadcast_awards' &&!empty($v->value)){
                $gameIds        = explode(',',$v->value);
                $liveBroadcastAwards = Game::select('game_name','main_game_plat_code')->whereIn('game_id',$gameIds)->get();
                foreach ($liveBroadcastAwards as $k1 => $v1) {
                    $liveBroadcastAwardsList.=$v1->main_game_plat_code.'-'.$v1->game_name.' | ';
                }
                $liveBroadcastAwardsList=rtrim($liveBroadcastAwardsList,'| ');
            }
        }

        $data                 = [];
        $signarr1                 = ['casino_betflow_calculate_rate','electronic_betflow_calculate_rate','esport_betflow_calculate_rate','fish_betflow_calculate_rate','card_betflow_calculate_rate','lottery_betflow_calculate_rate','sport_betflow_calculate_rate','is_bet_flow_convert','agent_casino_betflow_calculate_rate','agent_electronic_betflow_calculate_rate','agent_esport_betflow_calculate_rate','agent_fish_betflow_calculate_rate','agent_card_betflow_calculate_rate','agent_lottery_betflow_calculate_rate','agent_sport_betflow_calculate_rate','enabel_agent_commissionflow_single'];
        $signarr2                 = ['no_fake_pg_playerids','is_loss_write_betflow','enable_clean_loss','clean_loss_amount_cycle','clean_loss_amount','enable_fast_kill','enable_agent_game_limit','first_deposit_activity_plus','batch_register_ip_number','enable_batch_register_froze','ip_blacklist','fake_withdraw_player_ids','fake_withdraw_limit','forcibly_joinfakegame_activityid','materialIds','prefix_type','live_broadcast_awards','skip_abrbitrageurs_judge_channel'];
        $signarr3                 = ['guaranteed_level_difference','limit_highest_guaranteed','dividend_level_difference','limit_highest_dividend','enabele_setting_dividends','enabele_setting_guaranteed','dividend_enumerate'];
        $signarr5                 = ['third_wallet','disable_withdraw_channel','finance_min_recharge','finance_max_recharge','in_r_out_u','in_t_out_u','digital_rate','withdraw_digital_rate','finance_min_withdraw','min_withdrawal_usdt','withdraw_ratefee','alipay_withdraw_ratefee'];
        $signarr6                 = ['enable_register_gift_code','voucher_money','voucher_betflow_multiple','voucher_valid_day','voucher_recharge_amount','enable_send_voucher','register_gift_code_amount','is_show_front_exchange','stop_exchange_rate','enable_coupons_bank_store','not_included_exchange_rate','voucher_withdraw_max_money','enable_voucher_recharge','voucher_need_recharge_amount','skip_abrbitrageurs_judge_channel','disable_voucher_channel','disable_voucher_team_channel'];
        $signarr7                 = ['player_dividends_day','player_dividends_method','player_dividends_start_day','operating_expenses','player_realtime_dividends_start_day','directlyunder_commission_dividends_rate','enable_tongbao_method','tongbao_rate','bonus_rate','enable_dividends_tongbao_method','tongbao_dividends_rate','wallet_passage_rate','no_wallet_passage_rate','agent_single_background','player_cycle_deposit_amount','player_cycle_continue_deposit','player_cycle_betflow','prefix_exclusive_rate','prefix_exclusive_active','prefix_decreasing_rate','dividends_receive_method'];
        $signarr8                 = ['is_registergift','giftmultiple','is_bindbankcardorthirdwallet','registergift_limit_day_number','register_probability','registergift_limit_cycle','enable_register_img_verification','enable_login_img_verification','carrier_register_telehone','enable_login_behavior_verification','enable_register_behavior_verification','is_allow_general_agent','is_allow_player_register','register_real_name','register_receive_activityid','player_max_register_one_ip_minute'];
        $signarr9                 = ['enable_capitation_fee','capitation_fee_type','capitation_fee_recharge_amount','capitation_fee_bet_flow','capitation_fee_gift_amount','capitation_fee_deposit_days','is_capitation_first_deposit_calculate','capitation_first_deposit_calculate_activityid','capitation_fee_cycle','capitation_fee_rule','capitation_fee_single_recharge_amount'];
        $signarr11                = ['open_sign_in','sign_in_category','sign_in_day_gift','sign_in_flow_limit_multiple','sign_in_need_recharge_amount','sign_in_need_bet_flow'];
        $signarr13                = ['arbitrage_game_list','arbitrage_game_name_list','arbitrage_game_flow_convert','pg_replace_curr_cw_rate','pg_replace_today_curr_cw_rate','recharge_withdraw_proportion','cycle_recharge_withdraw_proportion','replace_curr_cw_rate','replace_today_curr_cw_rate','register_code_recharge','current_intelligent_rate','one_and_one_recharge_amount','one_and_one_withdrawal_amount','site_stock'];
        $signarr15                = ['enable_bet_gradient_rebate','video_bet_gradient_rebate','ele_bet_gradient_rebate','esport_bet_gradient_rebate','card_bet_gradient_rebate','sport_bet_gradient_rebate','fish_bet_gradient_rebate','lott_bet_gradient_rebate','rebate_method'];
        $signarr17                = ['enable_fixed_earnings','enable_fixed_guaranteed','default_earnings','default_guaranteed','enable_auto_guaranteed_upgrade'];
        $signarr18                = ['enable_invite_gradient_rebate','video_invite_gradient_rebate','ele_invite_gradient_rebate','esport_invite_gradient_rebate','card_invite_gradient_rebate','sport_invite_gradient_rebate','fish_invite_gradient_rebate','lott_invite_gradient_rebate'];
        $signarr19                = ['marketing_contact'];
        $signarr16                 = ['short_link_no_register','no_delete_short_link'];
        $arr1                     = [];
        $arr2                     = [];
        $arr3                     = [];
        $arr4                     = [];
        $arr5                     = [];
        $arr6                     = [];
        $arr7                     = [];
        $arr8                     = [];
        $arr9                     = [];
        $arr10                    = [];
        $arr11                    = [];
        $arr13                    = [];
        $arr15                    = [];
        $arr16                    = [];
        $arr17                    = [];
        $arr18                    = [];
        $arr19                    = [];

        foreach ($carrierMultipleFront as $key => $value) {
            if(in_array($value->sign, $signarr1)){
                $rows           = [];
                $rows['key']    = $value->sign;
                $rows['value']  = $value->value;
                $rows['remark'] = $value->remark;
                $arr1[]         = $rows;
            } elseif(in_array($value->sign, $signarr2)){
                $rows           = [];
                $rows['key']    = $value->sign;
                $rows['value']  = $value->value;
                $rows['remark'] = $value->remark;
                $arr2[]         = $rows;
            } elseif(in_array($value->sign, $signarr3)){
                $rows           = [];
                $rows['key']    = $value->sign;
                $rows['value']  = $value->value;
                $rows['remark'] = $value->remark;
                $arr3[]         = $rows;
            } elseif(in_array($value->sign, $signarr5)){
                $rows           = [];
                $rows['key']    = $value->sign;
                if(in_array($value->sign,['third_wallet','disable_withdraw_channel'])){
                    $rows['value']  = json_decode($value->value,true);
                } else{
                    $rows['value']  = $value->value;
                }

                $rows['remark'] = $value->remark;
                $arr5[]         = $rows;
            } elseif(in_array($value->sign, $signarr6)){
                $rows           = [];
                $rows['key']    = $value->sign;
                $rows['value']  = $value->value;
                $rows['remark'] = $value->remark;
                $arr6[]         = $rows;
            } elseif(in_array($value->sign, $signarr7)){
                $rows           = [];
                $rows['key']    = $value->sign;
                $rows['value']  = $value->value;
                $rows['remark'] = $value->remark;
                $arr7[]         = $rows;
            } elseif(in_array($value->sign, $signarr8)){
                $rows           = [];
                $rows['key']    = $value->sign;

                if($value->sign=='register_probability'){
                    $rows['value']  = json_decode($value->value,true);
                } else{
                    $rows['value']  = $value->value;
                }

                $rows['remark'] = $value->remark;
                $arr8[]         = $rows;

            } elseif(in_array($value->sign, $signarr9)){
                $rows           = [];
                $rows['key']    = $value->sign;
                $rows['value']  = $value->value;
                $rows['remark'] = $value->remark;
                $arr9[]         = $rows;
            } elseif(in_array($value->sign, $signarr11)){
                $rows           = [];
                $rows['key']    = $value->sign;

                if($value->sign=='sign_in_day_gift'){
                    $rows['value']  = json_decode($value->value,true);
                } else{
                    $rows['value']  = $value->value;
                }

                $rows['remark'] = $value->remark;
                $arr11[]         = $rows;
            }elseif(in_array($value->sign, $signarr13)){
                $rows           = [];
                $rows['key']    = $value->sign;
                $rows['value']  = $value->value;
                $rows['remark'] = $value->remark;
                $arr13[]         = $rows;
            }elseif(in_array($value->sign, $signarr15)){
                $rows           = [];
                $rows['key']    = $value->sign;
                
                if($value->sign=='enable_bet_gradient_rebate' || $value->sign=='rebate_method'){
                    $rows['value']  = $value->value;
                } else{
                    $rows['value']  = json_decode($value->value,true);
                }
                $rows['remark'] = $value->remark;
                $arr15[]         = $rows;
            }elseif(in_array($value->sign, $signarr16)){
                $rows           = [];
                $rows['key']    = $value->sign;
                $rows['remark'] = $value->remark;
                $rows['value']  = $value->value;
                $arr16[]         = $rows;
            }elseif(in_array($value->sign, $signarr17)){
                $rows           = [];
                $rows['key']    = $value->sign;
                $rows['value']  = $value->value;
                $rows['remark'] = $value->remark;
                $arr17[]         = $rows;
            } elseif(in_array($value->sign, $signarr18)){
                $rows           = [];
                $rows['key']    = $value->sign;

                if($value->sign=='enable_invite_gradient_rebate'){
                    $rows['value']  = $value->value;
                } else{
                    $rows['value']  = json_decode($value->value,true);
                }

                $rows['remark'] = $value->remark;
                $arr18[]         = $rows;
            }elseif(in_array($value->sign, $signarr19)){
                $rows           = [];
                $rows['key']    = $value->sign;
                if($value->sign=='marketing_contact'){
                    $rows['value']  = json_decode($value->value,true);
                } else{
                    $rows['value']  = $value->value;
                }
                $rows['remark'] = $value->remark;
                $arr19[]         = $rows;
            }else{
                $rows           = [];
                $rows['key']    = $value->sign;
                $rows['value']  = $value->value;
                $rows['remark'] = $value->remark;
                $arr4[]         = $rows;
            }
        }

        $rows           = [];
        $rows['key']    = 'arbitrage_game_name_list';
        $rows['value']  = $arbitrageGameNameList;
        $rows['remark'] = '刷水游戏列表';
        $arr13[]        = $rows;

        $rows           = [];
        $rows['key']    = 'live_broadcast_awards_list';
        $rows['value']  = $liveBroadcastAwardsList;
        $rows['remark'] = '直播爆奖游戏列表';
        $arr2[]        = $rows;

        $mainGamePlats    = MainGamePlat::all();
        $mainGamePlatArrs = [];
        foreach ($mainGamePlats as $key => $value) {
            $mainGamePlatArrs[$value->main_game_plat_id] = $value->alias;
        }

        foreach ($carrierPreFixGamePlats as $key => $value) {
            $rows           = [];
            $rows['key']    = 'game_plat_id_'.$value->game_plat_id;
            $rows['value']  = $value->point;
            $rows['remark'] = $mainGamePlatArrs[$value->game_plat_id];
            $arr10[]        = $rows;
        }

        $data =[
            ['title'=>'基本设置','arr'  =>$arr4],
            ['title'=>'默认保底分红设置','arr'  =>$arr17],
            ['title'=>'会员返佣金设置','arr'  =>$arr3],
            ['title'=>'流水比例设置','arr'  =>$arr1],
            ['title'=>'会员返水梯度设置','arr'  =>$arr15],
            ['title'=>'直属投注梯度返佣设置','arr'  =>$arr18],
            ['title'=>'盈利加强设置','arr'  =>$arr2],
            ['title'=>'体验券设置','arr'  =>$arr6],
            ['title'=>'注册登录设置','arr'  =>$arr8],
            ['title'=>'存取款设置','arr'  =>$arr5],
            ['title'=>'人头费设置','arr'  =>$arr9],
            ['title'=>'套利设置与统计','arr'  =>$arr13],
            ['title'=>'场馆费设置','arr'  =>$arr10],
            ['title'=>'签倒活动设置','arr'  =>$arr11],
            ['title'=>'客服设置','arr'  =>$arr19],
            ['title'=>'分红设置','arr'  =>$arr7],
            ['title'=>'短链接设置','arr'  =>$arr16]
        ];

        return $this->returnApiJson('操作成功', 1, $data);
    }

    public function dataMonitor()
    {
        $time                    = time() - 3600;
        $playerBetFlowPlayerIds  = PlayerBetFlowMiddle::where('carrier_id',$this->carrier->id)->where('bet_time','>=',$time)->where('whether_recharge',1)->pluck('player_id')->toArray();
        $players                 = Player::whereIn('player_id',$playerBetFlowPlayerIds)->get();
        $data                    = [];
            
        foreach ($players as $key => $value) {
            $playerTransferAmount       = PlayerTransfer::select('amount','created_at')->where('player_id',$value->player_id)->where('type','recharge')->orderBy('id','desc')->first();
            $rechargeAmount             = 0;
            $rechargeTime               = 0; 

            if($playerTransferAmount && !is_null($playerTransferAmount->amount)){
                $rechargeAmount = bcdiv($playerTransferAmount->amount,10000,2);
                $rechargeTime   = strtotime($playerTransferAmount->created_at);
            }

            $playerBetFlowMiddle        = PlayerBetFlowMiddle::select(\DB::raw('sum(company_win_amount) as company_win_amount'))->where('player_id',$value->player_id)->where('bet_time','>=',$rechargeTime)->first();

            $playerWithdrawFlowLimit    = PlayerWithdrawFlowLimit::select(\DB::raw('sum(limit_amount) as limit_amount'),\DB::raw('sum(complete_limit_amount) as complete_limit_amount'))->where('player_id',$value->player_id)->where('is_finished',0)->first();

            $unLimitAmount = 0;
            if($playerWithdrawFlowLimit && !is_null($playerWithdrawFlowLimit->limit_amount)){
                $unLimitAmount =  bcdiv($playerWithdrawFlowLimit->limit_amount - $playerWithdrawFlowLimit->complete_limit_amount,10000,2);
            }

            $row                             = [];
            $row['player_id']                = $value->player_id;
            $row['user_name']                = $value->user_name;
            $row['recharge_Amount']          = $rechargeAmount;
            if($playerTransferAmount && strtotime($playerTransferAmount->created_at)){
                $row['recharge_Time']            = date('Y-m-d H:i:s',strtotime($playerTransferAmount->created_at));
            } else{
                $row['recharge_Time']            = '';
            }
            
            $row['company_win_amount']       = $playerBetFlowMiddle && is_null($playerBetFlowMiddle->company_win_amount) ? 0 :$playerBetFlowMiddle->company_win_amount;
            $row['un_complete_limit_amount'] = $unLimitAmount;
            $data[]                          = $row;
        }
        return $this->returnApiJson('操作成功', 1,$data);
    }
    public function rechargWithdrawStat()
    {
        $input = request()->all();

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return $this->returnApiJson('对不起，用户ID不能为空', 0);
        }

        $player = Player::where('player_id',$input['player_id'])->first();
        if(!$player){
            return $this->returnApiJson('对不起，此用户不存在', 0);
        }

        $query1 = PlayerDepositPayLog::select(\DB::raw('sum(amount) as amount'))->where('player_id',$input['player_id'])->where('status',1);
        $query2 = PlayerDepositPayLog::select(\DB::raw('sum(amount) as amount'))->where('parent_id',$input['player_id'])->where('status',1);
        $query3 = PlayerDepositPayLog::select(\DB::raw('sum(amount) as amount'))->where('rid','like',$player->rid.'|%')->where('parent_id','!=',$input['player_id'])->where('status',1);
        $query4 = PlayerWithdraw::select(\DB::raw('sum(amount) as amount'))->where('player_id',$input['player_id'])->whereIn('status',[1,2]);
        $query5 = PlayerWithdraw::select(\DB::raw('sum(amount) as amount'))->where('parent_id',$input['player_id'])->whereIn('status',[1,2]);
        $query6 = PlayerWithdraw::select(\DB::raw('sum(amount) as amount'))->where('rid','like',$player->rid.'|%')->where('parent_id','!=',$input['player_id'])->whereIn('status',[1,2]);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query1->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $query2->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $query3->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $query4->where('created_at','>=',date('Y-m-d',strtotime($input['startDate'])).' 00:00:00');
            $query5->where('created_at','>=',date('Y-m-d',strtotime($input['startDate'])).' 00:00:00');
            $query6->where('created_at','>=',date('Y-m-d',strtotime($input['startDate'])).' 00:00:00');
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query1->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $query2->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $query3->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $query4->where('created_at','<=',date('Y-m-d',strtotime($input['endDate'])).' 23:59:59');
            $query5->where('created_at','<=',date('Y-m-d',strtotime($input['endDate'])).' 23:59:59');
            $query6->where('created_at','<=',date('Y-m-d',strtotime($input['endDate'])).' 23:59:59');
        }

        $item1 = $query1->first();
        $item2 = $query2->first();
        $item3 = $query3->first();
        $item4 = $query4->first();
        $item5 = $query5->first();
        $item6 = $query6->first();

        $data                                    = [];
        $data['recharge_amount']                 = 0;
        $data['directly_under_recharge_amount']  = 0;
        $data['team_recharge_amount']            = 0;
        $data['withdraw_amount']                 = 0;
        $data['directly_under_withdraw_amount']  = 0;
        $data['team_withdraw_amount']            = 0;

        if($item1 && !is_null($item1->amount)){ $data['recharge_amount']                = $item1->amount;}
        if($item2 && !is_null($item2->amount)){ $data['directly_under_recharge_amount'] = $item2->amount;}
        if($item3 && !is_null($item3->amount)){ $data['team_recharge_amount']           = $item3->amount;}
        if($item4 && !is_null($item4->amount)){ $data['withdraw_amount']                = $item4->amount;}
        if($item5 && !is_null($item5->amount)){ $data['directly_under_withdraw_amount'] = $item5->amount;}
        if($item6 && !is_null($item6->amount)){ $data['team_withdraw_amount']           = $item6->amount;}

        return $this->returnApiJson('操作成功', 1,$data);
    }

    public function safeDetect()
    {
        $input = request()->all();
        $data  = [];

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return $this->returnApiJson('对不起，用户ID不能为空',0);
        }

        $player = Player::where('player_id',$input['player_id'])->first();
        if(!$player){
            return $this->returnApiJson('对不起，此用户不存在',0);
        }

        $playerDividendsMethod                        = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'player_dividends_method',$player->prefix);
        if($player->win_lose_agent && $playerDividendsMethod==5){
            $parent = $player;
        } else{
            $playerId                                   = $player->parent_id;
            $defaultAgent                               = CarrierCache::getDefaultAgent($player->carrier_id);
            do{
                $parent                                 = Player::where('player_id',$playerId)->first();
                if($parent->player_id == $defaultAgent->player_id || $parent->win_lose_agent){
                    break;
                }
                $playerId = $parent->parent_id;
            } while(!$parent->win_lose_agent);
        }

        if($parent->win_lose_agent){
            $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($parent->carrier_id,'player_dividends_method',$parent->prefix);
            switch ($playerDividendsMethod) {
                case 1:
                    $partnerInfo                                    = DevidendMode1::calculateDividend($parent,null,null,1);
                    $partnerInfo['directlyunder_recharge_amount']   = $partnerInfo['directlyunderRecharge'];
                    $partnerInfo['directlyunder_withdraw_amount']   = $partnerInfo['directlyunderWithdraw'];
                    $partnerInfo['team_recharge_amount']            = $partnerInfo['teamRecharge'];
                    $partnerInfo['team_withdraw_amount']            = $partnerInfo['teamWithdraw'];
                    $partnerInfo['amount']                          = $partnerInfo['totalCommission'];
                    break;
                case 2:
                    $partnerInfo                                    = DevidendMode2::calculateDividend($parent);
                    $partnerInfo['directlyunder_recharge_amount']   = $partnerInfo['directlyunder_recharge_amount'];
                    $partnerInfo['directlyunder_withdraw_amount']   = $partnerInfo['directlyunder_withdraw_amount'];
                    $partnerInfo['team_recharge_amount']            = $partnerInfo['team_recharge_amount'];
                    $partnerInfo['team_withdraw_amount']            = $partnerInfo['team_withdraw_amount'];
                    $partnerInfo['amount']                          = $partnerInfo['amount'];
                    break;
                case 3:
                    $partnerInfo                                    = DevidendMode3::calculateDividend($parent);
                    $partnerInfo['directlyunder_recharge_amount']   = $partnerInfo['directlyunderRecharge'];
                    $partnerInfo['directlyunder_withdraw_amount']   = $partnerInfo['directlyunderWithdraw'];
                    $partnerInfo['team_recharge_amount']            = $partnerInfo['teamRecharge'];
                    $partnerInfo['team_withdraw_amount']            = $partnerInfo['teamWithdraw'];
                    $partnerInfo['amount']                          = $partnerInfo['totalCommission'];
                    break;
                case 5:
                    $partnerInfo                                    = DevidendMode5::calculateDividend($parent);
                    $partnerInfo['directlyunder_recharge_amount']   = $partnerInfo['directlyunderRecharge'];
                    $partnerInfo['directlyunder_withdraw_amount']   = $partnerInfo['directlyunderWithdraw'];
                    $partnerInfo['team_recharge_amount']            = $partnerInfo['teamRecharge'];
                    $partnerInfo['team_withdraw_amount']            = $partnerInfo['teamWithdraw'];
                    $partnerInfo['amount']                          = $partnerInfo['totalCommission'];
                    break;
                case 4:
                    $partnerInfo                                    = DevidendMode4::calculateDividend($parent);
                    $partnerInfo['directlyunder_recharge_amount']   = $partnerInfo['directlyunderRecharge'];
                    $partnerInfo['directlyunder_withdraw_amount']   = $partnerInfo['directlyunderWithdraw'];
                    $partnerInfo['team_recharge_amount']            = $partnerInfo['teamRecharge'];
                    $partnerInfo['team_withdraw_amount']            = $partnerInfo['teamWithdraw'];
                    $partnerInfo['amount']                          = $partnerInfo['totalCommission'];
                    break;
                
                default:
                    // code...
                    break;
            }

            $data['team_recharge_amount']           = $partnerInfo['team_recharge_amount'];
            $data['team_withdraw_amount']           = $partnerInfo['team_withdraw_amount'];
            $data['directlyunder_recharge_amount']  = $partnerInfo['directlyunder_recharge_amount'];
            $data['directlyunder_withdraw_amount']  = $partnerInfo['directlyunder_withdraw_amount'];
            $data['amount']                         = $partnerInfo['amount'];

        } else{
            $data['team_recharge_amount']           = 0;
            $data['team_withdraw_amount']           = 0;
            $data['directlyunder_recharge_amount']  = 0;
            $data['directlyunder_withdraw_amount']  = 0;
            $data['amount']                         = 0; 
        }

        return $this->returnApiJson('操作成功', 1,$data);
    }

    public function stockList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $defaultUserName= CarrierCache::getCarrierConfigure($this->carrier->id,'default_user_name');

        $query          = ReportPlayerStatDay::select('self_stock','change_self_stock','change_stock','stock','team_stock','change_team_stock','player_id','user_name','parent_id','prefix','day')->where('user_name','!=',$defaultUserName)->orderBy('id','desc');

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return $this->returnApiJson('对不起，此站点不存在',0);
        }

        if(isset($input['rid']) && !empty($input['rid'])){
            $player = Player::where('player_id',$input['rid'])->where('prefix',$input['prefix'])->first();
            if(!$player){
                return $this->returnApiJson('操作成功', 1,['data' => [], 'total' => 0, 'currentPage' => 1, 'totalPage' => 0]);
            } else{
                $query->where('rid','like',$player->rid.'|%');
            }
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('player_id',$input['player_id']);
        }

        if(isset($input['parent_id']) && !empty($input['parent_id'])){
            $query->where('parent_id',$input['parent_id']);
        }

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $query->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['greater_than_stock']) && is_numeric($input['greater_than_stock'])){
            $query->where('self_stock','>=',$input['greater_than_stock']*10000);
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=', date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=', date('Ymd',strtotime($input['endDate'])));
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($data as $k => &$v) {
            $v->day           = date('Y-m-d',strtotime($v->day));
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
        }

        return $this->returnApiJson('操作成功', 1,['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function capitationFeeLevelsAdd($id=0)
    {
        $input          = request()->all();

        if($id){
           $carrierCapitationFeeSetting = CarrierCapitationFeeSetting::where('carrier_id',$this->carrier->id)->where('id',$id)->first(); 
           if(!$carrierCapitationFeeSetting){
                return $this->returnApiJson('对不起，这条数据不存在',0);
           }
        } else {
            $carrierCapitationFeeSetting              = new CarrierCapitationFeeSetting();
        }
        if(!isset($input['prefix']) || empty($input['prefix'])){
            return $this->returnApiJson('对不起，此站点不存在',0);
        }

        $capitationFeeGiftAmount = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'capitation_fee_gift_amount',$input['prefix']);
        $enableCapitationFee     = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'enable_capitation_fee',$input['prefix']);
        if(!$enableCapitationFee){
            return $this->returnApiJson('对不起，未开启人头费',0);
        }

        if(!isset($input['sort']) || !is_numeric($input['sort']) || intval($input['sort']) != $input['sort'] || $input['sort'] < 1 ){
            return $this->returnApiJson('对不起，关卡取值不正确',0);
        }
        
        $carrierCapitationFeeSetting->carrier_id  = $this->carrier->id;
        $carrierCapitationFeeSetting->prefix      = $input['prefix'];
        $carrierCapitationFeeSetting->amount      = $capitationFeeGiftAmount;
        $carrierCapitationFeeSetting->sort        = $input['sort'];
        $carrierCapitationFeeSetting->status      = 1;
        $carrierCapitationFeeSetting->save();

        return $this->returnApiJson('操作成功', 1);
    }

    public function capitationFeeLevelsList()
    {
        $res = CarrierCapitationFeeSetting::capitationFeeLevelsList($this->carrier);
        if(is_array($res)){
            return $this->returnApiJson('操作成功', 1 ,$res);
        } else{
            return $this->returnApiJson($res,0);
        }
    }

    public function capitationFeeLevelsDel($id)
    {
        $carrierCapitationFeeSetting = CarrierCapitationFeeSetting::where('id',$id)->first();
        if(!$carrierCapitationFeeSetting){
            return $this->returnApiJson('对不起，此条数据不存在',0);
        }

        $carrierCapitationFeeSetting->delete();

        return $this->returnApiJson('操作成功', 1);
    }

    public function voucherConvertList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerGiftCode::select('parent_id','prefix','player_id')->groupBy('parent_id');

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('parent_id',$input['player_id']);
        }

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $player = Player::where('user_name',$input['user_name'])->first();
            if($player){
                $query->where('parent_id',$player->player_id);
            } else{
                $query->where('parent_id','');
            }
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $total         = count($query->get());
        $item          = $query->skip($offset)->take($pageSize)->get();

        $playerIds     = [];
        $userNames     = [];

        foreach ($item as $k => $v) {
            $playerIds[] = $v->parent_id;
        }

        $players = Player::whereIn('player_id',$playerIds)->get();
        foreach ($players as $k => $v) {
            $userNames[$v->player_id] = $v->user_name;
        }

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k1 => $v1) {
            $carrierPreFixDomainArr[$v1->prefix] = $v1->name;
        }

        foreach ($item as $key => &$value) {
            $query1 = PlayerGiftCode::where('parent_id',$value->parent_id);
            $query2 = PlayerGiftCode::where('parent_id',$value->parent_id)->where('is_recharge',1);

            if(isset($input['startDate']) && strtotime($input['startDate'])){
                $query1->where('day','>=',date('Ymd',strtotime($input['startDate'])));
                $query2->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            }

            if(isset($input['endDate']) && strtotime($input['endDate'])){
                $query1->where('day','<=',date('Ymd',strtotime($input['endDate'])));
                $query2->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            }

            $voucherNumber          = $query1->count();
            $rechargeNumber         = $query2->count();
            $value->user_name       = $userNames[$value->parent_id];

            $value->player_id       = $value->parent_id;
            $value->voucher_number  = $voucherNumber;
            $value->recharge_number = $rechargeNumber;
            $value->convert_rate    = bcmul(bcdiv($rechargeNumber, $voucherNumber,2),100,2);
            $value->multiple_name   = $carrierPreFixDomainArr[$value->prefix];
        }
        return $this->returnApiJson('操作成功', 1,['item' => $item,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function popList() 
    {
        $input = request()->all();
        if(isset($input['prefix']) && !empty($input['prefix'])){
            $data = CarrierPop::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->orderBy('sort','desc')->get();
        } else{
            $data = CarrierPop::where('carrier_id',$this->carrier->id)->orderBy('sort','desc')->get();
        }

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }
        
        foreach ($data as $key => &$value) {
            $value->multiple_name = $carrierPreFixDomainArr[$value->prefix];
        }
        
        return returnApiJson('操作成功', 1,$data);
    }

    public function popSave($id=0) 
    {
        if($id) {
            $carrierPop = CarrierPop::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
            if(!$carrierPop) {
                return returnApiJson('对不起, 此弹窗内容不存在', 0);
            }
        } else {
            $carrierPop = new CarrierPop();
        }

        $res = $carrierPop->popSave($this->carrierUser,$this->carrier);

        if($res===true) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function popChangeStatus($id=0) 
    {
        $carrierPop = CarrierPop::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if(!$carrierPop) {
            return returnApiJson('对不起, 此弹窗内容不存在', 0);
        }

        $carrierPop->status = $carrierPop->status ? 0:1;
        $carrierPop->save();

        return returnApiJson('操作成功', 1);
    }

    public function popDelete($id)
    {
        $carrierPop = CarrierPop::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if(!$carrierPop) {
            return returnApiJson('对不起, 此弹窗内容不存在', 0);
        }

        $carrierPop->delete();

        return returnApiJson('操作成功', 1);
    }
    public function waterQuery()
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return returnApiJson('对不起, 站点不能为空', 0);
        }

        $query = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'),'player_id','prefix')->where('carrier_id',$this->carrier->id)->where('whether_recharge',1)->where('prefix',$input['prefix']);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('player_id',$input['player_id']);
        }

        if(isset($input['parent_id']) && !empty($input['parent_id'])){
            $query->where('parent_id',$input['parent_id']);
        }

        if(isset($input['rid']) && !empty($input['rid'])){
            $player = Player::where('player_id',$input['rid'])->where('prefix',$input['prefix'])->first();
            if(!$player){
                return $this->returnApiJson('操作成功', 1,['item' => [], 'total' => 0, 'currentPage' => 1, 'totalPage' => 0]);
            } else{
                $query->where('rid','like',$player->rid.'|%');
            }
        }

        if(isset($input['performance']) && is_numeric($input['performance']) && $input['performance']>0){
            $query->having(\DB::raw('sum(agent_process_available_bet_amount)'),'>=',$input['performance']);
        }

        $query->groupBy('player_id')->orderBy('agent_process_available_bet_amount','desc');

        $total            = count($query->get());
        $item             = $query->skip($offset)->take($pageSize)->get();

        $playerIds        = [];
        foreach ($item as $key => $value) {
            $playerIds[]  = $value->player_id;
        }

        $players     = Player::whereIn('player_id',$playerIds)->get();
        $playerNames = [];

        foreach ($players as $key => $value) {
            $playerNames[$value->player_id] = $value->user_name;
        }

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }
        
        foreach ($item as $k => &$v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
            $v->user_name     = $playerNames[$v->player_id];
        }

        return returnApiJson('操作成功', 1,['item' => $item, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ]);

    }

    public function gameMonitor($playerId)
    {
        $player = Player::where('player_id',$playerId)->first();

        if(!$player){
            return returnApiJson('对不起, 此用户不存在', 0);
        }

        $playerBetFlows = PlayerBetFlow::select('game_name','game_id',\DB::raw('count(game_id) as number'),\DB::raw('sum(company_win_amount) as company_win_amount'),\DB::raw('sum(available_bet_amount) as available_bet_amount'))->where('player_id',$playerId)->where('whether_recharge',1)->groupBy('game_id')->get();

        $gameIds = [];
        foreach ($playerBetFlows as $key => $value) {
            $gameIds[] = $value->game_id;
        }

        $games         = Game::whereIn('game_id',$gameIds)->get();
        $gamePlatNames = [];
        foreach ($games as $key => $value) {
            $gamePlatNames[$value->game_id] = $value->main_game_plat_code;
        }

        foreach ($playerBetFlows as $k => &$v) {
            $v->main_game_plat_code = $gamePlatNames[$v->game_id];
        }

        return returnApiJson('操作成功', 1,$playerBetFlows);
    }

    public function clearPerformance($playerId)
    {
        $input  = request()->all();
        $player = Player::where('player_id',$playerId)->first();

        if(!$player){
            return returnApiJson('对不起, 此用户不存在', 0);
        }

        if(!isset($input['day']) || empty($input['day']) || !strtotime($input['day'])){
            return returnApiJson('对不起, 日期取值不正确', 0);
        }

        PlayerBetFlowMiddle::where('player_id',$playerId)->where('day',date('Ymd',strtotime($input['day'])))->update(['agent_process_available_bet_amount'=>0]);
        return returnApiJson('操作成功', 1);
    }

    public function thirdWalletSetname($id=0)
    {
        $input          = request()->all();
        $playerWithdraw = PlayerWithdraw::where('id',$id)->first();
        if(!$playerWithdraw){
            return returnApiJson('对不起，此条数据不存在', 0);
        }

        if(!in_array($playerWithdraw->type,[3,4,6,7,8,9,10,11,12])){
            return returnApiJson('对不起，不是钱包无需设置', 0);
        }

        if(!isset($input['name']) || empty($input['name'])){
            return returnApiJson('对不起，姓名不能为空', 0);
        }

        $digitalAddressLib = DigitalAddressLib::where('type',$playerWithdraw->type)->where('address',$playerWithdraw->player_digital_address)->first();
        if($digitalAddressLib){
            return returnApiJson('对不起，此钱包已设置过姓名', 0);
        } else{
            $digitalAddressLib            = new DigitalAddressLib();
            $digitalAddressLib->address   = $playerWithdraw->player_digital_address;
            $digitalAddressLib->type      = $playerWithdraw->type;
            $digitalAddressLib->name      = trim($input['name']);
            $digitalAddressLib->save();

            return returnApiJson('操作成功', 1);
        }
    }

    public function withdrawChangeSuspend($id)
    {
        $playerWithdraw = PlayerWithdraw::where('id',$id)->first();
        if(!$playerWithdraw){
            return returnApiJson('对不起，此订单不存在', 1);
        } else{
            $playerWithdraw->is_suspend = $playerWithdraw->is_suspend ? 0:1;
            $playerWithdraw->save();
            return returnApiJson('操作成功', 1);
        }
    }

    public function domainAdd()
    {
        $input = request()->all();
        if(!isset($input['domain']) || empty($input['domain'])){
            return returnApiJson('对不起，域名不能为空', 1);
        }

        $domain         = new Domain();
        $domain->domain = $input['domain'];
        $domain->save();

        return returnApiJson('操作成功', 1);
    }

    public function domainlist()
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        $query            = Domain::orderBy('id','desc');

        if(isset($input['domain']) && !empty($input['domain'])){
            $query->where('domain',$input['domain']);
        }

        $total            = $query->count();
        $item             = $query->skip($offset)->take($pageSize)->get();

        return returnApiJson('操作成功', 1,['item' => $item, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ]);
    }

    public function domainDel($id)
    {
        $existDomain = Domain::where('id',$id)->first();
        if($existDomain){
            PlayerInviteCode::where('domain',$existDomain->domain)->update(['domain'=>'']);
            $existDomain->delete();
            return returnApiJson('操作成功', 1);
        } else{
            return returnApiJson('对不起，此条数据不存在', 0);
        }
    }

    public function allDomain()
    {
        $usedDomains    = PlayerInviteCode::where('domain','!=','')->pluck('domain')->toArray();
        $allDomains     = Domain::pluck('domain')->toArray();
        $domains        = array_diff($allDomains,$usedDomains);

       return returnApiJson('操作成功', 1,['domains'=>$domains]);
    }

    public function batchBankcardBackList()
    {
        $input = request()->all();
        if(!isset($input['playerIds']) || !is_array($input['playerIds']) ){
            return returnApiJson('对不起，用户参数不正确', 0);
        }

        $insertData      = [];
        $playerBankCards = PlayerBankCard::select('inf_player_bank_cards.*','def_bank.bank_name')->whereIn('player_id',$input['playerIds'])->leftJoin('def_bank','def_bank.id','=','inf_player_bank_cards.bank_Id')->get();
        $arbitrageBank   = ArbitrageBank::pluck('card_account')->toArray();
        foreach ($playerBankCards as $key => $value) {
            if(!in_array($value->card_account, $arbitrageBank)){
                $row                        = [];
                $row['bank_name']           = $value->bank_name;
                $row['card_owner_name']     = $value->card_owner_name;
                $row['card_account']        = $value->card_account;
                $row['created_at']          = date('Y-m-d H:i:s');
                $row['updated_at']          = date('Y-m-d H:i:s');
                $insertData[]               = $row;
            }
        }

        if(count($insertData)){
            \DB::table('def_arbitrage_bank')->insert($insertData);
        }

        return returnApiJson('操作成功', 1);
    }

    public function showPlayerearnings($id=0)
    {
        $player = Player::where('player_id',$id)->first();
        if(!$player){
            return returnApiJson('对不起，此用户不存在', 0);
        }

        if($player->win_lose_agent==0){
            return returnApiJson('对不起，此用户不是代理', 0);
        }

        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'player_dividends_method',$player->prefix);
        $result                = '';

        switch ($playerDividendsMethod) {
            case '1':
                $result = DevidendMode1::calculateDividend($player,null,null,1);
                break;
            case '2':
                $result = DevidendMode2::calculateDividend($player);
                break;
            case '3':
                $result = DevidendMode3::calculateDividend($player);
                break;
            case '5':
                $result = DevidendMode5::calculateDividend($player);
                break;
            case '4':
                $result = DevidendMode4::calculateDividend($player);
                break;
            
            default:
                // code...
                break;
        }
        return returnApiJson('操作成功', 1,$result);
    }

    public function againGetBetflow()
    {
        $input = request()->all();
        if(!isset($input['startTime']) || !strtotime($input['startTime'])){
            return returnApiJson('对不起，开始时间取值不正确', 0);
        }

        if(!isset($input['endTime']) || !strtotime($input['endTime'])){
            return returnApiJson('对不起，结束时间取值不正确', 0);
        }

        if(strtotime($input['endTime'])-strtotime($input['startTime'])>3600){
            return returnApiJson('对不起，时间区间不能超过1小时', 0);
        }

        if(time()-strtotime($input['startTime'])>864000){
            return returnApiJson('对不起，不能抓取10天前的数据', 0);
        }

        $carrier = Carrier::first();
        if(!is_null($carrier) && !empty($carrier->apiusername)){
            $game     = new Games($carrier,null);
            $carriers = Carrier::pluck('apiUsername')->toArray();

            for($i=strtotime($input['startTime']);$i<strtotime($input['endTime']);$i+=600){
                $game->getBetTimeRecord($carriers,$i);
                \Log::info('手动补单拉单时间的值是'.date('Y-m-d H:i:s',$i));
            }
            return returnApiJson('操作成功', 1);
        } else{
            return returnApiJson('对不起，未设置API接口参数', 0);
        }
    }

    public function batchSendGift()
    {
        $input = request()->all();
        if(!isset($input['prefix']) || empty($input['prefix'])){
            return returnApiJson('对不起，站点取值不正确', 0);
        }

        if(!isset($input['gift']) || !is_numeric($input['gift']) || $input['gift'] <= 0){
            return returnApiJson('对不起，礼金取值不正确', 0);
        }

        if(!isset($input['turnover_limit']) || !is_numeric($input['turnover_limit']) || $input['turnover_limit'] < 0  ){
            return returnApiJson('对不起，流水限制取值不正确', 0);
        }

        if(!isset($input['remark']) || empty($input['remark'])){
            return returnApiJson('对不起，备注不能为空', 0);
        }

        if(!array_key_exists('game_category',$input)){
            return returnApiJson('对不起，流水限制分类取值不正确', 0);
        }

        if(empty($input['game_category'])){
            $input['game_category'] = 0;
        }

        if(!isset($input['player_ids']) || !is_array($input['player_ids'])){
            return returnApiJson('对不起，用户取值不正确', 0);
        }

        if(strlen($input['player_ids'][0])==8){
            $playerIdsArr = Player::whereIn('player_id',$input['player_ids'])->where('prefix',$input['prefix'])->pluck('player_id')->toArray();
        } else{
            $playerIdsArr = Player::whereIn('extend_id',$input['player_ids'])->where('prefix',$input['prefix'])->pluck('player_id')->toArray();
        }

        //发了注册彩金的不能发
        $registerPlayerIdsArr     = PlayerTransfer::whereIn('player_id',$playerIdsArr)->where('type','register_gift')->pluck('player_id')->toArray();
        $playerIdsArr             = array_diff($playerIdsArr,$registerPlayerIdsArr);

        //有个充值记录的不能发
        $rechargePlayerIdsArr     = PlayerTransfer::whereIn('player_id',$playerIdsArr)->where('type','recharge')->pluck('player_id')->toArray();
        $playerIdsArr             = array_diff($playerIdsArr,$rechargePlayerIdsArr);

        foreach ($playerIdsArr as $key => $value) {

            //未绑银行卡与未绑支付宝不发放
            $existPlayerBankCard = PlayerBankCard::where('player_id',$value)->first();
            $existPlayerAlipay   = PlayerAlipay::where('player_id',$value)->first();
            if(!$existPlayerBankCard && !$existPlayerAlipay){
                continue;
            }
            
            $cacheKey = "player_" .$value;
            $redisLock = Lock::addLock($cacheKey,10);

            if (!$redisLock) {
                \Log::info('批量发放代理扶持加锁加锁失败,用户是'.$value);
                return returnApiJson('批量发放代理扶持加锁失败用户是'.$value, 0);
            } else {
                try {
                    \DB::beginTransaction();
                    $playerAccount                                   = PlayerAccount::where('player_id',$value)->lockForUpdate()->first();
                                        
                    $playerTransfer                                  = new PlayerTransfer();
                    $playerTransfer->prefix                          = $input['prefix'];
                    $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                    $playerTransfer->rid                             = $playerAccount->rid;
                    $playerTransfer->top_id                          = $playerAccount->top_id;
                    $playerTransfer->parent_id                       = $playerAccount->parent_id;
                    $playerTransfer->player_id                       = $playerAccount->player_id;
                    $playerTransfer->is_tester                       = $playerAccount->is_tester;
                    $playerTransfer->level                           = $playerAccount->level;
                    $playerTransfer->user_name                       = $playerAccount->user_name;
                    $playerTransfer->mode                            = 1;
                    $playerTransfer->type                            = 'agent_support';
                    $playerTransfer->type_name                       = '代理扶持';
                    $playerTransfer->day_m                           = date('Ym',time());
                    $playerTransfer->day                             = date('Ymd',time());
                    $playerTransfer->amount                          = $input['gift']*10000;
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                    $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                    $playerTransfer->remark                          = $input['remark'];
                    $playerTransfer->save();

                    $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
                    $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                    $playerWithdrawFlowLimit->top_id                 = $playerAccount->top_id;
                    $playerWithdrawFlowLimit->parent_id              = $playerAccount->parent_id;
                    $playerWithdrawFlowLimit->rid                    = $playerAccount->rid;
                    $playerWithdrawFlowLimit->player_id              = $playerAccount->player_id;
                    $playerWithdrawFlowLimit->user_name              = $playerAccount->user_name;
                    $playerWithdrawFlowLimit->limit_type             = 5;

                    if($input['game_category']!=0){
                        $playerWithdrawFlowLimit->betflow_limit_category = $input['game_category'];
                    } 

                    $playerWithdrawFlowLimit->limit_amount           = $input['turnover_limit']*10000;
                    $playerWithdrawFlowLimit->complete_limit_amount  = 0;
                    $playerWithdrawFlowLimit->is_finished            = 0;
                    $playerWithdrawFlowLimit->operator_id            = 0;
                    $playerWithdrawFlowLimit->save();

                    $playerAccount->balance                          = $playerTransfer->balance;
                    $playerAccount->save();
                    \DB::commit();
                    Lock::release($redisLock);
                } catch (\Exception $e) {
                    \DB::rollback();
                    Lock::release($redisLock);
                    Clog::recordabnormal('批量发放代理扶持异常'.'用户是'.$value.'异常是'.$e->getMessage());   
                    return returnApiJson('批量发放代理扶持异常'.'用户是'.$value.'异常是'.$e->getMessage(), 0);
                }
            }   
        }

        return returnApiJson('操作成功', 1);
    }

    public function createBetflow($id)
    {
        $input  = request()->all();
        $player = Player::where('player_id',$id)->first();
        if(!$player){
            return returnApiJson('对不起，此用户不存在', 0);
        }

        $isBetFlowConvert                    = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'is_bet_flow_convert',$player->prefix);
        $electronicBetflowCalculateRate      = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'electronic_betflow_calculate_rate',$player->prefix);
        $enabelAgentCommissionflowSingle     = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'enabel_agent_commissionflow_single',$player->prefix);
        $agentElectronicBetflowCalculateRate = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'agent_electronic_betflow_calculate_rate',$player->prefix);

        if(!isset($input['win_amount']) || empty($input['win_amount']) || $input['win_amount']==0){
            return returnApiJson('对不起，公司输赢不能为空', 0);
        }

        if($player->is_hedging_account && $input['win_amount']<0){
            return returnApiJson('对不起，对冲号游戏输赢必须为正', 0);
        }

        //对冲号处理
        if($player->is_hedging_account){
            try {
                \DB::beginTransaction();
                $playAccount                                = PlayerAccount::where('player_id',$player->player_id)->lockForUpdate()->first();

                $playerTransefer                            = new PlayerTransfer();
                $playerTransefer->prefix                    = $player->prefix;
                $playerTransefer->carrier_id                = $player->carrier_id;
                $playerTransefer->rid                       = $player->rid;
                $playerTransefer->top_id                    = $player->top_id;
                $playerTransefer->parent_id                 = $player->parent_id;
                $playerTransefer->player_id                 = $player->player_id;
                $playerTransefer->is_tester                 = $player->is_tester;
                $playerTransefer->user_name                 = $player->user_name;
                $playerTransefer->level                     = $player->level;
                $playerTransefer->platform_id               = 17;
                $playerTransefer->mode                      = 2;
                $playerTransefer->type                      = 'casino_transfer_out';
                $playerTransefer->type_name                 = '转出中心钱包';
                $playerTransefer->project_id                = '';
                $playerTransefer->day_m                     = date('Ym');
                $playerTransefer->day                       = date('Ymd');
                $playerTransefer->amount                    = $playAccount->balance;
                $playerTransefer->before_balance            = $playAccount->balance;
                $playerTransefer->balance                   = $playAccount->balance - $playerTransefer->amount;
                $playerTransefer->before_frozen_balance     = $playAccount->frozen;
                $playerTransefer->frozen_balance            = $playAccount->frozen;
                $playerTransefer->save();

                $playerBetFlowMiddle                                           = new PlayerBetFlowMiddle();
                $playerBetFlowMiddle->player_id                                = $player->player_id;
                $playerBetFlowMiddle->carrier_id                               = $player->carrier_id;
                $playerBetFlowMiddle->rid                                      = $player->rid;
                $playerBetFlowMiddle->parent_id                                = $player->parent_id;                    
                $playerBetFlowMiddle->game_category                            = 2;

                if($isBetFlowConvert){
                    $playerBetFlowMiddle->bet_amount                               = 7*$input['win_amount']*bcdiv($electronicBetflowCalculateRate,100,2);
                    $playerBetFlowMiddle->available_bet_amount                     = 7*$input['win_amount']*bcdiv($electronicBetflowCalculateRate,100,2);
                    $playerBetFlowMiddle->process_available_bet_amount             = 7*$input['win_amount']*bcdiv($electronicBetflowCalculateRate,100,2);
                } else{
                    $playerBetFlowMiddle->bet_amount                               = 7*$input['win_amount'];
                    $playerBetFlowMiddle->available_bet_amount                     = 7*$input['win_amount'];
                    $playerBetFlowMiddle->process_available_bet_amount             = 7*$input['win_amount'];
                }

                $playerBetFlowMiddle->main_game_plat_id                        = 17;
                $playerBetFlowMiddle->company_win_amount                       = -$input['win_amount'];
                $playerBetFlowMiddle->number                                   = bcdiv($input['win_amount'],1,0)+rand(1,50);
                $playerBetFlowMiddle->day                                      = date('Ymd');
                $playerBetFlowMiddle->bet_time                                 = time()+60;
                $playerBetFlowMiddle->stat_time                                = 0;
                $playerBetFlowMiddle->whether_recharge                         = 1;
                $playerBetFlowMiddle->is_live_streaming_account                = 0;
                $playerBetFlowMiddle->prefix                                   = $player->prefix;
                $playerBetFlowMiddle->win_lose_agent                           = 0;

                if($enabelAgentCommissionflowSingle){
                    $playerBetFlowMiddle->agent_process_available_bet_amount   = bcdiv($playerBetFlowMiddle->process_available_bet_amount*$agentElectronicBetflowCalculateRate,100,2);
                } else{
                    $playerBetFlowMiddle->agent_process_available_bet_amount   = $playerBetFlowMiddle->process_available_bet_amount;
                }
                
                $playerBetFlowMiddle->bet_flow_ids                             = '';
                $playerBetFlowMiddle->save();

                $playerTransefer1                            = new PlayerTransfer();
                $playerTransefer1->prefix                    = $player->prefix;
                $playerTransefer1->carrier_id                = $player->carrier_id;
                $playerTransefer1->rid                       = $player->rid;
                $playerTransefer1->top_id                    = $player->top_id;
                $playerTransefer1->parent_id                 = $player->parent_id;
                $playerTransefer1->player_id                 = $player->player_id;
                $playerTransefer1->is_tester                 = $player->is_tester;
                $playerTransefer1->user_name                 = $player->user_name;
                $playerTransefer1->level                     = $player->level;
                $playerTransefer1->platform_id               = 17;
                $playerTransefer1->mode                      = 1;
                $playerTransefer1->type                      = 'casino_transfer_in';
                $playerTransefer1->type_name                 = '转入中心钱包';
                $playerTransefer1->project_id                = '';
                $playerTransefer1->day_m                     = date('Ym');
                $playerTransefer1->day                       = date('Ymd');
                $playerTransefer1->balance                   = $playAccount->balance + $input['win_amount']*10000;
                $playerTransefer1->amount                    = $playerTransefer1->balance;
                $playerTransefer1->before_balance            = $playerTransefer->balance;
                $playerTransefer1->before_frozen_balance     = $playAccount->frozen;
                $playerTransefer1->frozen_balance            = $playAccount->frozen;
                $playerTransefer1->save();

                $playAccount->balance = $playerTransefer1->balance;
                $playAccount->save();

                \DB::commit();
                return returnApiJson('操作成功', 1);
            } catch (\Exception $e) {
                \DB::rollback();
                Clog::recordabnormal('手动生成投注数据异常'.$e->getMessage());   
                return returnApiJson('操作异常'.$e->getMessage(), 0);
            }
        } else{
            try {
                \DB::beginTransaction();
                $playerBetFlowMiddle                                           = new PlayerBetFlowMiddle();
                $playerBetFlowMiddle->player_id                                = $player->player_id;
                $playerBetFlowMiddle->carrier_id                               = $player->carrier_id;
                $playerBetFlowMiddle->rid                                      = $player->rid;
                $playerBetFlowMiddle->parent_id                                = $player->parent_id;                    
                $playerBetFlowMiddle->game_category                            = 2;

                if($isBetFlowConvert){
                    $playerBetFlowMiddle->bet_amount                               = 7*abs($input['win_amount'])*bcdiv($electronicBetflowCalculateRate,100,2);
                    $playerBetFlowMiddle->available_bet_amount                     = 7*abs($input['win_amount'])*bcdiv($electronicBetflowCalculateRate,100,2);
                    $playerBetFlowMiddle->process_available_bet_amount             = 7*abs($input['win_amount'])*bcdiv($electronicBetflowCalculateRate,100,2);
                } else{
                    $playerBetFlowMiddle->bet_amount                               = 7*abs($input['win_amount']);
                    $playerBetFlowMiddle->available_bet_amount                     = 7*abs($input['win_amount']);
                    $playerBetFlowMiddle->process_available_bet_amount             = 7*abs($input['win_amount']);
                }

                $playerBetFlowMiddle->main_game_plat_id                        = 17;
                $playerBetFlowMiddle->company_win_amount                       = -$input['win_amount'];
                $playerBetFlowMiddle->number                                   = bcdiv($input['win_amount'],1,0)+rand(1,50);
                $playerBetFlowMiddle->day                                      = date('Ymd');
                $playerBetFlowMiddle->bet_time                                 = time()+60;
                $playerBetFlowMiddle->stat_time                                = 0;
                $playerBetFlowMiddle->whether_recharge                         = 1;
                $playerBetFlowMiddle->is_live_streaming_account                = 0;
                $playerBetFlowMiddle->prefix                                   = $player->prefix;
                $playerBetFlowMiddle->win_lose_agent                           = 0;

                if($enabelAgentCommissionflowSingle){
                    $playerBetFlowMiddle->agent_process_available_bet_amount   = bcdiv($playerBetFlowMiddle->process_available_bet_amount*$agentElectronicBetflowCalculateRate,100,2);
                } else{
                    $playerBetFlowMiddle->agent_process_available_bet_amount   = $playerBetFlowMiddle->process_available_bet_amount;
                }
                
                $playerBetFlowMiddle->bet_flow_ids                             = '';
                $playerBetFlowMiddle->save();

                \DB::commit();
                return returnApiJson('操作成功', 1);
            } catch (\Exception $e) {
                \DB::rollback();
                Clog::recordabnormal('手动生成投注数据异常1'.$e->getMessage());   
                return returnApiJson('操作异常'.$e->getMessage(), 0);
            }
        }
    }
 
    public function firstDepositWithdrawal($id)
    {
        $playerWithdraw   = PlayerWithdraw::where('id',$id)->first();

        if(!$playerWithdraw){
            return returnApiJson('对不起，此条数据不存在', 0);
        }
        $oneAndOneRecharge_amount   = CarrierCache::getCarrierMultipleConfigure($playerWithdraw->carrier_id,'one_and_one_recharge_amount',$playerWithdraw->prefix);
        $oneAndOneWithdrawal_amount = CarrierCache::getCarrierMultipleConfigure($playerWithdraw->carrier_id,'one_and_one_withdrawal_amount',$playerWithdraw->prefix);
        $playerDividendsMethod      = CarrierCache::getCarrierMultipleConfigure($playerWithdraw->carrier_id,'player_dividends_method',$playerWithdraw->prefix);
        $firstDepositActivityPlus   = CarrierCache::getCarrierMultipleConfigure($playerWithdraw->carrier_id,'first_deposit_activity_plus',$playerWithdraw->prefix);

        $data                       = [];
        $data['rechargeAmount']     = $oneAndOneRecharge_amount;
        $data['withdrawAmount']     = $oneAndOneWithdrawal_amount;
        if($oneAndOneRecharge_amount==0){
            $data['withdrawRate'] = 0;
        } else{
            $data['withdrawRate']       = bcdiv($oneAndOneWithdrawal_amount,$oneAndOneRecharge_amount,2)*100;
        }
        if($playerDividendsMethod==5 && $playerWithdraw->is_agent==1){
            $activityAmount =  PlayerDepositPayLog::where('player_id',$playerWithdraw->player_id)->where('status',1)->where('activityids',$firstDepositActivityPlus)->sum('amount');
            $sonAmount      =  PlayerDepositPayLog::where('parent_id',$playerWithdraw->player_id)->where('status',1)->where('is_agent',0)->where('activityids',$firstDepositActivityPlus)->sum('amount');
            $activityAmount += $sonAmount;
        } else{
            $activityAmount =  PlayerDepositPayLog::where('parent_id',$playerWithdraw->parent_id)->where('status',1)->where('activityids',$firstDepositActivityPlus)->sum('amount');
        }
        
        if($oneAndOneRecharge_amount ==0){
            $data['activityRate'] = 0;
        } else{
            $data['activityRate'] = bcdiv($activityAmount,$oneAndOneRecharge_amount,2);
        }
       
        return returnApiJson('操作成功', 1,$data);
    }

    public function createAccountAssociate()
    {
        $input = request()->all();
        if(isset($input['player_ids']) && !empty($input['player_ids'])){
            $playerIds = explode(',', $input['player_ids']);
            if(count($playerIds)!=2){
                return returnApiJson('对不起，用户ID不正确', 0);
            }

            if(strlen(trim($playerIds[0]))!=8 || strlen(trim($playerIds[1]))!=8){
                return returnApiJson('对不起，用户ID不正确，证输入8位数用户ID', 0);
            } else{
                $playerLogin              = PlayerLogin::where('player_id',trim($playerIds[0]))->first();
                $player                   = Player::where('player_id',trim($playerIds[1]))->first();
                if(!$player){
                    return returnApiJson('对不起，用户ID不正确，证输入8位数用户ID', 0);
                }
                if(!$playerLogin){
                    return returnApiJson('对不起，第一个帐号没有登录信息', 0);
                }

                $playerLogin1                  = new PlayerLogin();
                $playerLogin1->player_id       = $player->player_id;
                $playerLogin1->user_name       = $player->user_name;
                $playerLogin1->carrier_id      = $player->carrier_id;
                $playerLogin1->prefix          = $player->prefix;
                $playerLogin1->login_ip        = $playerLogin->login_ip;
                $playerLogin1->login_domain    = $playerLogin->login_domain;
                $playerLogin1->login_time      = $playerLogin->login_time;
                $playerLogin1->login_location  = $playerLogin->login_location;
                $playerLogin1->osName          = $playerLogin->osName;
                $playerLogin1->fingerprint     = $playerLogin->fingerprint;
                $playerLogin1->save();

                return returnApiJson('操作成功', 1);
            }
        }
    }

    public function autoDividendDistribution($playerId)
    {
        $player = Player::where('player_id',$playerId)->first();
        if(!$player){
            return returnApiJson('对不起，此用户不存在', 0);
        }
        $player->is_auto_dividend = $player->is_auto_dividend ? 0:1;
        $player->save();

        return returnApiJson('操作成功', 1);
    }

    public function fraudRecharge($id)
    {
        $player = Player::where('player_id',$id)->first();
        if(!$player){
            return returnApiJson('对不起，此用户不存在', 0);
        }

        $player->frozen_status = 4;
        $player->remark        = 'P图骗单';
        $player->save();

        $loginIps        = PlayerLogin::where('player_id',$id)->pluck('login_ip')->toArray();
        $fingerPrints    = PlayerLogin::where('player_id',$id)->pluck('fingerprint')->toArray();
        $allLoginIps     = PlayerLogin::pluck('login_ip')->toArray();
        $allFingerPrints = PlayerLogin::pluck('fingerprint')->toArray();
        $loginIps        = array_diff($loginIps,$allLoginIps);
        $fingerPrints    = array_diff($fingerPrints,$allFingerPrints);

        $data         = [];

        foreach ($loginIps as $key => $value) {
            if(!empty($value)){
                $row               = [];
                $row['ip']         = $value;
                $row['type']       = 1;
                $row['created_at'] = date('Y-m-d H:i:s');
                $row['updated_at'] = date('Y-m-d H:i:s');
                $data[]            = $row;
            }
        }

        foreach ($fingerPrints as $key => $value) {

            if(!empty($value)){
                $row               = [];
                $row['fingerprint']= $value;
                $row['type']       = 2;
                $row['created_at'] = date('Y-m-d H:i:s');
                $row['updated_at'] = date('Y-m-d H:i:s');
                $data[]            = $row;
            }
        }

        \DB::table('log_fraud_recharge')->insert($data);

        return returnApiJson('操作成功', 1);
    }

    public function noticeList()
    {
        $input = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = CarrierNotice::where('carrier_id',$this->carrier->id)->orderBy('sort','desc')->orderBy('updated_at','desc');

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        $total                  = $query->count();
        $data                   = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($data as $k => $v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
        }

        return returnApiJson('操作成功', 1,['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function noticeEdit($id=0)
    {
        $input = request()->all();

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return returnApiJson('对不起，站点不能为空', 0);
        }

        if(!isset($input['title']) || empty($input['title'])){
            return returnApiJson('对不起，标题不能为空', 0);
        }

        if(!isset($input['content']) || empty($input['content'])){
            return returnApiJson('对不起，内容不能为空', 0);
        }

        if(!isset($input['sort']) || !is_numeric($input['sort'])){
            return returnApiJson('对不起，排序取值不正确', 0);
        }

        if($id){
            $carrierNotice = CarrierNotice::where('id',$id)->first();
            if(!$id){
                return returnApiJson('对不起，此条公告不存在', 0);
            }
        } else{
            $carrierNotice             = new CarrierNotice();
        }

        $carrierNotice->prefix     = $input['prefix'];
        $carrierNotice->title      = $input['title'];
        $carrierNotice->content    = $input['content'];
        $carrierNotice->sort       = $input['sort'];
        $carrierNotice->carrier_id = $this->carrier->id;
        $carrierNotice->save();

        return returnApiJson('操作成功', 1);
    }

    public function noticeDel($id)
    {
        $carrierNotice = CarrierNotice::where('id',$id)->first();
        if(!$id){
            return returnApiJson('对不起，此条公告不存在', 0);
        } else{
            $carrierNotice->delete();
        }

        return returnApiJson('操作成功', 1);
    }

    public function changeWthdrawMobileStatus($id)
    {
        $existPlayer = Player::where('player_id',$id)->first();
        if(!$existPlayer){
            return returnApiJson('对不起，此用户不存在', 0);
        }

        $existPlayer->is_withdraw_mobile = $existPlayer->is_withdraw_mobile ? 0:1;
        $existPlayer->save();

        return returnApiJson('操作成功', 1);
    }

    public function currencySettingList()
    {
        $input = request()->all();
        if(!isset($input['currency'])){
            return returnApiJson('对不起，币种不能为空', 0);
        }

        $currencyWebSites = CurrencyWebSite::where('currency',$input['currency'])->groupby('sign')->get()->toArray();

        foreach ($currencyWebSites as $key => &$value) {
            if(in_array($value['sign'], ['third_wallet','disable_withdraw_channel'])){
                $value['value'] = json_decode($value['value'],true);
            }
        }

        return returnApiJson('操作成功', 1,$currencyWebSites);
    }

    public function batchSetWage()
    {
        $input = request()->all();
        if(!isset($input['user_name'])){
            return  returnApiJson('对不起，帐号不能为空', 0);
        }

        if(!isset($input['earnings']) || !is_numeric($input['earnings']) || $input['earnings']<0 || $input['earnings']>=70 || intval($input['earnings'])!=$input['earnings']){
            return  returnApiJson('对不起，分红为空或取值不正确', 0);
        }

        if(!isset($input['guaranteed']) || !is_numeric($input['guaranteed']) || $input['guaranteed']<0 || $input['guaranteed']>=500 || intval($input['guaranteed'])!=$input['guaranteed']){
            return  returnApiJson('对不起，保底为空或取值不正确', 0);
        }

        if(!isset($input['prefix'])){
            return  returnApiJson('对不起，站点不能为空', 0);
        }

        $errorString = '';
        $replaceArr  = ["\n"," "];

        $input['user_name'] = str_replace($replaceArr,',',$input['user_name']);
        $usernames          = explode(',',$input['user_name']);
        foreach ($usernames as $key => $value) {
                if(!$value){
                    continue;
                }

                $userName     = $value.'_'.$input['prefix'];
                $existPlayer  = Player::where('prefix',$input['prefix'])->where('user_name',$userName)->first();

                //正常设置
                if($existPlayer){

                    $playerSetting                     = PlayerSetting::where('user_name',$userName)->first();
                    $playerSetting->earnings           = $input['earnings'];
                    $playerSetting->guaranteed         = $input['guaranteed'];
                    $playerSetting->save();

                    if($playerSetting->earnings > 0 ){
                        PlayerDigitalAddress::where('player_id',$playerSetting->player_id)->update(['win_lose_agent'=>1]);
                        ReportPlayerStatDay::where('player_id',$playerSetting->player_id)->update(['win_lose_agent'=>1]);
                        PlayerBetFlowMiddle::where('player_id',$playerSetting->player_id)->update(['win_lose_agent'=>1]);
                        PlayerDepositPayLog::where('player_id',$playerSetting->player_id)->update(['is_agent'=>1]);
                        Player::where('player_id',$playerSetting->player_id)->update(['win_lose_agent'=>1]);
                        PlayerCache::forgetisWinLoseAgent($playerSetting->player_id);
                    } else{
                        PlayerDigitalAddress::where('player_id',$playerSetting->player_id)->update(['win_lose_agent'=>0]);
                        ReportPlayerStatDay::where('player_id',$playerSetting->player_id)->update(['win_lose_agent'=>0]);
                        PlayerBetFlowMiddle::where('player_id',$playerSetting->player_id)->update(['win_lose_agent'=>0]);
                        PlayerDepositPayLog::where('player_id',$playerSetting->player_id)->update(['is_agent'=>0]);
                        Player::where('player_id',$playerSetting->player_id)->update(['win_lose_agent'=>0]);
                        PlayerCache::forgetisWinLoseAgent($playerSetting->player_id);
                    }

                    PlayerCache::forgetPlayerSetting($playerSetting->player_id);

                } else{
                    $errorString=$errorString.'|'.$value;
                }
            
        }

        if(empty($errorString)){
            return  returnApiJson('操作成功', 1);
        } else{
            return  returnApiJson('对不起，此部分数据设置失败'.$errorString, 0);
        }
    }

    public function currencySettingSave()
    {
        $input = request()->all();
        if(!isset($input['currency'])){
            return  returnApiJson('对不起，币种取值不正常', 1);
        } 

        if(!isset($input['digital_rate']) || $input['digital_rate']<=0){
            return  returnApiJson('对不起，存款数字币汇率取值不正常', 1);
        }

        if(!isset($input['withdraw_digital_rate']) || $input['withdraw_digital_rate']<=0){
            return  returnApiJson('对不起，取款数字币汇率取值不正常', 1);
        }

        if(!isset($input['in_r_out_u']) || $input['in_r_out_u']<=0){
            return  returnApiJson('对不起，进人民币出U取值不正常', 1);
        }

        if(!isset($input['in_t_out_u']) || $input['in_t_out_u']<=0){
            return  returnApiJson('对不起，存钱包出U取值不正常', 1);
        }

        $prefixs = CarrierPreFixDomain::where('currency',$input['currency'])->pluck('prefix')->toArray();

        CurrencyWebSite::where('currency',$input['currency'])->where('sign','digital_rate')->update(['value'=>$input['digital_rate']]);
        CarrierMultipleFront::whereIn('prefix',$prefixs)->where('sign','digital_rate')->update(['value'=>$input['digital_rate']]);
        
        CurrencyWebSite::where('currency',$input['currency'])->where('sign','withdraw_digital_rate')->update(['value'=>$input['withdraw_digital_rate']]);
        CarrierMultipleFront::whereIn('prefix',$prefixs)->where('sign','withdraw_digital_rate')->update(['value'=>$input['withdraw_digital_rate']]);

        CurrencyWebSite::where('currency',$input['currency'])->where('sign','in_r_out_u')->update(['value'=>$input['in_r_out_u']]);
        CarrierMultipleFront::whereIn('prefix',$prefixs)->where('sign','in_r_out_u')->update(['value'=>$input['in_r_out_u']]);

        CurrencyWebSite::where('currency',$input['currency'])->where('sign','in_t_out_u')->update(['value'=>$input['in_t_out_u']]);
        CarrierMultipleFront::whereIn('prefix',$prefixs)->where('sign','in_t_out_u')->update(['value'=>$input['in_t_out_u']]);

        if(isset($input['third_wallet']) && is_array($input['third_wallet'])){
            CurrencyWebSite::where('currency',$input['currency'])->where('sign','third_wallet')->update(['value'=>json_encode($input['third_wallet'])]);
            CarrierMultipleFront::whereIn('prefix',$prefixs)->where('sign','third_wallet')->update(['value'=>json_encode($input['third_wallet'])]);
        } else{
            CurrencyWebSite::where('currency',$input['currency'])->where('sign','third_wallet')->update(['value'=>json_encode([])]);
            CarrierMultipleFront::whereIn('prefix',$prefixs)->where('sign','third_wallet')->update(['value'=>json_encode([])]);
        }

        if(isset($input['disable_withdraw_channel']) && is_array($input['disable_withdraw_channel'])){
            CurrencyWebSite::where('currency',$input['currency'])->where('sign','disable_withdraw_channel')->update(['value'=>json_encode($input['disable_withdraw_channel'])]);
            CarrierMultipleFront::whereIn('prefix',$prefixs)->where('sign','disable_withdraw_channel')->update(['value'=>json_encode($input['disable_withdraw_channel'])]);
        } else{
            CurrencyWebSite::where('currency',$input['currency'])->where('sign','disable_withdraw_channel')->update(['value'=>json_encode([])]);
            CarrierMultipleFront::whereIn('prefix',$prefixs)->where('sign','disable_withdraw_channel')->update(['value'=>json_encode([])]);
        }

        CarrierCache::flushCarrierConfigure($this->carrier->id);

        return returnApiJson('操作成功', 1);
    }

    public function allGameline()
    {
        $gameLines = GameLine::orderBy('rate','asc')->get()->toArray();

        return returnApiJson('操作成功', 1,$gameLines);
    }
}