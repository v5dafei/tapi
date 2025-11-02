<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Log\Carrier;

class RemainQuota extends Model
{
   
    public $table    = 'log_carrier_remainquota';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    
    ];

    protected $casts = [
       
    ];

    public static $rules = [];

    public function carrier()
    {
        return $this->belongsTo(Carrier::class,'carrier_id','id');
    }

    static function getList()
    {
        $input          = request()->all();
        $query          = self::orderBy('id','desc');
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $total          = $query->count();
        $data           = $query->skip($offset)->take($pageSize)->get();

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function list($carrier)
    {
        
        $input          = request()->all();

        $query          = self::where('carrier_id',$carrier->id)->orderBy('id','desc');
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['direction'])){
            $query->where('direction',$input['direction']);
        }

        if(isset($input['game_account'])){
            $query->where('game_account',trim($input['game_account']));
        }

        if(isset($input['startTime']) && strtotime($input['startTime'])){
            $query->where('created_at','>=',$input['startTime'].' 00:00:00');
        } else{
            $query->where('created_at','>=',date('Y-m-d 00:00:00'));
        }

        if(isset($input['endTime']) && strtotime($input['endTime'])){
            $query->where('created_at','<=',$input['endTime'].' 23:59:59');
        }

        $total          = $query->count();
        $data           = $query->skip($offset)->take($pageSize)->get();

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
