<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    protected $adminUser = null;

    public function __construct()
    {
        define('ERR_MSG', '可能服务器繁忙,请稍后重试！');
        define('MER_ID', 'carrier_id');
        define('INIT_TIME', microtime(true));

        # 调试模式相关处理
        if ( $this->isDebugMode() ) {
            # 开启SQL执行监听
            \DB::enableQueryLog();
        }

        $this->adminUser = auth("admin")->user();
    }

}
