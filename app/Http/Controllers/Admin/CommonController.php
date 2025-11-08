<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Authenticatable;
use App\Http\Controllers\Admin\BaseController;
use App\Lib\S3;

class CommonController extends BaseController
{
    use Authenticatable;

    public function fileUpload($directory) 
    {
        $input = request()->all();

        if(strstr($directory,'_')){
            $directoryArr = explode('_',$directory);
            $directory    = $directoryArr[0];

            $bankcurrencys     =  config('main')['bankcurrency'];
            $currencyarr       = [];

            foreach ($bankcurrencys as $key => $value) {
                $currencyarr[$value] = $key;
            }
        }

        if($directory=='bankicon'){
            $arr   = [
                'carrier_id' => 0,
                'directory'  => $directory.'/'.$currencyarr[$value]
            ];
        } else {
            $arr   = [
                'directory'  => $directory
            ];
        }

        $res = S3::uploadImage($input['file'], $arr);

        if(is_array($res)) {
            return returnApiJson('操作成功', 1, $res);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function init()
    {
        return returnApiJson('操作成功', 1,['gameImgResourseUrl'=>config('main')['alicloudstore'],'videoImgResourseUrl'=>config('main')['awscloudstore']]);
    }
}
