<?php

namespace App\Http\Controllers\Web;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\BaseController;
use App\Lib\Cache\CarrierCache;
use App\Jobs\TelegramJob;
use App\Models\Def\PayChannel;
use App\Models\CarrierBankCardType;
use App\Models\Player;
use App\Models\PlayerIpBlack;
use App\Models\LanguageHashVersion;
use App\Models\CarrierActivityGiftCode;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use App\Models\CarrierQuestions;
use App\Models\CarrierImage;
use App\Models\Def\Game as Games;
use App\Models\CarrierHorizontalMenu;
use App\Models\CarrierGuaranteed;
use App\Models\PlayerAccount;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\PlayerReceiveGiftCenter;
use App\Models\Log\PlayerGiftCode;
use App\Models\PlayerBankCard;
use App\Models\PlayerDigitalAddress;
use App\Models\PlayerHoldGiftCode;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerLogin;
use App\Lib\Cache\GameCache;
use App\Models\Conf\PlayerSetting;
use App\Models\Conf\CarrierMultipleFront;
use App\Lib\Cache\Lock;
use App\Models\Def\Development;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\PlayerInviteCode;
use App\Lib\Cache\PlayerCache;
use App\Lib\Clog;
use App\Models\Log\PlayerFingerprint;
use App\Models\Log\BankStat;
use App\Models\Log\AlipayStat;
use App\Models\CarrierNotice;
use App\Models\PlayerAlipay;

class SystemController extends BaseController
{
    use Authenticatable;

    // 登录
    public function getAgent()
    {
        $data       = config('main')['addReduceList'];

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function banktypeList()
    {
        $currency = CarrierCache::getCurrencyByPrefix($this->prefix);
        $data     = CarrierBankCardType::where('carrier_id',$this->carrier->id)->where('currency',$currency)->get();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function init()
    {
        $input            = request()->all();
        $playerIpBlacks   = PlayerIpBlack::select('ips')->where('carrier_id',$this->carrier->id)->first();
        $roundLogo        = CarrierImage::where('carrier_id',$this->carrier->id)->where('image_category_id',21)->first();

        if(!empty($playerIpBlacks->ips)){
            $ipblackArr  = explode(',',$playerIpBlacks->ips);
        } else {
            $ipblackArr = [];
        }
        
        $mainDomain = '';

        $navigationLogo          = CarrierImage::where('carrier_id',$this->carrier->id)->where('image_category_id',24)->first();
        $openSignIn              = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'open_sign_in',$this->prefix);
        $supportLanguage         = CarrierCache::getCarrierConfigure($this->carrier->id, 'supportMemberLangMap');
        $supportLanguageArr      = explode(',', $supportLanguage);
        $supportLanguageKeyValue = [];
        $currency                = CarrierCache::getCurrencyByPrefix($this->prefix);

        foreach ($supportLanguageArr as $key => $value) {
            switch ($value) {
                case 'zh-cn':
                    $supportLanguageKeyValue[] = ['code'=>'zh-cn','name'=>'简体中文'];
                    break;
                case 'en':
                    $supportLanguageKeyValue[] = ['code'=>'en','name'=>'English'];
                    break;
                case 'vi':
                    $supportLanguageKeyValue[] = ['code'=>'vi','name'=>'Tiếng Việt'];
                    break;
                case 'th':
                    $supportLanguageKeyValue[] = ['code'=>'th','name'=>'ไทย'];
                    break;
                case 'id':
                    $supportLanguageKeyValue[] = ['code'=>'id','name'=>'bahasa Indonesia'];
                    break;
                case 'hi':
                    $supportLanguageKeyValue[] = ['code'=>'hi','name'=>'हिन्दी'];
                    break;
                case 'tl':
                    $supportLanguageKeyValue[] = ['code'=>'tl','name'=>'Tagalog'];
                    break;
                default:
                    $supportLanguageKeyValue[] = ['code'=>'zh-cn','name'=>'简体中文'];
                    break;
            }
        }

        $areaCode = '';
        switch ($currency) {
            case 'CNY':
                $areaCode = '86';
                break;
            case 'PHP':
                $areaCode = '63';
                break;
            case 'VND':
                $areaCode = '84';
                break;
            case 'INR':
                $areaCode = '91';
                break;
            case 'IDR':
                $areaCode = '62';
                break;
            case 'THB':
                $areaCode = '66';
                break;
            case 'USD':
                $areaCode = '';
                break;
            
            default:
                $areaCode = '86';
                break;
        }

        $isDepositWithdrawal = 0;

        //多前端处理
        $androidDownUrl         = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'android_down_url',$this->prefix);
        $h5url                  = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'h5url',$this->prefix);
        $appDownUrl             = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'app_down_url',$this->prefix);
        $kefuLink               = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'kefu_link',$this->prefix);
        $siteTitle              = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'site_title',$this->prefix);
        $enableRegisterGiftCode = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_register_gift_code',$this->prefix);
        $officialUrl            = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'official_url',$this->prefix);
        $isAllowPlayerRegister  = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'is_allow_player_register',$this->prefix);
        $isMaintain             = (bool)CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'is_maintain',$this->prefix);
        $registerImgVerification= CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_register_img_verification',$this->prefix);
        $activitycloud          = config('main')['activitycloud'][$this->carrier->id];

        //推广链接
        $h5urlArr         = explode(',',$officialUrl);
        $officialUrl      = isset($h5urlArr[0]) ? $h5urlArr[0]:'';
        $tempurl          = '';
        foreach ($h5urlArr as $key => $value) {
            $tempurl= $tempurl.'https://www.'.$value.',';
        }
        $tempurl          = rtrim($tempurl,',');

        $data['livedomain']           = config('main')['live_domain'];
        $params = [
            'is_maintain'             => $isMaintain,
            'registerImgVerification' => $registerImgVerification,
            'kefu_link'               => $kefuLink,
            'gameImgResourseUrl'      => config('main')['alicloudstore'],
            'activityImgResourseUrl'  => config('main')[$activitycloud],
            'version'                 => '1.0.1',
            'appUrl'                  => $appDownUrl,
            'maindomain'              => $mainDomain,
            'ipblacks'                => $ipblackArr,
            'h5'                      => $h5url,
            'is_sex'                  => $this->carrier->is_sex,
            'websitetitle'                  => $siteTitle,
            'lotteryTry'                    => CarrierCache::getCarrierConfigure($this->carrier->id, 'carrier_lottery_try'),
            'registerTelehone'              => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'carrier_register_telehone',$this->prefix),
            'is_mandatory_invitation'       => 1,
            'support_language'              => $supportLanguageKeyValue,
            'default_language'              => CarrierCache::getCarrierConfigure($this->carrier->id, 'default_language_code'),
            'is_allow_player_login'         => 1,
            'is_allow_player_register'      => $isAllowPlayerRegister,
            'register_real_name'            => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'register_real_name',$this->prefix),
            'enable_eidt_telehone_verification' => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_eidt_telehone_verification',$this->prefix),
            'login_for_phone'               => 0,
            'is_card_model'                 => 2,
            'is_deposit_withdrawal'         => $isDepositWithdrawal,
            'enable_bet_gradient_rebate'    => 0,
            'is_sms_verify'                 => 0,
            'registerVerification'          => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_register_img_verification',$this->prefix),
            'loginVerification'             => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_login_img_verification',$this->prefix),
            'enable_sub_betflow'            => 0,
            'loading_icon'                  => '',
            'carrier_id'                    => $this->carrier->id,
            'round_logo'                    => $roundLogo ? $roundLogo:'',
            'navigationLogo'                => $navigationLogo ? $navigationLogo->url : '',
            'carrier_gift_code'             => $enableRegisterGiftCode,
            'currency'                      => $currency,
            'areaCode'                      => $areaCode,
            'openSignIn'                    => $openSignIn,
            'livedomain'                    => config('main')['live_domain'],
            'liveaccountlists'              => '',
            'enable_register_behavioral_verification' => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_register_behavior_verification',$this->prefix),
            'enable_login_behavioral_verification' => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_login_behavior_verification',$this->prefix),
            'aliyun_appkey'                           => config('main')['aliyunappkey'],
            'android_down_url'                        => $androidDownUrl,
            'official_url'                            => $officialUrl,
            'official_url2'                           => $tempurl,
            'isShowJointVenture'                      => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'is_show_joint_venture',$this->prefix),
            'isShowFrontExchange'                     => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'is_show_front_exchange',$this->prefix),
            'enabeleSettingDividends'                 => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enabele_setting_dividends',$this->prefix),
            'enabeleSettingGuaranteed'                => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enabele_setting_guaranteed',$this->prefix),
            'rankingsType'                            => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'rankings_type',$this->prefix),
            'rankingsCycle'                           => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'rankings_cycle',$this->prefix),
            'marketing_contact'                       => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'marketing_contact',$this->prefix),
        ];

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $params);
    }

    public function newInit()
    {
        $input            = request()->all();
        $h5url            = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'h5url',$this->prefix);
        $activitycloud    = config('main')['activitycloud'][$this->carrier->id];
        $h5urlArr         = explode(',',$h5url);
        $h5url            = isset($h5urlArr[0]) ? $h5urlArr[0]:'';
        $tempurl          = '';
        foreach ($h5urlArr as $key => $value) {
            $tempurl= $tempurl.'https://www.'.$value.',';
        }
        $tempurl          = rtrim($tempurl,',');
        $params = [
            'kefu_link'                     => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'kefu_link',$this->prefix),    //
            'gameImgResourseUrl'            => config('main')['alicloudstore'],    //
            'activityImgResourseUrl'        => config('main')[$activitycloud],
            'maindomain'                    => '',   //
            'registerTelehone'              => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'carrier_register_telehone',$this->prefix),  //
            'is_mandatory_invitation'       => 1,  //
            'is_allow_player_login'         => 1,    //
            'is_allow_player_register'      => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'is_allow_player_register',$this->prefix),  //
            'register_real_name'            => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'register_real_name',$this->prefix), //
            'carrier_gift_code'             => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_register_gift_code',$this->prefix),
            'isShowFrontExchange'           => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'is_show_front_exchange',$this->prefix),
            'down_page_url'                 => '',
            'enabeleSettingDividends'       => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enabele_setting_dividends',$this->prefix),
            'enabeleSettingGuaranteed'      => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enabele_setting_guaranteed',$this->prefix),
            'h5url'                         => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'h5url',$this->prefix),
            'enable_rankings'               => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_rankings',$this->prefix),
            'rankingsType'                  => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'rankings_type',$this->prefix),
            'rankingsCycle'                 => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'rankings_cycle',$this->prefix),
            'enable_eidt_telehone_verification' => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_eidt_telehone_verification',$this->prefix),
            'official_url'                  => 'https://www.'.$h5url,
            'official_url2'                 => $tempurl,
            'is_sms_verify'                 => 0,
            'registerVerification'          => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_register_img_verification',$this->prefix),
            'loginVerification'             => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_login_img_verification',$this->prefix),
            'marketing_contact'             => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'marketing_contact',$this->prefix),
        ];

        $platsArr = [];
        $plats    = GameCache::getPlatsList($this->carrier->id,$input);

        foreach ($plats as $key => $value) {
            $rows                         = [];
            $rows['alias']                = $value['alias'];
            $rows['main_game_plat_id']    = $value['main_game_plat_id'];
            $rows['main_game_plat_code']  = $value['main_game_plat_code'];
            $platsArr[]                   = $rows;
        }

        $params['plats']                  = $platsArr;

        $enableThirdWallet               = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'third_wallet',$this->prefix);
        $enableThirdWallet               = json_decode($enableThirdWallet,true);
        $params['enableThirdWallet']     = $enableThirdWallet;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $params);
    }

    public function marqueeNotice()
    {
         $data       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'carrier_marquee_notice',$this->prefix);
         return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['marqueeNotice'=>$data]);
    }

    public function payouttop()
    {
        $data = CarrierCache::getPayoutTop($this->carrier->id);
        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function captcha()
    {
        $phrase = new PhraseBuilder;
        // 设置验证码位数
        $code = $phrase->build(4,'0123456789');
        // 生成验证码图片的Builder对象，配置相应属性
        $builder = new CaptchaBuilder($code, $phrase);
        // 设置背景颜色25,25,112
        $builder->setBackgroundColor(204, 224, 222);
        // 设置倾斜角度
        $builder->setMaxAngle(5);
        // 设置验证码后面最大行数
        $builder->setMaxBehindLines(10);
        // 设置验证码前面最大行数
        $builder->setMaxFrontLines(10);
        // 设置验证码颜色
        $builder->setTextColor(149, 117, 142);
        // 可以设置图片宽高及字体
        $builder->build($width = 150, $height = 40, $font = null);
        // 获取验证码的内容
        $phrase = $builder->getPhrase();

        $ip              = real_ip();

        cache()->put(md5($ip),$phrase,now()->addSeconds(60));
        $data['captcha'] = $builder->inline();
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function questionType()
    {
        $data =[
            [
                'type'   => 1,
                'value'  => '存款问题',
                'remark' => config('main')['questiondesc']['1']
            ],
            [
                'type'   => 2,
                'value'  => '取款问题',
                'remark' => config('main')['questiondesc']['2']
            ],
            [
                'type'   => 3,
                'value'  => '帐号问题',
                'remark' => config('main')['questiondesc']['3']
            ],
            [
                'type'   => 4,
                'value'  => '优惠活动',
                'remark' => config('main')['questiondesc']['4']
            ],
            [
                'type'   => 5,
                'value'  => '代理加盟',
                'remark' => config('main')['questiondesc']['5']
            ],
            [
                'type'   => 6,
                'value'  => '虚拟货币',
                'remark' => config('main')['questiondesc']['6']
            ],
            [
                'type'   => 7,
                'value'  => '三方钱包',
                'remark' => config('main')['questiondesc']['7']
            ],
            [
                'type'   => 8,
                'value'  => '转帐问题',
                'remark' => config('main')['questiondesc']['8']
            ],
            [
                'type'   => 9,
                'value'  => '其他问题',
                'remark' => config('main')['questiondesc']['9']
            ],
        ];

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function questionList()
    {
        $data = CarrierQuestions::questionLists($this->carrier);
        if(is_array($data)) {
            return returnApiJson(config('language')[$this->language]['success1'], 1,$data);
        } else {
            return returnApiJson($data, 0);
        }
    }

    public function allquestionList()
    {
        $carrierQuestions =  CarrierQuestions::where('carrier_id',$this->carrier->id)->orderBy('id','desc')->get();
        $mapTypes         =  [];
        foreach ($carrierQuestions as $key => $value) {
            $mapTypes[$value->type][]=$value;
        }
        $data =[
            [
                'type'   => 1,
                'value'  => '存款问题',
                'remark' => config('main')['questiondesc']['1'],
                'list'   => $mapTypes[1]
            ],
            [
                'type'   => 2,
                'value'  => '取款问题',
                'remark' => config('main')['questiondesc']['2'],
                'list'   => $mapTypes[2]
            ],
            [
                'type'   => 3,
                'value'  => '帐号问题',
                'remark' => config('main')['questiondesc']['3'],
                'list'   => $mapTypes[3]
            ],
            [
                'type'   => 4,
                'value'  => '优惠活动',
                'remark' => config('main')['questiondesc']['4'],
                'list'   => $mapTypes[4]
            ],
            [
                'type'   => 5,
                'value'  => '代理加盟',
                'remark' => config('main')['questiondesc']['5'],
                'list'   => $mapTypes[5]
            ],
            [
                'type'   => 6,
                'value'  => '虚拟货币',
                'remark' => config('main')['questiondesc']['6'],
                'list'   => $mapTypes[6]
            ],
            [
                'type'   => 7,
                'value'  => '三方钱包',
                'remark' => config('main')['questiondesc']['7'],
                'list'   => $mapTypes[7]
            ],
            [
                'type'   => 8,
                'value'  => '转帐问题',
                'remark' => config('main')['questiondesc']['8'],
                'list'   => $mapTypes[8]
            ],
            [
                'type'   => 9,
                'value'  => '其他问题',
                'remark' => config('main')['questiondesc']['9'],
                'list'   => $mapTypes[9]
            ],
        ];

        return returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function registerProtocol()
    {
        $data['protocol'] = config('protocol')['register'];
        return returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function menus()
    {
        $input                 = request()->all(); 

        $carrierHorizontalMenus = CarrierHorizontalMenu::where('carrier_id',$this->carrier->id)->where('status',1)->where('prefix',$this->prefix)->orderBy('sort','desc')->get();
        $data['menus']         = $carrierHorizontalMenus;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function newMenus()
    {
        $input                 = request()->all(); 

        $carrierHorizontalMenus = CarrierHorizontalMenu::select('api','key','sort','type')->where('carrier_id',$this->carrier->id)->where('status',1)->where('prefix',$this->prefix)->orderBy('sort','desc')->get();
        $showHotGameNumber      = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'show_hot_game_number',$this->prefix);
        
        $data['menus']         = $carrierHorizontalMenus;
        $hotgamelist           = [];

        $gameIds               = [];
        //添加热门
        $i                     = 0;
        $hotGameLists = GameCache::hotGameList($this->carrier->id,$input,$this->prefix);
        foreach ($hotGameLists['data'] as $key => $value) {
            if(!in_array($value['game_id'],$gameIds) && $i<$showHotGameNumber){
                $gameIds[]                     = $value['game_id'];
                $rows                          = [];
                $rows['display_name']          = $value['display_name'];
                $rows['game_icon_square_path'] = $value['game_icon_square_path'];
                $rows['game_id']               = $value['game_id'];
                $rows['is_hot']                = $value['is_hot'];
                $rows['sort']                  = $value['sort'];
                $hotgamelist[]                 = $rows;
                $i++;
            }         
        }

        $data['hotgamelist']               = $hotgamelist;

        //电子
        $i                   = 1;
        $electroniccategorys = [];
        $electronicCategorys = GameCache::electronicCategoryList($this->carrier->id,$this->prefix);
        foreach ($electronicCategorys as $key => $value) {
            $rows                        = [];
            $rows['game_icon_path']      = $value['game_icon_path'];
            $rows['game_plat_id']        = $value['game_plat_id'];
            $rows['display_name']        = $value['display_name'];
            $rows['is_recommend']        = $value['is_recommend'];
            $rows['main_game_plat_code'] = $value['main_game_plat_code'];
            $rows['template_moblie_game_icon_path'] = '/game/slots/'.$i.'.png';
            $rows['new_template_moblie_game_icon_path'] = '/game/slots/'.$this->prefix.'/'.$value['main_game_plat_code'].'.png';
            $electroniccategorys[]       = $rows;
            $i++;
        }

        $data['electronic']     = $electroniccategorys;

        //视讯
        $i        = 1;
        $livesArr = [];
        $lives    = GameCache::live($this->carrier->id,$input,$this->prefix);
        foreach ($lives['data'] as $key => $value) {
            $rows                             = [];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['game_plat_id']             = $value['game_plat_id'];
            $rows['display_name']             = $value['display_name'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $rows['template_moblie_game_icon_path'] = '/game/live/'.$i.'.png';
            $rows['new_template_moblie_game_icon_path'] = '/game/live/'.$this->prefix.'/'.$value['main_game_plat_code'].'.png';
            $livesArr[]                       = $rows;
            $i++;
        }

        $data['live']     = $livesArr;
        //棋牌
        $i        = 1;
        $cardsArr = [];
        $card    = GameCache::card($this->carrier->id,$input,$this->prefix);
        foreach ($card['data'] as $key => $value) {
            $rows                             = [];
            $rows['display_name']             = $value['display_name'];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $rows['template_moblie_game_icon_path'] = '/game/card/'.$i.'.png';
            $rows['new_template_moblie_game_icon_path'] = '/game/card/'.$this->prefix.'/'.$value['main_game_plat_code'].'.png';
            $cardsArr[]                       = $rows;
            $i++;
        }
        
        $data['card']     = $cardsArr;

        //彩票
        $i           = 1;
        $lotteryArrs = [];
        $lotterys    = GameCache::lotteryList($this->carrier->id,$input,$this->prefix);

        foreach ($lotterys['data'] as $key => $value) {
            $rows                             = [];
            $rows['display_name']             = $value['display_name'];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $rows['template_moblie_game_icon_path'] = '/game/lottery/'.$i.'.png';
            $rows['new_template_moblie_game_icon_path'] = '/game/lottery/'.$this->prefix.'/'.$value['main_game_plat_code'].'.png';
            $lotteryArrs[]                       = $rows;
            $i++;
        }
        
        $data['lottery']     = $lotteryArrs;

        //体育
        $i         = 1;
        $sportArrs = [];
        $sports    = GameCache::sport($this->carrier->id,$input,$this->prefix);
        foreach ($sports['data'] as $key => $value) {
            $rows                             = [];
            $rows['display_name']             = $value['display_name'];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $rows['template_moblie_game_icon_path'] = '/game/sport/'.$i.'.png';
            $rows['new_template_moblie_game_icon_path'] = '/game/sport/'.$this->prefix.'/'.$value['main_game_plat_code'].'.png';
            $sportArrs[]                      = $rows;
            $i++;
        }
        
        $data['sport']     = $sportArrs;
        //电竞
        $i         = 1;
        $esportArrs = [];
        $esports    = GameCache::esport($this->carrier->id,$input);
        foreach ($esports['data'] as $key => $value) {
            $rows                             = [];
            $rows['display_name']             = $value['display_name'];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $rows['template_moblie_game_icon_path'] = '/game/esport/'.$i.'.png';
            $rows['new_template_moblie_game_icon_path'] = '/game/esport/'.$this->prefix.'/'.$value['main_game_plat_code'].'.png';
            $esportArrs[]                      = $rows;
            $i++;
        }
        
        $data['esport']     = $esportArrs;

        //捕鱼
        $i         = 1;
        $fishArrs = [];
        $fishs    = GameCache::fish($this->carrier->id,$input,$this->prefix);
        foreach ($fishs['data'] as $key => $value) {
            $rows                             = [];
            $rows['display_name']             = $value['display_name'];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $rows['template_moblie_game_icon_path'] = '/game/fish/'.$i.'.png';
            $rows['new_template_moblie_game_icon_path'] = '/game/fish/'.$this->prefix.'/'.$value['main_game_plat_code'].'_'.$value['game_code'].'.png';
            $fishArrs[]                       = $rows;
            $i++;
        }
        
        $data['fish']     = $fishArrs;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function thirdWalletHelp()
    {
        $data = [];
        $data['title'] = 'GoPay、ToPay、OkPay、EbPay、WanbPay、JdPay、KdPay、NoPay、BobiPay';
        $data['gift']  = 2;
        $data['list']  = [
            [
                [
                'label' => '点击下载OkPay钱包',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'okpay_down')
                ],
                [
                'label' => '查看教程',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'okpay_tutorial')
                ],
            ],
            [
                [
                'label' => '点击下载GoPay钱包',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'gopay_down')
                ],
                [
                'label' => '查看教程',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'gopay_tutorial')
                ],
            ],
            [
                [
                'label' => '点击下载TOPay钱包',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'topay_down')
                ],
                [
                'label' => '查看教程',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'topay_tutorial')
                ],
            ],
            [
                [
                'label' => '点击下载EbPay钱包',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'ebpay_down')
                ],
                [
                'label' => '查看教程',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'ebpay_tutorial')
                ],
            ],
            [
                [
                'label' => '点击下载WanbPay钱包',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'wanb_down')
                ],
                [
                'label' => '查看教程',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'wanb_tutorial')
                ],
            ],
            [
                [
                'label' => '点击下载JdPay钱包',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'jdpay_down')
                ],
                [
                'label' => '查看教程',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'jdpay_tutorial')
                ],
            ],
            [
                [
                'label' => '点击下载KdPay钱包',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'kdpay_down')
                ],
                [
                'label' => '查看教程',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'kdpay_tutorial')
                ],
            ],
            [
                [
                'label' => '点击下载NoPay钱包',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'nopay_down')
                ],
                [
                'label' => '查看教程',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'nopay_tutorial')
                ],
            ],
            [
                [
                'label' => '点击下载BobiPay钱包',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'bobipay_down')
                ],
                [
                'label' => '查看教程',
                'link'  => CarrierCache::getCarrierConfigure($this->carrier->id,'bobipay_tutorial')
                ],
            ]
        ];

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function allowWithdrawMethod()
    {
        $allowThirdWallets        = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'third_wallet',$this->prefix);
        $disableWithdrawChannel   = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'disable_withdraw_channel',$this->prefix);
        $allowThirdWallets        = json_decode($allowThirdWallets,true);
        $disableWithdrawChannel   = json_decode($disableWithdrawChannel,true);
        $thirdWallets             = array_diff($allowThirdWallets, $disableWithdrawChannel);

        $data = [];
        foreach ($thirdWallets as $key => $value) {
            switch ($value) {
                case '1':
                    $row              = [];
                    $row['type']      = $value;
                    $row['type_name'] = 'USDT';
                    $data[]           = $row;
                    break;
                case '3':
                    $row              = [];
                    $row['type']      = $value;
                    $row['type_name'] = 'Okpay';
                    $data[]           = $row;
                    break;
                case '4':
                    $row              = [];
                    $row['type']      = $value;
                    $row['type_name'] = 'Gopay';
                    $data[]           = $row;
                    break;
                case '6':
                    $row              = [];
                    $row['type']      = $value;
                    $row['type_name'] = 'Topay';
                    $data[]           = $row;
                    break;
                case '7':
                    $row              = [];
                    $row['type']      = $value;
                    $row['type_name'] = 'Ebpay';
                    $data[]           = $row;
                    break;
                case '8':
                    $row              = [];
                    $row['type']      = $value;
                    $row['type_name'] = 'Wanb';
                    $data[]           = $row;
                    break;
                case '9':
                    $row              = [];
                    $row['type']      = $value;
                    $row['type_name'] = 'Jdpay';
                    $data[]           = $row;
                    break;
                case '10':
                    $row              = [];
                    $row['type']      = $value;
                    $row['type_name'] = 'Kdpay';
                    $data[]           = $row;
                    break;
                case '11':
                    $row              = [];
                    $row['type']      = $value;
                    $row['type_name'] = 'Nopay';
                    $data[]           = $row;
                    break;
                case '12':
                    $row              = [];
                    $row['type']      = $value;
                    $row['type_name'] = 'Bobipay';
                    $data[]           = $row;
                    break;
                default:
                    break;
            }
        }

        $row              = [];
        $row['type']      = '0';
        $row['type_name'] = '银行卡';
        $data[]           = $row;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }


    public function guaranteedList()
    {
       $data                   = [];
       $playerSetting          = PlayerCache::getPlayerSetting($this->user->player_id);
       $data['selfguaranteed'] = $playerSetting->guaranteed;
       $selfLevel              = 0;
       $carrierGuaranteed =  CarrierGuaranteed::where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->orderBy('sort','asc')->get();
       $carrierGuaranteed1 = $carrierGuaranteed->toArray();

       if(!isset($carrierGuaranteed[0]->quota)){
            return $this->returnApiJson(config('language')[$this->language]['error403'], 0);
       }

       if($data['selfguaranteed'] <= $carrierGuaranteed[0]->quota){
            $selfLevel = $carrierGuaranteed[0]->id;
       } else{
            foreach ($carrierGuaranteed as $key => $value) {
                if($value->quota <= $data['selfguaranteed']){
                    $selfLevel = $value->id;
                }
            }

            $maxCarrierGuaranteed = end($carrierGuaranteed1);
            if($maxCarrierGuaranteed['quota'] < $data['selfguaranteed']){
                $selfLevel = 0;
            }
        }

        foreach ($carrierGuaranteed as $k => &$v) {
            if($v->id==$selfLevel){
                $v->selfLevel =1;
            } else{
                $v->selfLevel =0;
            }
        }
       
       $data['list']           = $carrierGuaranteed;
       
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$carrierGuaranteed);
    }

    public function newGuaranteedList()
    {
       $data                   = [];
       $playerSetting          = PlayerCache::getPlayerSetting($this->user->player_id);
       $data['selfguaranteed'] = $playerSetting->guaranteed;
       $selfLevel              = 0;
       $carrierGuaranteed =  CarrierGuaranteed::where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->orderBy('sort','asc')->get();
       if(!$carrierGuaranteed){
            return $this->returnApiJson(config('language')[$this->language]['error404'], 0);
       }
       $carrierGuaranteed1 = $carrierGuaranteed->toArray();

       if($data['selfguaranteed'] <= $carrierGuaranteed[0]->quota){
            $selfLevel = $carrierGuaranteed[0]->id;
       } else{
            foreach ($carrierGuaranteed as $key => $value) {
                if($value->quota <= $data['selfguaranteed']){
                    $selfLevel = $value->id;
                }
            }

            $maxCarrierGuaranteed = end($carrierGuaranteed1);
            if($maxCarrierGuaranteed['quota'] < $data['selfguaranteed']){
                $selfLevel = 0;
            }
        }

        foreach ($carrierGuaranteed as $k => &$v) {
            if($v->id==$selfLevel){
                $v->selfLevel =1;
            } else{
                $v->selfLevel =0;
            }
        }
       
       $data['list']           = $carrierGuaranteed;
       
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function agentVoucherList()
    {
        $res = PlayerHoldGiftCode::agentVoucherList($this->user);
        if(is_array($res)){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$res);
        } else{
            return $this->returnApiJson($res, 0);
        }
    }

    public function index()
    {
        $input                  = request()->all();
        $h5url                  = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'h5url',$this->prefix);
        $h5urlArr               = explode(',',$h5url);
        $h5url                  = isset($h5urlArr[0]) ? $h5urlArr[0]:'';
        $showHotGameNumber      = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'show_hot_game_number',$this->prefix);
        $activitycloud          = config('main')['activitycloud'][$this->carrier->id];
        $collection       = [];
        $params = [
            'kefu_link'                     => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'kefu_link',$this->prefix),    //
            'gameImgResourseUrl'            => config('main')['alicloudstore'],    //
            'activityImgResourseUrl'        => config('main')[$activitycloud],
            'maindomain'                    => '',   //
            'registerTelehone'              => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'carrier_register_telehone',$this->prefix),  //
            'is_mandatory_invitation'       => 1,  //
            'is_allow_player_login'         => 1,    //
            'is_allow_player_register'      => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'is_allow_player_register',$this->prefix),  //
            'register_real_name'            => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'register_real_name',$this->prefix), //
            'carrier_gift_code'             => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_register_gift_code',$this->prefix),
            'isShowFrontExchange'           => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'is_show_front_exchange',$this->prefix),
            'down_page_url'                 => '',
            'enabeleSettingDividends'       => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enabele_setting_dividends',$this->prefix),
            'enabeleSettingGuaranteed'      => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enabele_setting_guaranteed',$this->prefix),
            'h5url'                         => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'h5url',$this->prefix),
            'enable_rankings'               => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_rankings',$this->prefix),
            'rankingsType'                  => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'rankings_type',$this->prefix),
            'rankingsCycle'                 => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'rankings_cycle',$this->prefix),
            'voucherRechargeAmount'         => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'voucher_recharge_amount',$this->prefix),
            'registerGiftCodeAmount'        => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'register_gift_code_amount',$this->prefix),
            'withdrawalNeedSms'             => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'withdrawal_need_sms',$this->prefix),
            'siteTransferMethod'            => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'site_transfer_method',$this->prefix),
            'enableSafeBox'                 => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_safe_box',$this->prefix),
            'enable_eidt_telehone_verification' => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_eidt_telehone_verification',$this->prefix),
            'withdrawalRetentionAmount'     => 0,
            'official_url'                  => 'https://www.'.$h5url,
            'is_sms_verify'                 => 0,
            'registerVerification'          => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_register_img_verification',$this->prefix), //
            'loginVerification'             => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_login_img_verification',$this->prefix),
            'marketing_contact'             => CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'marketing_contact',$this->prefix),
        ];

        $platsArr = [];
        $plats    = GameCache::getPlatsList($this->carrier->id,$input);

        foreach ($plats as $key => $value) {
            $rows                         = [];
            $rows['alias']                = $value['alias'];
            $rows['main_game_plat_id']    = $value['main_game_plat_id'];
            $rows['main_game_plat_code']  = $value['main_game_plat_code'];
            $platsArr[]                   = $rows;
        }

        $params['plats']                  = $platsArr;

        $enableThirdWallet               = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'third_wallet',$this->prefix);
        $enableThirdWallet               = json_decode($enableThirdWallet,true);
        $params['enableThirdWallet']     = $enableThirdWallet;
        $collection['init']              = $params;


        $carrierHorizontalMenus          = CarrierHorizontalMenu::select('api','key','sort','type')->where('carrier_id',$this->carrier->id)->where('status',1)->where('prefix',$this->prefix)->orderBy('sort','desc')->get();  
        $data['menus']         = $carrierHorizontalMenus;
        $hotgamelist           = [];

        $gameIds               = [];
        //添加热门
        $i                     = 0;
        $hotGameLists = GameCache::hotGameList($this->carrier->id,$input,$this->prefix);
        foreach ($hotGameLists['data'] as $key => $value) {
            if(!in_array($value['game_id'],$gameIds) && $i<$showHotGameNumber){
                $gameIds[]                     = $value['game_id'];
                $rows                          = [];
                $rows['display_name']          = $value['display_name'];
                $rows['game_icon_square_path'] = $value['game_icon_square_path'];
                $rows['game_id']               = $value['game_id'];
                $rows['is_hot']                = $value['is_hot'];
                $rows['sort']                  = $value['sort'];
                $hotgamelist[]                 = $rows;
                $i++;
            }         
        }

        $data['hotgamelist']               = $hotgamelist;

        //电子
        $electroniccategorys = [];
        $electronicCategorys = GameCache::electronicCategoryList($this->carrier->id,$this->prefix);
        foreach ($electronicCategorys as $key => $value) {
            $rows                        = [];
            $rows['game_icon_path']      = $value['game_icon_path'];
            $rows['game_plat_id']        = $value['game_plat_id'];
            $rows['display_name']        = $value['display_name'];
            $rows['is_recommend']        = $value['is_recommend'];
            $rows['main_game_plat_code'] = $value['main_game_plat_code'];
            $electroniccategorys[]       = $rows;
        }

        $data['electronic']     = $electroniccategorys;

        //视讯
        $livesArr = [];
        $lives    = GameCache::live($this->carrier->id,$input,$this->prefix);
        foreach ($lives['data'] as $key => $value) {
            $rows                             = [];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['game_plat_id']             = $value['game_plat_id'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $rows['display_name']             = $value['display_name'];
            $livesArr[]                       = $rows;
        }

        $data['live']     = $livesArr;
        //棋牌
        $cardsArr = [];
        $card    = GameCache::card($this->carrier->id,$input,$this->prefix);
        foreach ($card['data'] as $key => $value) {
            $rows                             = [];
            $rows['display_name']             = $value['display_name'];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $cardsArr[]                       = $rows;
        }
        
        $data['card']     = $cardsArr;

        //彩票
        $lotteryArrs = [];
        $lotterys    = GameCache::lotteryList($this->carrier->id,$input,$this->prefix);

        foreach ($lotterys['data'] as $key => $value) {
            $rows                             = [];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $lotteryArrs[]                       = $rows;
        }
        
        $data['lottery']     = $lotteryArrs;

        //体育
        $sportArrs = [];
        $sports    = GameCache::sport($this->carrier->id,$input,$this->prefix);
        foreach ($sports['data'] as $key => $value) {
            $rows                             = [];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $sportArrs[]                      = $rows;
        }
        
        $data['sport']     = $sportArrs;
        //电竞
        $esportArrs = [];
        $esports    = GameCache::esport($this->carrier->id,$input);
        foreach ($esports['data'] as $key => $value) {
            $rows                             = [];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $esportArrs[]                      = $rows;
        }
        
        $data['esport']     = $esportArrs;

        //捕鱼
        $fishArrs = [];
        $fishs    = GameCache::fish($this->carrier->id,$input,$this->prefix);
        foreach ($fishs['data'] as $key => $value) {
            $rows                             = [];
            $rows['display_name']             = $value['display_name'];
            $rows['game_icon_path']           = $value['game_icon_path'];
            $rows['game_id']                  = $value['game_id'];
            $rows['main_game_plat_code']      = $value['main_game_plat_code'];
            $fishArrs[]                       = $rows;
        }
        
        $data['fish']                         = $fishArrs;

        $collection['newmenus']               = $data;

        $d                           = [];
        $d['marqueeNotice']          = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'carrier_marquee_notice',$this->prefix);
        $collection['marqueenotice'] = $d;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$collection);
    }

    public function directTypeFeesList()
    {
        $giftdeductions = config('main')['giftdeduction'];
        $developments   = Development::all();
        $data           = [];

        foreach ($developments as $key => $value) {
            foreach ($giftdeductions as $k => $v) {
                if($v== $value->sign){
                    $row = [];
                    $row['name'] = $value->name;
                    $row['type'] = $v;
                    $data[]      = $row;
                }
            }
        }
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function errorLog()
    {
        $input = request()->all();
        if(isset($input['error']) && !empty($input['error'])){
            \Log::info('收到的错误日志是',['cccc'=>$input['error']]);
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        } else{
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }
    }

    public function voucherExchange()
    {
        $voucherKey = 'voucher_'.$this->user->player_id;

        if(cache()->get($voucherKey,0)!=0){
            return $this->returnApiJson(config('language')[$this->language]['error405'], 0);
        }

        cache()->put($voucherKey, 1, now()->addMinutes(3));

        $input = request()->all();
        if(!isset($input['gift_code']) || empty($input['gift_code'])){
            return $this->returnApiJson(config('language')[$this->language]['error406'], 0);
        }

        $carrierActivityGiftCode = CarrierActivityGiftCode::where('carrier_id',$this->carrier->id)->where('prefix',$this->user->prefix)->where('gift_code',$input['gift_code'])->first();

        if(!$carrierActivityGiftCode){
            return $this->returnApiJson(config('language')[$this->language]['error407'], 0);
        }

        if($carrierActivityGiftCode->status==1){
            return $this->returnApiJson(config('language')[$this->language]['error408'], 0);
        }

        if($carrierActivityGiftCode->startTime > time() || $carrierActivityGiftCode->endTime < time()){
            return $this->returnApiJson(config('language')[$this->language]['error409'], 0);
        }

        if(!$carrierActivityGiftCode->distributestatus){
            return $this->returnApiJson(config('language')[$this->language]['error410'], 0);
        }

        $existPlayerTransfer = PlayerTransfer::where('player_id',$this->user->prefix)->where('mode',1)->first();
        if($existPlayerTransfer){
            return $this->returnApiJson(config('language')[$this->language]['error411'], 0);
        }

        $rechargePlayerTransfer = PlayerTransfer::where('player_id',$this->user->player_id)->where('type','recharge')->first();
        if($rechargePlayerTransfer){
            return $this->returnApiJson(config('language')[$this->language]['error412'], 0);
        }

        if(!empty($this->user->gift_code)){
            return $this->returnApiJson(config('language')[$this->language]['error413'], 0);
        }
        
        //同姓名的只可领取1个
        $sameRealNameCount = Player::where('prefix',$this->user->prefix)->where('real_name',$this->user->real_name)->count();
        if($sameRealNameCount >1){
            return $this->returnApiJson(config('language')[$this->language]['error540'], 0);
        }

        //上级在禁领名单中不能领取
        $disableVoucherChannel     = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'disable_voucher_channel',$this->user->prefix);
        if(!empty($disableVoucherChannel)){
            $directIds =explode(',', $disableVoucherChannel);
            if(in_array($this->user->parent_id,$directIds)){
                return $this->returnApiJson(config('language')[$this->language]['error538'], 0); 
            }
        }

        //团队在禁领名单中不能领取
        $disableVoucherTeamChannel = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'disable_voucher_team_channel',$this->user->prefix);
        if(!empty($disableVoucherTeamChannel)){
            $directTeamIds =explode(',', $disableVoucherTeamChannel);
            foreach ($directTeamIds as $key => $value) {
                if(strpos($this->user->rid,$value) !== false){
                    return $this->returnApiJson(config('language')[$this->language]['error539'], 0); 
                }
            }
        }

        //有关联IP或关联设备领取过券不让再次领取
        $existPlayerIds = PlayerTransfer::where('prefix',$this->user->prefix)->where('type','code_gift')->pluck('player_id')->toArray();
        $existlogoinIps = PlayerLogin::whereIn('player_id',$existPlayerIds)->where('player_id','!=',$this->user->player_id)->pluck('login_ip')->toArray();
        $existlogoinIps = array_unique($existlogoinIps);

        $existFingerprints = PlayerFingerprint::whereIn('player_id',$existPlayerIds)->where('player_id','!=',$this->user->player_id)->pluck('fingerprint')->toArray();
        $existFingerprints = array_unique($existFingerprints);
        $selflogoinIps     = PlayerLogin::where('player_id',$this->user->player_id)->pluck('login_ip')->toArray();

        foreach ($selflogoinIps as $key => $value) {
            if(in_array($value,$existlogoinIps)){
                $carrierActivityGiftCode->status =1;
                $carrierActivityGiftCode->save();
                return $this->returnApiJson(config('language')[$this->language]['error414'], 0);
            }
        }

        $selfFingerprints  = PlayerFingerprint::where('player_id',$this->user->player_id)->pluck('fingerprint')->toArray();

        foreach ($selfFingerprints as $key => $value) {
            if(in_array($value,$existFingerprints)){
                $carrierActivityGiftCode->status =1;
                $carrierActivityGiftCode->save();
                return $this->returnApiJson(config('language')[$this->language]['error415'], 0);
            }
        }

        $playerBankCard        = PlayerBankCard::where('player_id',$this->user->player_id)->first();
        $playerAlipay          = PlayerAlipay::where('player_id',$this->user->player_id)->first();

        if(!$playerBankCard && !$playerAlipay){
            return $this->returnApiJson(config('language')[$this->language]['error416'], 0);
        }

        //上线是渠道号跳过白嫖银行卡判断
        $skipAbrbitrageursJudgeChannel = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'skip_abrbitrageurs_judge_channel',$this->user->prefix);
        $parentIdisforum               = false;
        if(empty($skipAbrbitrageursJudgeChannel)){
            //白嫖党银行卡判断
            $allBankStat     = BankStat::pluck('banknumber')->toArray();
            if($playerBankCard && in_array($playerBankCard->card_account,$allBankStat)){
                $carrierActivityGiftCode->status =1;
                $carrierActivityGiftCode->save();
                return $this->returnApiJson(config('language')[$this->language]['error417'], 0);
            }

            //白嫖党支付宝判断
            $allAlipayStat     = AlipayStat::pluck('banknumber')->toArray();
            if($playerAlipay && in_array($playerAlipay->card_account,$allAlipayStat)){
                $carrierActivityGiftCode->status =1;
                $carrierActivityGiftCode->save();
                return $this->returnApiJson(config('language')[$this->language]['error417'], 0);
            }
        } else{
            $skipAbrbitrageursJudgeChannelArr = explode(',',$skipAbrbitrageursJudgeChannel);
            if(!in_array($this->user->parent_id,$skipAbrbitrageursJudgeChannelArr)){
                //白嫖党银行卡判断
                $allBankStat     = BankStat::pluck('banknumber')->toArray();
                if($playerBankCard && in_array($playerBankCard->card_account,$allBankStat)){
                    $carrierActivityGiftCode->status =1;
                    $carrierActivityGiftCode->save();
                    return $this->returnApiJson(config('language')[$this->language]['error417'], 0);
                }

                //白嫖党支付宝判断
                $allAlipayStat     = AlipayStat::pluck('banknumber')->toArray();
                if($playerAlipay && in_array($playerAlipay->card_account,$allAlipayStat)){
                    $carrierActivityGiftCode->status =1;
                    $carrierActivityGiftCode->save();
                    return $this->returnApiJson(config('language')[$this->language]['error417'], 0);
                }
            } else{
                $parentIdisforum               = true;
            }
        }

        //上级线路判定
        $stopExchangeRate           = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'stop_exchange_rate',$this->user->prefix);
        $notIncludedExchangeRate    = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'not_included_exchange_rate',$this->user->prefix);
        $rechargeAccount  = PlayerDepositPayLog::where('parent_id',$this->user->parent_id)->where('status',1)->groupBy('player_id')->count();
        $giftCodeAccount  = Player::where('parent_id',$this->user->parent_id)->where('gift_code','!=','')->count();

        if($parentIdisforum){
            $exchangeFlag     = $stopExchangeRate*$rechargeAccount + $stopExchangeRate + $notIncludedExchangeRate;
        } else{
            $exchangeFlag     = $stopExchangeRate*$rechargeAccount + $stopExchangeRate;
        }

        if($exchangeFlag < $giftCodeAccount){
            $carrierActivityGiftCode->status =1;
            $carrierActivityGiftCode->save();
            return $this->returnApiJson(config('language')[$this->language]['error418'], 0);
        }

        $cacheKey   = "player_" .$this->user->player_id;
        $redisLock = Lock::addLock($cacheKey,60);

        if (!$redisLock) {
            return returnApiJson(config('language')[$this->language]['error20'], 0);
        } else {
            try {
                \DB::beginTransaction();
                $playerAccount                                      = PlayerAccount::where('player_id',$this->user->player_id)->lockForUpdate()->first();

                $playerGiftCode                                     = new PlayerGiftCode();
                $playerGiftCode->carrier_id                         = $this->user->carrier_id;
                $playerGiftCode->top_id                             = $this->user->top_id;
                $playerGiftCode->parent_id                          = $this->user->parent_id;
                $playerGiftCode->rid                                = $this->user->rid;
                $playerGiftCode->player_id                          = $this->user->player_id;
                $playerGiftCode->user_name                          = $this->user->user_name;
                $playerGiftCode->day                                = date('Ymd');
                $playerGiftCode->limit_amount                       = $carrierActivityGiftCode->betflowmultiple*$carrierActivityGiftCode->money*10000;
                $playerGiftCode->amount                             = $carrierActivityGiftCode->money*10000;
                $playerGiftCode->giftcode                           = $input['gift_code'];
                $playerGiftCode->type                               = 1;
                $playerGiftCode->prefix                             = $this->user->prefix;
                $playerGiftCode->betflow_limit_category             = $carrierActivityGiftCode->betflow_limit_category;
                $playerGiftCode->betflow_limit_main_game_plat_id    = $carrierActivityGiftCode->betflow_limit_main_game_plat_id;
                $playerGiftCode->save();

                $playerReceiveGiftCenter                                  = new PlayerReceiveGiftCenter();
                $playerReceiveGiftCenter->orderid                         = 'LJ'.$playerAccount->player_id.time().rand('1','99');
                $playerReceiveGiftCenter->carrier_id                      = $playerAccount->carrier_id;
                $playerReceiveGiftCenter->player_id                       = $playerAccount->player_id;
                $playerReceiveGiftCenter->user_name                       = $playerAccount->user_name;
                $playerReceiveGiftCenter->top_id                          = $playerAccount->top_id;
                $playerReceiveGiftCenter->parent_id                       = $playerAccount->parent_id;
                $playerReceiveGiftCenter->rid                             = $playerAccount->rid;
                $playerReceiveGiftCenter->type                            = 26;
                $playerReceiveGiftCenter->amount                          = $playerGiftCode->amount;
                $playerReceiveGiftCenter->invalidtime                     = time();
                $playerReceiveGiftCenter->limitbetflow                    = $playerGiftCode->limit_amount;
                $playerReceiveGiftCenter->betflow_limit_category          = $carrierActivityGiftCode->betflow_limit_category;
                $playerReceiveGiftCenter->betflow_limit_main_game_plat_id = $carrierActivityGiftCode->betflow_limit_main_game_plat_id;
                $playerReceiveGiftCenter->status                          = 1;
                $playerReceiveGiftCenter->receivetime                     = time();
                $playerReceiveGiftCenter->save();

                $playerTransfer                                  = new PlayerTransfer();
                $playerTransfer->prefix                          = $this->user->prefix;
                $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                $playerTransfer->rid                             = $playerAccount->rid;
                $playerTransfer->top_id                          = $playerAccount->top_id;
                $playerTransfer->parent_id                       = $playerAccount->parent_id;
                $playerTransfer->player_id                       = $playerAccount->player_id;
                $playerTransfer->is_tester                       = $playerAccount->is_tester;
                $playerTransfer->level                           = $playerAccount->level;
                $playerTransfer->user_name                       = $playerAccount->user_name;
                $playerTransfer->mode                            = 1;
                $playerTransfer->type                            = 'code_gift';
                $playerTransfer->type_name                       = config('language')['zh']['text117'];
                $playerTransfer->en_type_name                    = config('language')['en']['text117'];
                $playerTransfer->day_m                           = date('Ym',time());
                $playerTransfer->day                             = date('Ymd',time());
                $playerTransfer->amount                          = $carrierActivityGiftCode->money*10000;
                $playerTransfer->before_balance                  = $playerAccount->balance;
                $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                $playerTransfer->before_frozen_balance           = 0;
                $playerTransfer->frozen_balance                  = 0;
                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;

                $playerTransfer->save();

                $playerWithdrawFlowLimit                                  = new PlayerWithdrawFlowLimit();
                $playerWithdrawFlowLimit->carrier_id                      = $playerAccount->carrier_id;
                $playerWithdrawFlowLimit->top_id                          = $playerAccount->top_id;
                $playerWithdrawFlowLimit->parent_id                       = $playerAccount->parent_id;
                $playerWithdrawFlowLimit->rid                             = $playerAccount->rid;
                $playerWithdrawFlowLimit->player_id                       = $playerAccount->player_id;
                $playerWithdrawFlowLimit->user_name                       = $playerAccount->user_name;
                $playerWithdrawFlowLimit->limit_amount                    = $playerTransfer->amount*$carrierActivityGiftCode->betflowmultiple;
                $playerWithdrawFlowLimit->betflow_limit_category          = $carrierActivityGiftCode->betflow_limit_category;;
                $playerWithdrawFlowLimit->betflow_limit_main_game_plat_id = $carrierActivityGiftCode->betflow_limit_main_game_plat_id;;
                $playerWithdrawFlowLimit->limit_type                      = 26;
                $playerWithdrawFlowLimit->save();

                $playerAccount->balance                           = $playerTransfer->balance;
                $playerAccount->save();

                $carrierActivityGiftCode->status                  = 1;
                $carrierActivityGiftCode->save();

                $existPlayerHoldGiftCode = PlayerHoldGiftCode::where('gift_code',$input['gift_code'])->where('prefix',$this->user->prefix)->first();
                if($existPlayerHoldGiftCode){
                    $existPlayerHoldGiftCode->status                  = 1;
                    $existPlayerHoldGiftCode->save();
                }

                $this->user->gift_code = $input['gift_code'];
                $this->user->save();

                \DB::commit();
                Lock::release($redisLock);
                return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal('体验券兑换异常：'.$e->getMessage());   
                return $this->returnApiJson($e->getMessage(), 0);
            }
        }
    }

    public function customerService()
    {
        $kefuLink             = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'kefu_link',$this->prefix);
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['kefulink'=>$kefuLink]);
    }

    public function popDetection()
    {
        $status = 0;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['status'=>$status]);
    }

    public function popCommission()
    {
        $status                         = 0;
        $playerTransfer                 = PlayerTransfer::where('player_id',$this->user->player_id)->where('type','!=','withdraw_apply')->orderBy('id','desc')->first();
        if($playerTransfer && $playerTransfer->type=='commission_from_child'){
            $status = 1;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['status'=>$status]);
    }

    public function jumpDetect()
    {
        $url           = request()->header('Origin');
        $url           = str_replace("http://", "", trim($url));
        $url           = str_replace("https://", "", trim($url));
        $explodeArray  = explode('.',$url);
        $domain        = '';

        if(count($explodeArray)==2){
            $domain    = $url;
        } elseif(count($explodeArray)==3){
            $domain    = $explodeArray[1].'.'.$explodeArray[2];
        }

        $playerInviteCode = PlayerInviteCode::where('domain',$domain)->first();
        if($playerInviteCode){
            $h5url    = CarrierCache::getCarrierMultipleConfigure($playerInviteCode->carrier_id,'h5url',$playerInviteCode->prefix);
            $h5urlArr = explode(',',$h5url);
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['url'=>'https://'.$playerInviteCode->code.'.'.$h5urlArr[0]]);
        } else{
            return $this->returnApiJson(config('language')[$this->language]['error419'], 0);
        }
    }

    public function newGuaranteedList1()
    {
       $input                  = request()->all();
       if(!isset($input['game_category']) || !in_array($input['game_category'], [1,2,3,4,5,6,7])){
           return $this->returnApiJson(config('language')[$this->language]['error420'], 0);
       }
       $carrierGuaranteed      =  CarrierGuaranteed::where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->where('game_category',$input['game_category'])->orderBy('sort','asc')->get();
       
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$carrierGuaranteed);
    }


    public function noticeList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query                  = CarrierNotice::where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->orderBy('sort','desc')->orderBy('updated_at','desc');

        $total                  = $query->count();
        $data                   = $query->skip($offset)->take($pageSize)->get();

        return returnApiJson(config('language')[$this->language]['success1'], 1,['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }
}