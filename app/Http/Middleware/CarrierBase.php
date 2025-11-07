<?php

namespace App\Http\Middleware;

use App\Models\RolesModel\PermissionServiceTeam;
use App\Models\RolesModel\Permission;
use App\Services\Context;
use App\Lib\Clog;
use Closure;
use App\Models\CarrierIps;
use App\Models\Carrier;
use App\Models\Log\CarrierAdminLog;

class CarrierBase
{

    use Context;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $routeName     = request()->route()->getName();
        $url           = request()->header('Host');
        $url           = str_replace("http://", "", trim($url));
        $explodeArray  = explode('.',$url);

        if(count($explodeArray) != 3) {
             return response()->json(['success'=>false,'message' => '对不起, 请求域名不正确!','data'=>[],'code'=>401],401)->send();
        }

        $topDomain = $explodeArray[1].'.'.$explodeArray[2];
        if($topDomain!= config('main')['carrier_base_url']) {
            return response()->json(['success'=>false,'message' => '对不起, 请求接口域名错误!','data'=>[],'code'=>401],401)->send();
        }

        $explodeheaderArray  = explode('-',$explodeArray[0]);

        if(3 != count($explodeheaderArray) || $explodeheaderArray[0] != 'partner' || $explodeheaderArray[2] != 'api') {
            return response()->json(['success'=>false,'message' => '对不起, 请求接口域名错误!','data'=>[],'code'=>401],401)->send();
        }

        $currCarrier = Carrier::where('sign',strtoupper($explodeheaderArray[1]))->first();
        if(!$currCarrier) {
            return response()->json(['success'=>false,'message' => '对不起, 此运营商不存在!','data'=>[],'code'=>401],401)->send();
        }

        $currCarrierUser = auth("carrier")->user();

        if(!in_array($routeName,config('guest')['carrier'])) {

            $ip  = real_ip();
            $ips = CarrierIps::where('carrier_id',$currCarrier->id)->pluck('login_ip')->toArray();
           /* if(!in_array($ip, $ips)){
                 return response()->json(['success'=>false,'message' => '对不起, 您无权访问!','data'=>[],'code'=>401],401)->send();
            }
            */
            if ( !auth("carrier")->user() ) {
                //$this->error('对不起, 用户未登录!', [], 401, 401);
                return response()->json(['success'=>false,'message' => '对不起, 用户未登录!','data'=>[],'code'=>401],401)->send();
            }

            $input                             = request()->all();
            $realip                            = real_ip();
            $carrierAdminLog                   = new CarrierAdminLog();

            $permission                        = Permission::select('permissions.group_id','permissions.name','permissions.id','permissions.description','a.group_name as sub_group_name','b.group_name')->where('name',$routeName)
                ->leftJoin('permission_group as a','a.id','=','permissions.group_id')
                ->leftJoin('permission_group as b','b.id','=','a.parent_id')
                ->first();

            if($permission && $currCarrierUser->username!='super_admin'){
                    $path = explode(config('main')['carrier_base_url'],request()->url());

                //去除详情内容为空的请求
                if(count($input) > 0 && !array_key_exists('page_index', $input)){
                    $carrierAdminLog->action           = $permission->group_name.'|'.$permission->sub_group_name.'|'.$permission->description;
                    $carrierAdminLog->group_id         = $permission->group_id;
                    $carrierAdminLog->carrieruser_id   = $currCarrierUser->id;
                    $carrierAdminLog->carrier_id       = $currCarrier->id;
                    $carrierAdminLog->user_name        = $currCarrierUser->username;
                    $carrierAdminLog->day              = date('Ymd');
                    $carrierAdminLog->routename        = $path[1];
                    $carrierAdminLog->permissionsid    = $permission->id;
                    $carrierAdminLog->actionTime       = time();
                    $carrierAdminLog->actionIP         = ip2long($realip);
                    $carrierAdminLog->params           = json_encode($input);
                    $carrierAdminLog->save();
                }
            }

            if(!$currCarrierUser->is_super_admin){
                $permissions     = PermissionServiceTeam::where('service_team_id',$currCarrierUser->team_id)->pluck('permission_id')->toArray();
                $teamPermissions = Permission::whereIn('id',$permissions)->pluck('name')->toArray();
                $allPermissions  = Permission::pluck('name')->toArray();
                $ignoreRoute     = [
                    'system/systemnoticelist',
                    'carrier/updatepassword',
                    'player/agents',
                    'carrier/init',
                    'carrier/remainquota',
                    'system/getalllanguage',
                    'system/alllanguages',
                    'carrier/getbalance',
                    'carrier/transferto',
                    'carrier/changerepair',
                    'carrier/allprefixsetting',
                    'carrier/allprefix',
                    'carrier/safedetect',
                    'carrier/banktypepagelist'

                    //'carrier/playerfinanceinfo',
                    //'carrier/playerdigitaladdresslist',
                    //'carrier/playercasinotransfersetting',
                    //'carrier/withdrawaudit',

                    //'lottery/cancelBet'
                ];

                if(!in_array($routeName, $teamPermissions) && !in_array($routeName, $ignoreRoute) ){
                    Clog::payMsg('测试权限', '', ['路由'=>$routeName]);

                    if(!in_array($routeName, $allPermissions)){
                        Clog::payMsg('缺少路由', '', ['路由'=>$routeName]);
                    } else {
                        \Log::info('请求的路由是'.$routeName.'这个未添加权限'); 
                        return response()->json(['success'=>false,'message' => '对不起, 您没有此权限!','data'=>[],'code'=>402],402)->send();
                     }
                }
            }
        }
        return $next($request);
    }
}
