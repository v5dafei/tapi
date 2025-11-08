<?php
namespace App\Observers;

use App\Models\CarrierPreFixDomain;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\Conf\CarrierPayChannel;
use App\Models\Map\CarrierGamePlat;
use App\Models\CarrierPlayerGrade;
use App\Models\Player;
use App\Models\PlayerLevel;
use App\Lib\Cache\CarrierCache;
use App\Models\Log\PrefixVersion;

class CarrierPreFixDomainObserver
{
    public function created(CarrierPreFixDomain $carrierPreFixDomain)
    {
        $insertData = [];

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'is_maintain';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否开启维护';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'is_allow_general_agent';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否允许注册总代';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'is_allow_player_register';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '是否允许注册 1=是，0=否';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_eidt_telehone_verification';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '修改手机号需要验证码(1=是,0=否)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'register_real_name';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否启用实名注册(1=是,0=否)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'carrier_register_telehone';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '注册必填手机号(1=是,0=否)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_register_behavior_verification';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '注册启用形为验证码(1=是,0=否)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_login_behavior_verification';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '登录启用形为验证码(1=是,0=否)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;
        
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_recharge';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '开启充值';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'site_transfer_method';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '允许站内转帐';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'is_show_joint_venture';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否显示合营计划';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_safe_box';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '启用保箱险';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'withdrawal_need_sms';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '取款需要手机验证码';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_rankings';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '启用排行榜';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'rankings_type';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '排行榜类型(1=流水排行榜,2=业绩排行榜)'; 
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'rankings_cycle';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '排行榜周期(1=1天,2=同分红周期)'; 
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'rankings_performance_low';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '最低上榜流水/业绩'; 
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'android_down_url';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '安卓APP下载地址(多个逗号隔开)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'app_down_url';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '苹果APP下载地址(多个逗号隔开)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'h5url';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '域名推广(多个逗号隔开，例如aaa.com,bbb.com)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'official_url';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '官网地址';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'site_title';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '网站名称';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'default_nick_name';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '默认昵称';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'show_hot_game_number';
        $rows['value']            = 20;
        $rows['type']             = 0;
        $rows['remark']           = '显示热门游戏数量';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'id_length';
        $rows['value']            = 5;
        $rows['type']             = 0;
        $rows['remark']           = '玩家ID长度(5到7位)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'player_max_register_one_ip_minute';
        $rows['value']            = 3;
        $rows['type']             = 0;
        $rows['remark']           = '单IP注册数';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'register_receive_activityid';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '注册送活动ID';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'site_online_time';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '站点上线时间';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'finance_min_withdraw';
        $rows['value']            = 103;
        $rows['type']             = 0;
        $rows['remark']           = '系统最小提款金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'min_withdrawal_usdt';
        $rows['value']            = 500;
        $rows['type']             = 0;
        $rows['remark']           = 'USDT最小提币金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'withdraw_ratefee';
        $rows['value']            = 2;
        $rows['type']             = 0;
        $rows['remark']           = '提现手续费';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'alipay_withdraw_ratefee';
        $rows['value']            = 5;
        $rows['type']             = 0;
        $rows['remark']           = '支付宝提款手续费';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'carrier_agent_marquee_notice';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '代理后台跑马灯';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'carrier_marquee_notice';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '跑马灯公告';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //默认保底分红设置
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_fixed_earnings';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '启用固定分红';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_fixed_guaranteed';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '启用固定保底';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_auto_guaranteed_upgrade';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '保底自动升级';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'default_earnings';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '固定分红';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'default_guaranteed';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '固定保底';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //会员返佣设置
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enabele_setting_dividends';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '允许设置分红';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enabele_setting_guaranteed';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '允许设置保底';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'guaranteed_level_difference';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '保底级差';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'limit_highest_dividend';
        $rows['value']            = 58;
        $rows['type']             = 0;
        $rows['remark']           = '需审核分红';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'limit_highest_guaranteed';
        $rows['value']            = 210;
        $rows['type']             = 0;
        $rows['remark']           = '需审核保底';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'dividend_level_difference';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '分红级差';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'dividend_enumerate';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '分红枚举';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //会员提现流水比例设置
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'is_bet_flow_convert';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '启用会员提现流水折算';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;


        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enabel_agent_commissionflow_single';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '代理佣金流水单独结算';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'casino_betflow_calculate_rate';
        $rows['value']            = 100;
        $rows['type']             = 0;
        $rows['remark']           = '真人流水比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'electronic_betflow_calculate_rate';
        $rows['value']            = 100;
        $rows['type']             = 0;
        $rows['remark']           = '电子流水比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'esport_betflow_calculate_rate';
        $rows['value']            = 100;
        $rows['type']             = 0;
        $rows['remark']           = '电竞流水比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'fish_betflow_calculate_rate';
        $rows['value']            = 100;
        $rows['type']             = 0;
        $rows['remark']           = '捕鱼流水比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'card_betflow_calculate_rate';
        $rows['value']            = 100;
        $rows['type']             = 0;
        $rows['remark']           = '棋牌流水比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'lottery_betflow_calculate_rate';
        $rows['value']            = 100;
        $rows['type']             = 0;
        $rows['remark']           = '彩票流水比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'sport_betflow_calculate_rate';
        $rows['value']            = 100;
        $rows['type']             = 0;
        $rows['remark']           = '体育流水比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //会员返水设置
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_bet_gradient_rebate';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '开启投注返水';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'rebate_method';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '返水方式(0=自助领取，1=系统发放)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'video_bet_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '视讯投注梯度返水';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'ele_bet_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '电子投注梯度返水';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'esport_bet_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '电竞投注梯度返水';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'card_bet_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '棋牌投注梯度返水';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'sport_bet_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '体育投注梯度返水';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'fish_bet_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '捕鱼投注梯度返水';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'lott_bet_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '彩票投注梯度返水';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //直属投注梯度返佣设置
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_invite_gradient_rebate';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '开启直属投注梯度返佣';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'video_invite_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '直属视讯投注梯度返佣';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'ele_invite_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '直属电子投注梯度返佣';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'esport_invite_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '直属电竞投注梯度返佣';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'card_invite_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '直属棋牌投注梯度返佣';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'sport_invite_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '直属体育投注梯度返佣';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'fish_invite_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '直属捕鱼投注梯度返佣';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'lott_invite_gradient_rebate';
        $rows['value']            = json_encode([['probability'=>'1','bonus'=>'0.00']]);
        $rows['type']             = 0;
        $rows['remark']           = '直属彩票投注梯度返佣';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //代理保底流水
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'agent_casino_betflow_calculate_rate';
        $rows['value']            = 15;
        $rows['type']             = 0;
        $rows['remark']           = '代理真人保底比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'agent_electronic_betflow_calculate_rate';
        $rows['value']            = 50;
        $rows['type']             = 0;
        $rows['remark']           = '代理电子保底比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'agent_esport_betflow_calculate_rate';
        $rows['value']            = 15;
        $rows['type']             = 0;
        $rows['remark']           = '代理电竞保底比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'agent_fish_betflow_calculate_rate';
        $rows['value']            = 15;
        $rows['type']             = 0;
        $rows['remark']           = '代理捕鱼保底比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'agent_card_betflow_calculate_rate';
        $rows['value']            = 15;
        $rows['type']             = 0;
        $rows['remark']           = '代理棋牌保底比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'agent_lottery_betflow_calculate_rate';
        $rows['value']            = 15;
        $rows['type']             = 0;
        $rows['remark']           = '代理彩票保底比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'agent_sport_betflow_calculate_rate';
        $rows['value']            = 15;
        $rows['type']             = 0;
        $rows['remark']           = '代理体育保底比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //盈利加强设置
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_fast_kill';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否启用快杀';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_agent_game_limit';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否限制代理仅能进入电子棋牌捕鱼';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'is_loss_write_betflow';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否仅亏损计入流水';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_batch_register_froze';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '批量注册自动冻结';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'prefix_type';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '场馆如有假游戏仅显示可替换的真游戏(1=是，0=否)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_clean_loss';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否开启清亏损数据';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'clean_loss_amount_cycle';
        $rows['value']            = 7;
        $rows['type']             = 0;
        $rows['remark']           = '清亏损数据X个周期';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'clean_loss_amount';
        $rows['value']            = 200;
        $rows['type']             = 0;
        $rows['remark']           = '清亏损负金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'agent_support_withdraw_amount';
        $rows['value']            = 200;
        $rows['type']             = 0;
        $rows['remark']           = '代理扶持提现金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'no_fake_pg_playerids';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '不进假PG名单(逗号分隔)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'materialIds';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '素材号ID(多个用逗号间隔)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'fake_withdraw_player_ids';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '提现号ID(多个逗号间隔)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'fake_withdraw_limit';
        $rows['value']            = 2000;
        $rows['type']             = 0;
        $rows['remark']           = '提现号限制金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'first_deposit_activity_plus';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '首存1加1活动ID';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'forcibly_joinfakegame_activityid';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '强制点杀活动ID';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'batch_register_ip_number';
        $rows['value']            = 5;
        $rows['type']             = 0;
        $rows['remark']           = '批量注册同IP注册个数';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'ip_blacklist';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '注册/登录IP黑名单(用逗号间隔)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //体验券设置
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_send_voucher';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '开启充值发放体验券';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_register_gift_code';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '注册是否开启体验券';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'is_show_front_exchange';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '显示前台兑换';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_coupons_bank_store';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否领体验券未充值计入套利银行卡与支付宝库(1=是，0=否)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'stop_exchange_rate';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '下级多少个领取彩金券未充值停止兑换';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'not_included_exchange_rate';
        $rows['value']            = 100;
        $rows['type']             = 0;
        $rows['remark']           = '渠道用户多少个彩金券不计入';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'voucher_withdraw_max_money';
        $rows['value']            = 103;
        $rows['type']             = 0;
        $rows['remark']           = '体验券最高提现金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'voucher_need_recharge_amount';
        $rows['value']            = 200;
        $rows['type']             = 0;
        $rows['remark']           = '体验券需要充值金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'voucher_money';
        $rows['value']            = 25;
        $rows['type']             = 0;
        $rows['remark']           = '充值发放体验券金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'voucher_betflow_multiple';
        $rows['value']            = 15;
        $rows['type']             = 0;
        $rows['remark']           = '充值发放体验券流水限制倍数';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'voucher_valid_day';
        $rows['value']            = 3;
        $rows['type']             = 0;
        $rows['remark']           = '充值发放体验券有效天数';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'voucher_recharge_amount';
        $rows['value']            = 5;
        $rows['type']             = 0;
        $rows['remark']           = '新增1个充值发放X张体验券';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'register_gift_code_amount';
        $rows['value']            = 300;
        $rows['type']             = 0;
        $rows['remark']           = '有效充值金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_voucher_recharge';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '体验券是否必须充值';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'skip_abrbitrageurs_judge_channel';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '跳过白嫖银行卡判定渠道ID(多个用逗号间隔)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'disable_voucher_channel';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '禁止兑换体验券上级ID(多个用逗号间隔)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'disable_voucher_team_channel';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '禁止兑换体验券团队ID(多个用逗号间隔)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //注册赠送设置
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_register_img_verification';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否开启注册图形验证码';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_login_img_verification';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否开启登录图形验证码';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'is_registergift';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否开启注册即送';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'is_bindbankcardorthirdwallet';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '是否绑定银行卡或支付宝';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'register_probability';
        $rows['value']            = json_encode([['giftamount'=>'1','giftmaxamount'=>'1','register_gift_probability'=>'100']]);;
        $rows['type']             = 0;
        $rows['remark']           = '注册赠送金额概率梯度';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'giftmultiple';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '流水倍数';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'registergift_limit_day_number';
        $rows['value']            = 100;
        $rows['type']             = 0;
        $rows['remark']           = '限制人数';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'registergift_limit_cycle';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '注册彩金发放数量限制周期(1=每天，0=永久)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //进入游戏设置
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'third_wallet';
        $rows['value']            = json_encode([]);
        $rows['type']             = 0;
        $rows['remark']           = '用户可绑定钱包及数字币地址';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'disable_withdraw_channel';
        $rows['value']            = json_encode([]);
        $rows['type']             = 0;
        $rows['remark']           = '禁止提现通道';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'finance_min_recharge';
        $rows['value']            = 100;
        $rows['type']             = 0;
        $rows['remark']           = '系统最小充值金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'finance_max_recharge';
        $rows['value']            = 50000;
        $rows['type']             = 0;
        $rows['remark']           = '在线最大存款金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'digital_rate';
        $rows['value']            = 6.3;
        $rows['type']             = 0;
        $rows['remark']           = '存款数字币汇率';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'withdraw_digital_rate';
        $rows['value']            = 6.4;
        $rows['type']             = 0;
        $rows['remark']           = '取款数字币汇率';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'in_r_out_u';
        $rows['value']            = 7;
        $rows['type']             = 0;
        $rows['remark']           = '进本地币出U';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'in_t_out_u';
        $rows['value']            = 8;
        $rows['type']             = 0;
        $rows['remark']           = '存钱包出U';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //人头费
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_capitation_fee';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '开启人头费';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'capitation_fee_type';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '人头费需要审核';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'capitation_fee_rule';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '包括负盈利代理(0=否,1=是)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'capitation_fee_cycle';
        $rows['value']            = 2;
        $rows['type']             = 0;
        $rows['remark']           = '人头费计算周期(1=同分红周期,2=永久)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'is_capitation_first_deposit_calculate';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '人头费首存金额是否记入充值';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'capitation_first_deposit_calculate_activityid';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '人头费首存金额不计入活动ID';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'capitation_fee_recharge_amount';
        $rows['value']            = 1000;
        $rows['type']             = 0;
        $rows['remark']           = '人头费要求存款金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'capitation_fee_bet_flow';
        $rows['value']            = 2000;
        $rows['type']             = 0;
        $rows['remark']           = '人头费要求有效流水';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'capitation_fee_deposit_days';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '人头费累积充值天数';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'capitation_fee_single_recharge_amount';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '人头费要求单笔充值大于X元';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'capitation_fee_gift_amount';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '人头费奖励金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //套利设置
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'arbitrage_game_list';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '刷水游戏列表逗号分隔';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'arbitrage_game_flow_convert';
        $rows['value']            = 10;
        $rows['type']             = 0;
        $rows['remark']           = '刷水游戏代理流水折算';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //数据统计
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'replace_curr_cw_rate';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '总返奖率';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'replace_today_curr_cw_rate';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '本期返奖率';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'pg_replace_curr_cw_rate';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = 'pg总返奖率';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'pg_replace_today_curr_cw_rate';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = 'pg本期返奖率';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'recharge_withdraw_proportion';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '总出款比';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'cycle_recharge_withdraw_proportion';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '本结算周期出款比';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'register_code_recharge';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '注册送存款人数';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'current_intelligent_rate';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '本期赢利百分比';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'agent_single_background';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否独立后台';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'player_dividends_day';
        $rows['value']            = 2;
        $rows['type']             = 0;
        $rows['remark']           = '分红方式分红周期';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'player_dividends_start_day';
        $rows['value']            = date('Y-m-d');
        $rows['type']             = 0;
        $rows['remark']           = '分红结算起始日';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'player_realtime_dividends_start_day';
        $rows['value']            = date('Y-m-d');
        $rows['type']             = 0;
        $rows['remark']           = '分红实时分红计算起始日';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'wallet_passage_rate';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '三方钱包代理收费点位';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'no_wallet_passage_rate';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '非三方钱包代理收费点位';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_tongbao_method';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '启用保底通宝模式';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'tongbao_rate';
        $rows['value']            = 50;
        $rows['type']             = 0;
        $rows['remark']           = '保底通宝分红比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'enable_dividends_tongbao_method';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '启用分红通宝模式';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'tongbao_dividends_rate';
        $rows['value']            = 50;
        $rows['type']             = 0;
        $rows['remark']           = '分红通宝分红比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'dividends_receive_method';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '分红领取方式(1=直接发入帐号，2=自助领取)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'operating_expenses';
        $rows['value']            = 20;
        $rows['type']             = 0;
        $rows['remark']           = '运营费用';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'bonus_rate';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '彩金费率';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'prefix_decreasing_rate';
        $rows['value']            = 20;
        $rows['type']             = 0;
        $rows['remark']           = '专享佣金:未达标递减比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'prefix_exclusive_rate';
        $rows['value']            = 70;
        $rows['type']             = 0;
        $rows['remark']           = '专享佣金:比例';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'player_cycle_deposit_amount';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '有效活跃定义:周期累积金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'player_cycle_continue_deposit';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '有效活跃定义:周期续存金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'prefix_exclusive_active';
        $rows['value']            = 5;
        $rows['type']             = 0;
        $rows['remark']           = '专享佣金:要求活跃数';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'player_cycle_betflow';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '有效活跃定义:有效流水';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'player_dividends_method';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '分红结算方式';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'one_and_one_recharge_amount';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '1+1活动充值金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'one_and_one_withdrawal_amount';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '1+1活动提现金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'open_sign_in';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '是否开启签到';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'sign_in_category';
        $rows['value']            = 3;
        $rows['type']             = 0;
        $rows['remark']           = '签到类型(1=每日签倒，2=累积签倒, 3=连续签倒)';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'sign_in_day_gift';
        $rows['value']            = json_encode(array());
        $rows['type']             = 0;
        $rows['remark']           = '签到天数及赠送金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'sign_in_flow_limit_multiple';
        $rows['value']            = 1;
        $rows['type']             = 0;
        $rows['remark']           = '签到赠送金额流水倍数';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'sign_in_need_recharge_amount';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '签到当天需要充值金额';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'sign_in_need_bet_flow';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '签到当天需要有效投注';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'site_stock';
        $rows['value']            = 0;
        $rows['type']             = 0;
        $rows['remark']           = '站点库存';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //删除短链接设置
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'short_link_no_register';
        $rows['value']            = 7;
        $rows['type']             = 0;
        $rows['remark']           = '短链接X天没有注册与充值';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'no_delete_short_link';
        $rows['value']            = 3000;
        $rows['type']             = 0;
        $rows['remark']           = '团队总充值X不删短链接';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        //客服提示语
        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'kefu_link';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '客服链接';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'marketing_contact';
        $rows['value']            = json_encode([['name'=>'','contact'=>'','icon'=>'']]);
        $rows['type']             = 0;
        $rows['remark']           = '官方联系方式';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        $rows                     = [];
        $rows['carrier_id']       = $carrierPreFixDomain->carrier_id;
        $rows['prefix']           = $carrierPreFixDomain->prefix;
        $rows['sign']             = 'live_broadcast_awards';
        $rows['value']            = '';
        $rows['type']             = 0;
        $rows['remark']           = '直播爆奖游戏';
        $rows['created_at']       = date('Y-m-d H:i:s');
        $rows['updated_at']       = date('Y-m-d H:i:s');
        $insertData[]             = $rows;

        \DB::table('conf_carrier_multiple_front')->insert($insertData);

        $prefixVersion             = new PrefixVersion();
        $prefixVersion->carrier_id = $carrierPreFixDomain->carrier_id;
        $prefixVersion->prefix     = $carrierPreFixDomain->prefix;
        $prefixVersion->save();

         //创建用户分组
        $insertPlayerLevel =[
            ['carrier_id' =>$carrierPreFixDomain->carrier_id,'groupname'=>'默认','is_system'=>1,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),'is_default'=>1,'sort'=>1,'rechargenumber'=>0,'single_maximum_recharge'=>0,'accumulation_recharge'=>0,'prefix'=>$carrierPreFixDomain->prefix,'game_line_id'=>0],
            ['carrier_id' =>$carrierPreFixDomain->carrier_id,'groupname'=>'高盈利','is_system'=>1,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),'is_default'=>0,'sort'=>2,'rechargenumber'=>0,'single_maximum_recharge'=>0,'accumulation_recharge'=>0,'prefix'=>$carrierPreFixDomain->prefix,'game_line_id'=>0],
            ['carrier_id' =>$carrierPreFixDomain->carrier_id,'groupname'=>'套利','is_system'=>1,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),'is_default'=>0,'sort'=>3,'rechargenumber'=>0,'single_maximum_recharge'=>0,'accumulation_recharge'=>0,'prefix'=>$carrierPreFixDomain->prefix,'game_line_id'=>0],
            ['carrier_id' =>$carrierPreFixDomain->carrier_id,'groupname'=>'学前班','is_system'=>2,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),'is_default'=>0,'sort'=>4,'rechargenumber'=>1,'single_maximum_recharge'=>100,'accumulation_recharge'=>100,'prefix'=>$carrierPreFixDomain->prefix,'game_line_id'=>0],
            ['carrier_id' =>$carrierPreFixDomain->carrier_id,'groupname'=>'幼儿园','is_system'=>2,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),'is_default'=>0,'sort'=>5,'rechargenumber'=>5,'single_maximum_recharge'=>100,'accumulation_recharge'=>100,'prefix'=>$carrierPreFixDomain->prefix,'game_line_id'=>0],
            ['carrier_id' =>$carrierPreFixDomain->carrier_id,'groupname'=>'小学','is_system'=>2,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),'is_default'=>0,'sort'=>6,'rechargenumber'=>10,'single_maximum_recharge'=>1000,'accumulation_recharge'=>100000,'prefix'=>$carrierPreFixDomain->prefix,'game_line_id'=>0],
            ['carrier_id' =>$carrierPreFixDomain->carrier_id,'groupname'=>'初中','is_system'=>2,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),'is_default'=>0,'sort'=>7,'rechargenumber'=>10,'single_maximum_recharge'=>1000,'accumulation_recharge'=>1000000,'prefix'=>$carrierPreFixDomain->prefix,'game_line_id'=>0],
            ['carrier_id' =>$carrierPreFixDomain->carrier_id,'groupname'=>'高中','is_system'=>2,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),'is_default'=>0,'sort'=>8,'rechargenumber'=>10,'single_maximum_recharge'=>1000,'accumulation_recharge'=>1500000,'prefix'=>$carrierPreFixDomain->prefix,'game_line_id'=>0],
            ['carrier_id' =>$carrierPreFixDomain->carrier_id,'groupname'=>'大学','is_system'=>2,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),'is_default'=>0,'sort'=>9,'rechargenumber'=>10,'single_maximum_recharge'=>10000,'accumulation_recharge'=>25000000,'prefix'=>$carrierPreFixDomain->prefix,'game_line_id'=>0],
            ['carrier_id' =>$carrierPreFixDomain->carrier_id,'groupname'=>'毕业','is_system'=>2,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),'is_default'=>0,'sort'=>10,'rechargenumber'=>10,'single_maximum_recharge'=>10000,'accumulation_recharge'=>50000000,'prefix'=>$carrierPreFixDomain->prefix,'game_line_id'=>0]

        ];

        \DB::table('inf_carrier_player_level')->insert($insertPlayerLevel);

        $existCarrierPlayerGrade = CarrierPlayerGrade::where('prefix',$carrierPreFixDomain->prefix)->first();
        if(!$existCarrierPlayerGrade){
            //默认会员等级
            $playlevels = [
                [
                    'level_name'     => 'VIP1',
                    'carrier_id'     => $carrierPreFixDomain->carrier_id,
                    'prefix'         => $carrierPreFixDomain->prefix,
                    'img'            => 'vip0_bg.png',
                    'sort'           => 1,
                    'upgrade_rule'   => serialize(['availablebet'=>0]),
                    'withdrawcount'  => 5,
                    'updategift'     => 0,
                    'birthgift'      => 0,
                    'weekly_salary'  => 0,
                    'monthly_salary' => 0,
                    'turnover_multiple' => 0,
                    'is_default'     => 1,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ],
                [
                    'level_name'     => 'VIP2',
                    'carrier_id'     => $carrierPreFixDomain->carrier_id,
                    'prefix'         => $carrierPreFixDomain->prefix,
                    'img'            => 'vip1_bg.png',
                    'sort'           => 2,
                    'upgrade_rule'   => serialize(['availablebet'=>30000]),
                    'withdrawcount'  => 5,
                    'updategift'     => 10,
                    'birthgift'      => 8,
                    'weekly_salary'  => 0,
                    'monthly_salary' => 0,
                    'turnover_multiple' => 10,
                    'is_default'     => 0,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ],
                [
                    'level_name'     => 'VIP3',
                    'carrier_id'     => $carrierPreFixDomain->carrier_id,
                    'prefix'         => $carrierPreFixDomain->prefix,
                    'img'            => 'vip2_bg.png',
                    'sort'           => 3,
                    'upgrade_rule'   => serialize(['availablebet'=>200000]),
                    'withdrawcount'  => 5,
                    'updategift'     => 20,
                    'birthgift'      => 18,
                    'weekly_salary'  => 0,
                    'monthly_salary' => 0,
                    'turnover_multiple' => 10,
                    'is_default'     => 0,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ],
                [
                    'level_name'     => 'VIP4',
                    'carrier_id'     => $carrierPreFixDomain->carrier_id,
                    'prefix'         => $carrierPreFixDomain->prefix,
                    'img'            => 'vip3_bg.png',
                    'sort'           => 4,
                    'upgrade_rule'   => serialize(['availablebet'=>500000]),
                    'withdrawcount'  => 5,
                    'updategift'     => 188,
                    'birthgift'      => 38,
                    'weekly_salary'  => 0,
                    'monthly_salary' => 0,
                    'turnover_multiple' => 10,
                    'is_default'     => 0,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ],
                [
                    'level_name'     => 'VIP5',
                    'carrier_id'     => $carrierPreFixDomain->carrier_id,
                    'prefix'         => $carrierPreFixDomain->prefix,
                    'img'            => 'vip4_bg.png',
                    'sort'           => 5,
                    'upgrade_rule'   => serialize(['availablebet'=>1500000]),
                    'withdrawcount'  => 10,
                    'updategift'     => 388,
                    'birthgift'      => 58,
                    'weekly_salary'  => 0,
                    'monthly_salary' => 0,
                    'turnover_multiple' => 10,
                    'is_default'     => 0,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ],
                [
                    'level_name'     => 'VIP6',
                    'carrier_id'     => $carrierPreFixDomain->carrier_id,
                    'prefix'         => $carrierPreFixDomain->prefix,
                    'img'            => 'vip5_bg.png',
                    'sort'           => 6,
                    'upgrade_rule'   => serialize(['availablebet'=>4500000]),
                    'withdrawcount'  => 10,
                    'updategift'     => 888,
                    'birthgift'      => 88,
                    'weekly_salary'  => 0,
                    'monthly_salary' => 0,
                    'turnover_multiple' => 10,
                    'is_default'     => 0,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ],
                [
                    'level_name'     => 'VIP7',
                    'carrier_id'     => $carrierPreFixDomain->carrier_id,
                    'prefix'         => $carrierPreFixDomain->prefix,
                    'img'            => 'vip6_bg.png',
                    'sort'           => 7,
                    'upgrade_rule'   => serialize(['availablebet'=>13000000]),
                    'withdrawcount'  => 10,
                    'updategift'     => 1388,
                    'birthgift'      => 188,
                    'weekly_salary'  => 0,
                    'monthly_salary' => 0,
                    'turnover_multiple' => 10,
                    'is_default'     => 0,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ],
                [
                    'level_name'     => 'VIP8',
                    'carrier_id'     => $carrierPreFixDomain->carrier_id,
                    'prefix'         => $carrierPreFixDomain->prefix,
                    'img'            => 'vip7_bg.png',
                    'sort'           => 8,
                    'upgrade_rule'   => serialize(['availablebet'=>30000000]),
                    'withdrawcount'  => 20,
                    'updategift'     => 3888,
                    'birthgift'      => 588,
                    'weekly_salary'  => 0,
                    'monthly_salary' => 0,
                    'turnover_multiple' => 10,
                    'is_default'     => 0,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ],
                [
                    'level_name'     => 'VIP9',
                    'carrier_id'     => $carrierPreFixDomain->carrier_id,
                    'prefix'         => $carrierPreFixDomain->prefix,
                    'img'            => 'vip8_bg.png',
                    'sort'           => 9,
                    'upgrade_rule'   => serialize(['availablebet'=>100000000]),
                    'withdrawcount'  => 20,
                    'updategift'     => 13888,
                    'birthgift'      => 888,
                    'weekly_salary'  => 0,
                    'monthly_salary' => 0,
                    'turnover_multiple' => 10,
                    'is_default'     => 0,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ],
                [
                    'level_name'     => 'VIP10',
                    'carrier_id'     => $carrierPreFixDomain->carrier_id,
                    'prefix'         => $carrierPreFixDomain->prefix,
                    'img'            => 'vip9_bg.png',
                    'sort'           => 10,
                    'upgrade_rule'   => serialize(['availablebet'=>300000000]),
                    'withdrawcount'  => 20,
                    'updategift'     => 38888,
                    'birthgift'      => 1888,
                    'weekly_salary'  => 0,
                    'monthly_salary' => 0,
                    'turnover_multiple' => 10,
                    'is_default'     => 0,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                ]
            ];
            \DB::table('inf_carrier_player_grade')->insert($playlevels);
        }

        //生成系统默认代理
        $defaultPlayerLevel      = CarrierPlayerGrade::where('carrier_id',$carrierPreFixDomain->carrier_id)->where('prefix',$carrierPreFixDomain->prefix)->where('is_default',1)->first();
        $lastPlayer              = Player::orderBy('player_id','desc')->first();

        if($lastPlayer){
            $nextPlayerId        = $lastPlayer->player_id +1;
        } else {
            $nextPlayerId        = $carrierPreFixDomain->carrier_id+1000;
        }

        $mtRand                  = mt_rand(1,9490);

        $defaultPlayerGrade      = PlayerLevel::where('carrier_id',$carrierPreFixDomain->carrier_id)->where('prefix',$carrierPreFixDomain->prefix)->where('is_default',1)->first();

        //创建默认
        $player                  = new Player();
        $player->player_id       = $nextPlayerId;
        $player->top_id          = $nextPlayerId;
        $player->parent_id       = 0;
        $player->user_name       = CarrierCache::getCarrierConfigure($carrierPreFixDomain->carrier_id,'default_user_name');
        $player->is_tester       = 0;
        $player->password        = bcrypt(randPassword());
        $player->carrier_id      = $carrierPreFixDomain->carrier_id;
        $player->player_level_id = $defaultPlayerLevel->id;
        $player->type            = 1;
        $player->level           = 1;
        $player->player_group_id = $defaultPlayerGrade->id;
        $player->nick_name       = '';
        $player->prefix          = $carrierPreFixDomain->prefix;
        $player->rid             = $nextPlayerId;
        $player->save();


        //插入横版导航
        $horizontalMenu =[
            [
                'carrier_id' => $carrierPreFixDomain->carrier_id,
                'prefix'     => $carrierPreFixDomain->prefix,
                'type'       => 'hotgamelist',
                'key'        => 'HOT',
                'api'        => '/api/hotgamelist',
                'sort'       => 100,
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrierPreFixDomain->carrier_id,
                'prefix'     => $carrierPreFixDomain->prefix,
                'type'       => 'electronic',
                'key'        => 'RNG',
                'api'        => '/api/electronic/categorylist',
                'sort'       => 99,
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrierPreFixDomain->carrier_id,
                'prefix'     => $carrierPreFixDomain->prefix,
                'type'       => 'live',
                'key'        => 'LIVE',
                'api'        => '/api/live/list',
                'sort'       => 98,
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrierPreFixDomain->carrier_id,
                'prefix'     => $carrierPreFixDomain->prefix,
                'type'       => 'card',
                'key'        => 'PVP',
                'api'        => '/api/card/list',
                'sort'       => 97,
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrierPreFixDomain->carrier_id,
                'prefix'     => $carrierPreFixDomain->prefix,
                'type'       => 'lottery',
                'key'        => 'LOTT',
                'api'        => '/api/lottery/list',
                'sort'       => 96,
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrierPreFixDomain->carrier_id,
                'prefix'     => $carrierPreFixDomain->prefix,
                'type'       => 'fish',
                'key'        => 'FISH',
                'api'        => '/api/fish/list',
                'sort'       => 95,
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrierPreFixDomain->carrier_id,
                'prefix'     => $carrierPreFixDomain->prefix,
                'type'       => 'sport',
                'key'        => 'SPORT',
                'api'        => '/api/sport/list',
                'sort'       => 94,
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrierPreFixDomain->carrier_id,
                'prefix'     => $carrierPreFixDomain->prefix,
                'type'       => 'esport',
                'key'        => 'ESPORT',
                'api'        => '/api/esport/list',
                'sort'       => 93,
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        \DB::table('inf_carrier_horizontal_menu')->insert($horizontalMenu);

        //插入游戏点位表
        $carrierGamePlats = CarrierGamePlat::where('carrier_id',$carrierPreFixDomain->carrier_id)->get();
        $insertData       = [];
        foreach ($carrierGamePlats as $key => $value) {
            $rows                      = [];
            $rows['carrier_id']        = $carrierPreFixDomain->carrier_id;
            $rows['game_plat_id']      = $value->game_plat_id;
            $rows['point']             = $value->point;
            $rows['prefix']            = $carrierPreFixDomain->prefix;
            $rows['created_at']        = date('Y-m-d H:i:s');
            $rows['updated_at']        = date('Y-m-d H:i:s');

            $insertData[]              = $rows;
        }

        \DB::table('map_carrier_prefix_game_plats')->insert($insertData);
    }

    public function updated(CarrierPreFixDomain $carrierPreFixDomain)
    {
    }

    public function deleted(CarrierPreFixDomain $carrierPreFixDomain)
    {
    }
}

