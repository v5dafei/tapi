<?php

namespace App\Models\Conf;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

class CarrierWebSite extends BaseModel
{

    protected $table = 'conf_carrier_web_site';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'carrier_id',
        'sign',
        'value',
        'remark'
    ];

    protected $casts = [
    ];

    # 配置分类
    const CONFIG_DEFAULT = 0;
    const CONFIG_LOTTERY = 9;
    const CONFIG_MAP = [
        self::CONFIG_DEFAULT => '默认',
        self::CONFIG_LOTTERY => '彩票',
    ];

    static function getConfigByKey ( $carrier_id, $sign, $default = null ) {
        $confCarrierWebSite = self::where('carrier_id', $carrier_id)->where('sign', $sign)->first();

        if ( $confCarrierWebSite ) {
            return $confCarrierWebSite->value;
        }
        return $default ? $default : $confCarrierWebSite;
    }

    static function getConfigByKey2 ( $carrier_id, $sign, $default = null,  $exipre = 10) {
        $row = self::getOneByCache(['data' => ['carrier_id' => $carrier_id, 'sign' => $sign]], $exipre);
//        $confCarrierWebSite = self::where('carrier_id', $carrier_id)->where('sign', $sign)->first();

        if ( !empty($row) ) {
            return $row['value'];
        }
        return $default;
    }

    static function getKvList ( $merId, $exipre = 10, $type = 0 ) {
        $bind = [ MER_ID => $merId ];
        if ( !empty($type) ) {
            $bind['type'] = $type;
        }

        $list = self::getListByCache([ 'data' => $bind, 'columns' => 'sign, value' ], $exipre);
        $new  = [];
        foreach ( $list as $k => $v ) {
            $new[$v['sign']] = $v['value'];
        }
        return $new;
    }
}
