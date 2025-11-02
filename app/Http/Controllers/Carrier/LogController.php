<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Carrier\BaseController;
use App\Models\Log\CarrierAdminLog;
use App\Models\Log\RemainQuota;
use App\Models\Log\PlayerLevelUpdate;

class LogController extends BaseController
{
    public function list()
    {
        $data       = CarrierAdminLog::getList($this->carrier);

        return returnApiJson('操作成功', 1, $data);
    }

    public function carrierRemainquotaList()
    {
    	$remainQuota  = new RemainQuota();
    	$data       = $remainQuota->list($this->carrier);

        return returnApiJson('操作成功', 1, $data);
    }

    public function playerLevelUpdateList()
    {
        $data = PlayerLevelUpdate::getList($this->carrier);
        return returnApiJson('操作成功', 1, $data);
    }
}
