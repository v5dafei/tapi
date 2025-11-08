<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Carrier\BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis as Redis;

class AuthController extends BaseController
{
    // 登录
    public function login()
    {
        \Log::info('进入登录');
        $input            = request()->all();

        if (!isset($input['username']) || empty($input['username'])) {
            return returnApiJson("对不起, 您输入的用户名为空!", 0);
        }

        if (!isset($input['password']) || empty($input['password'])) {
            return returnApiJson("对不起, 您输入的密码为空!", 0);
        }

        $cacheKey   = "carrier_login_error_" .$input['username'];

        if(cache()->get($cacheKey,0)==5){
            return returnApiJson("对不起, 错误次数太多，请3分钟之后再试!", 0);
        }

        // 商户登录验证
        $credentials = ['username' => $input['username'], 'password' => $input['password']];

        if (!$token = auth('carrier')->attempt($credentials)) {

            if(cache()->get($cacheKey,0)==0){
                cache()->put($cacheKey, 1, now()->addMinutes(3));
            } else {
                cache()->put($cacheKey,cache()->get($cacheKey)+1, now()->addMinutes(3));
            }
            return returnApiJson("对不起, 用户名或密码错误!", 0);
        }

        $admin = auth('carrier')->user();

        // 如果没有绑定  不用验证谷歌验证
        if ($admin->bind_google_status == 1) {
            $oneCode = trim(request("googlecode"));
            if (empty($oneCode)) {
                return returnApiJson("谷歌验证码不能为空", 0);
            }

            $ga = new \PHPGangsta_GoogleAuthenticator();
            $checkResult = $ga->verifyCode($admin->remember_token, $oneCode, 1); // 2 = 2 * 30秒时钟容差
            if (!$checkResult) {
                return returnApiJson("谷歌认证失败!", 0);
            }
        }
 
        $adminWhiteIpList = CarrierCache::getCarrierConfigure($this->carrier->id,'admin_white_ip_list');
        $currentIp        = real_ip();

        if(!empty($adminWhiteIpList)){
            $ips = explode(',',$adminWhiteIpList);
            if(!in_array($currentIp, $ips) && $admin->is_super_admin==0){
                return returnApiJson("对不起,此IP不允许登录!", 0);
            }
        }

        $data = [
            'token'               => $token,
            'token_type'          => 'bearer',
            'expires_in'          => auth('carrier')->factory()->getTTL() * 60,
            'ws_token'            => md5($token),
            'id'                  => $admin->id,
            'username'            => $admin->username,
            'is_super'            => $admin->is_super_admin,
            'is_super_admin'      => $admin->is_super_admin,
            'carrier_id'          => $this->carrier->id,
            'only_lottery_odds'   => 1,
            'is_transform_k'      => 0,
            'transform_k_decimal' => 0,
            'sign'                => strtolower($this->carrier->sign),
            'is_more_site'        => 1,
            'selfoperated'        => 1
        ];

        //超级管理员
        if($admin->is_super_admin){
            $permissions =  Permission::orderBy('id','asc')->where('frontroute','<>','')->get();
        } else {
            $permissionids = PermissionServiceTeam::where('service_team_id',$admin->team_id)->pluck('permission_id')->toArray();
            $permissions   = Permission::whereIn('id',$permissionids)->orderBy('id','asc')->where('frontroute','<>','')->get();
        }

        $data['permissions'] = $permissions;

        $admin->login_at        = date('Y-m-d H:i:s');

        $admin->save();

        return returnApiJson('登录成功', 1,$data);
    }

    // 登录
    public function switchLogin()
    {
        $input            = request()->all();

        if(!isset($input['sign']) || empty($input['sign'])){
            return returnApiJson("对不起, 网站Sign不能为空!", 0);
        }

        $carrier = Carrier::where('sign',$input['sign'])->first();

        if(!$carrier){
            return returnApiJson("对不起, 此站点不存在!", 0);
        }

        $token            = auth('carrier')->login($this->carrierUser);
        $admin            = auth('carrier')->user();

        $adminWhiteIpList = CarrierCache::getCarrierConfigure($carrier->id,'admin_white_ip_list');
        $currentIp        = real_ip();

        if(!empty($adminWhiteIpList)){
            $ips = explode(',',$adminWhiteIpList);
            if(!in_array($currentIp, $ips) && $admin->is_super_admin==0){
                return returnApiJson("对不起,此IP不允许登录!", 0);
            }
        }


        $data = [
            'token'               => $token,
            'token_type'          => 'bearer',
            'expires_in'          => auth('carrier')->factory()->getTTL() * 60,
            'ws_token'            => md5($token),
            'id'                  => $admin->id,
            'username'            => $admin->username,
            'is_super'            => $admin->is_super_admin,
            'is_super_admin'      => $admin->is_super_admin,
            'carrier_id'          => $carrier->id,
            'only_lottery_odds'   => 1,
            'is_transform_k'      => 0,
            'transform_k_decimal' => 0,
            'sign'                => strtolower($carrier->sign),
            'is_more_site'        => 1
        ];

        //超级管理员
        if($admin->is_super_admin){
            $permissions =  Permission::orderBy('id','asc')->where('frontroute','<>','')->get();
        } else {
            $permissionids = PermissionServiceTeam::where('service_team_id',$admin->team_id)->pluck('permission_id')->toArray();
            $permissions   = Permission::whereIn('id',$permissionids)->orderBy('id','asc')->where('frontroute','<>','')->get();
            
        }

        $data['permissions'] = $permissions;

        $admin->login_at        = date('Y-m-d H:i:s');

        $admin->save();

        return returnApiJson('登录成功', 1,$data);
    }

    // 获取谷歌验证
    public function getGoogle()
    {
        $admin = auth('carrier')->user();
        if (!empty($admin->google_img) && !empty($admin->remember_token)) {
            return returnApiJson("获取成功!!", 1, ['image_url' => $admin->google_img, 'secret' => $admin->remember_token]);
        }
        // 谷歌验证
        $ga          = new \PHPGangsta_GoogleAuthenticator();
        $secret      = $ga->createSecret();
        $checkResult = $ga->getQRCodeGoogleUrl($this->carrier->sign, $secret, 'googleVerify'); // 2 = 2 * 30秒时钟容差

        CarrierUser::where('id',$admin->id)->update(['remember_token' => $secret, 'google_img' => $checkResult]);

        return returnApiJson("获取成功!!", 1, ['image_url' => $checkResult, 'secret' => $secret]);
    }

    public function bindGoogle()
    {
        $admin              = auth('carrier')->user();
        $bind_google_status = trim(request("bind_google_status"));

        if (empty($admin->google_img) || empty($admin->remember_token)) {
            return returnApiJson("请先获取谷歌验证码!", 0);
        }

        $oneCode            = trim(request("code"));
        if (empty($oneCode)) {
            return returnApiJson("谷歌验证码不能为空", 0);
        }

        $ga          = new \PHPGangsta_GoogleAuthenticator();
        $checkResult = $ga->verifyCode($admin->remember_token, $oneCode, 2); // 2 = 2 * 30秒时钟容差
        if (!$checkResult) {
            return returnApiJson("谷歌认证失败!", 0);
        }

        CarrierUser::where('id',$admin->id)->update(['bind_google_status' => $bind_google_status]);
        return returnApiJson("开启成功!", 1);

    }

    // 关闭按钮
    public function closeGoogle($id=0)
    {
        $admin              = auth('carrier')->user();
        $data = [
            'remember_token' => '',
            'google_img'      => '',
            'bind_google_status' => 0,
        ];
       
        CarrierUser::where('id', $id)->update($data);
        
        return returnApiJson('操作成功', 1);
    }

    public function sendcode()
    {
        $input    = request()->all();

        if (!isset($input['username']) || empty($input['username'])) {
            return returnApiJson("对不起, 您输入的用户名为空!", 0);
        }

        if (!isset($input['password']) || empty($input['password'])) {
            return returnApiJson("对不起, 您输入的密码为空!", 0);
        }

        // 登录验证
        $credentials = ['username' => $input['username'], 'password' => $input['password']];

        if (!$token = auth('carrier')->attempt($credentials)) {
            return returnApiJson("对不起, 用户名或密码错误!", 0);
        }

        // 业务数据更正
        $admin            = auth('carrier')->user();
        $ip               = real_ip();
        $code             = mt_rand(10000, 99999);

        $key   = 'carrier_tg_login_code_' . md5($input['username']);
        cache()->put($key, $code, now()->addMinutes(3));

        $text  = '<b>用户名 : '.$input['username'].'</b>' . chr(10);
        $text .= '<b>I        P : '.$ip.'</b>' . chr(10);
        $text .= '<b>验证码 : '.$code.'</b>';

        if($input['username']=='super_admin'){
            $data = ['text'=>$text,'carrier_id'=> 0];
        } else {
            $data = ['text'=>$text,'carrier_id'=> $admin->carrier_id];
        }
       
        $res  = TelegramJob::dispatch($data);

        if ($res) {
            return returnApiJson("发送安全码成功, 请从相关群组获取!!", 1);
        } else {
            return returnApiJson($res['msg'], 0);
        }
    }

    public function updatePassword()
    {
        $input    = request()->all();

        if (!isset($input['newPassword']) || empty($input['newPassword'])) {
            return returnApiJson("对不起, 您输入的新密码为空!", 0);
        }

        if (!isset($input['password']) || empty($input['password'])) {
            return returnApiJson("对不起, 您输入的密码为空!", 0);
        }

        if(!\Hash::check($input['password'], $this->carrierUser->password)) {
            return returnApiJson("对不起, 您输入的密码不正确!", 0);
        }
        
        $this->carrierUser->password = \Hash::make($input['newPassword']);
        $this->carrierUser->save();

        return returnApiJson("更改密码成功!!", 1);
    }

    // 登出
    public function logout()
    {
        auth()->guard("carrier")->logout();
        return returnApiJson('登出成功!', 1);
    }

    protected function guard()
    {
        return Auth::guard('carrier');
    }

    public function username()
    {
        return 'username';
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('carrier')->refresh());
    }
}
