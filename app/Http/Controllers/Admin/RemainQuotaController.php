<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\BaseController;
use Illuminate\Auth\Authenticatable;
use App\Models\Log\RemainQuota;

class RemainQuotaController extends BaseController
{
    use Authenticatable;

    public function remainQuotaList() 
    {
        $data   = RemainQuota::getList();

        return returnApiJson('操作成功', 1, $data);
    }
}
