<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Log\Carrier;

class CarrierAdminLog extends Model
{
   
    public $table    = 'log_carrier_admin_log';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    
    ];

    protected $casts = [
       
    ];

    public static $rules = [];

    public static function getList($carrier)
    {
        $input       = request()->all();
        $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
        $offset      = ($currentPage - 1) * $pageSize;

        $query       = self::where('carrier_id',$carrier->id)->orderBy('created_at', 'desc');

        if(isset($input['startTime']) && strtotime($input['startTime'])){
            $query->where('actionTime','>=',strtotime($input['startTime']));
        } else {
            $query->where('actionTime','>=',strtotime(date('Y-m-d')));
        }

        if(isset($input['endTime']) && strtotime($input['endTime'])){
            $query->where('actionTime','<=',strtotime($input['endTime']));
        } else {
            $query->where('actionTime','<=',time());
        }

        if(isset($input['ip']) && ip2long($input['ip'])){
            $query->where('actionIP',ip2long($input['ip']));
        }

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $query->where('user_name',trim($input['user_name']));
        }

        if(isset($input['action']) && !empty($input['action'])){
            $query->where('action','like','%'.trim($input['action'].'%'));
        }

        $total       = $query->count();
        $items       = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->actionIP =long2ip($value->actionIP);
        }

        return [ 'data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ];
    }
}
