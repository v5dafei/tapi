<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Auth\Authenticatable;
use App\Http\Controllers\Admin\BaseController;
use App\Models\Def\Development;

class DevelopmentController extends BaseController
{
    use Authenticatable;

    public function developmentList() 
    {
        $data   = Development::orderBy('id','asc')->get();

        return returnApiJson('操作成功', 1, $data);
    }
}
