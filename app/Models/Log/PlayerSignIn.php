<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Log\Carrier;
use App\Models\CarrierPreFixDomain;

class PlayerSignIn extends Model
{
   
    public $table = 'log_sign_in';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $fillable = [
    
    ];

    protected $casts = [
       
    ];

    public static $rules = [];

    public static function getList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::where('carrier_id',$carrier->id)->orderBy('id','desc');
        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('player_id',$input['player_id']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $query->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<',date('Ymd',strtotime($input['endDate'])+86400));
        }

        $total      = $query->count();
        $items      = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($items as $k => &$v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
        }
        
        return ['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
