<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\BaseController;
use App\Models\Conf\SysTelegramChannel;
use App\Jobs\TelegramJob;

class AuthController extends BaseController
{
    use Authenticatable;

    // 登录
    public function login()
    {
        $input    = request()->all();

        if (!isset($input['username']) || empty($input['username'])) {
            return returnApiJson("对不起, 您输入的用户名为空!", 0);
        }

        if (!isset($input['password']) || empty($input['password'])) {
            return returnApiJson("对不起, 您输入的密码为空!", 0);
        }

        if (!isset($input['code']) || empty($input['code'])) {
            return returnApiJson("对不起, 您输入的安全码为空!", 0);
        }

        $cacheKey   = "admin_login_error_" .$input['username'];

        if(cache()->get($cacheKey,0)==5){
            return returnApiJson("对不起, 错误次数太多，请3分钟之后再试!", 0);
        }

        $cachecode = cache()->get('admin_tg_login_code_'.md5($input['username']));
        if(!$cachecode || $cachecode != $input['code']){
            return returnApiJson("对不起,您输入的验证码不正确!!!", 0);
        }

        // 商户登录验证
        $credentials = ['username' => $input['username'], 'password' => $input['password']];

        if (!$token = auth('admin')->attempt($credentials)) {

            if(cache()->get($cacheKey,0)==0){
                cache()->put($cacheKey, 1, now()->addMinutes(3));
            } else {
                cache()->put($cacheKey,cache()->get($cacheKey)+1, now()->addMinutes(3));
            }

            return returnApiJson("对不起, 用户名或密码错误!", 0);
        }

        $admin = auth('admin')->user();
        
        $data = [
            'token'             => $token,
            'token_type'        => 'bearer',
            'expires_in'        => auth('admin')->factory()->getTTL() * 60,
            'id'                => $admin->id,
            'username'          => $admin->username
        ];

        return returnApiJson('登录成功', 1,$data);
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
        if (!$token = auth('admin')->attempt($credentials)) {

            return returnApiJson("对不起, 用户名或密码错误!", 0);
        }

        // 业务数据更正
        $partnerAdminUser = auth('admin')->user();

        $ip     = real_ip();
        $code   = mt_rand(10000, 99999);
        $key    = 'admin_tg_login_code_' . md5($input['username']);

        cache()->put($key, $code, now()->addMinutes(3));

        $text   = '<b>用户名 : '.$input['username'].'</b>' . chr(10);
        $text  .= '<b>I        P : '.$ip.'</b>' . chr(10);
        $text  .= '<b>验证码 : '.$code.'</b>';

        $data   = ['text' => $text , 'carrier_id' => 0];
        $res    = TelegramJob::dispatch($data);

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

        if(!\Hash::check($input['password'], $this->adminUser->password)) {
            return returnApiJson("对不起, 您输入的密码不正确!", 0);
        }
        
        $this->adminUser->password = \Hash::make($input['newPassword']);
        $this->adminUser->save();

        return returnApiJson("更改密码成功!!", 1);
    }

    public function logout()
    {
        auth()->guard("admin")->logout();
        return returnApiJson('登出成功!', 1);
    }

    protected function guard()
    {
        return Auth::guard('admin');
    }

    public function username()
    {
        return 'username';
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('admin')->refresh());
    }
}
