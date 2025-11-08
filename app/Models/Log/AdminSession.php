<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Log\Carrier;

use App\Models\BaseModel;

class AdminSession extends BaseModel
{


    # 表核心字段
    const TABLE_PK   = 'id';
    const TABLE_NAME = 'log_admin_session';

    # 表时间字段
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    
    ];

    protected $casts = [
       
    ];

    public static $rules = [];

    /**
     * 通用格式化列表数据，用于外部展示
     * @param        $list
     * @param string $scene
     * @return mixed
     */
    public static function formatList ( $list ) {

        $timeColumns = [ 'createTime', 'login_time', 'access_time' ];
        $ipColumns   = [ 'regIP', 'lastIP', 'login_ip', 'ip' ];

        foreach ( $list as $k => &$row ) {
            # 时间字段格式化
            foreach ( $timeColumns as $column ) {
                if ( isset($row[$column]) ) {
                    $row[$column] = !empty($row[$column]) ? date('Y-m-d H:i:s', $row[$column]) : '--';
                }
            }
            # IP字段格式化
            foreach ( $ipColumns as $column ) {
                if ( isset($row[$column]) ) {
                    $row[$column] = !empty($row[$column]) && strlen($row[$column]) > 8 ? long2ip($row[$column]) : '--';
//                    $row[$column.'_location'] = (!empty($row[$column]) || $row[$column] !== '--') ? IP::ipLocation($row[$column], []) : '';
                }
            }
        }

        return $list;
    }


}
