<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Log\Carrier;
use Illuminate\Support\Facades\DB;
use App\Models\Player;

class CarrierFeedback extends Model
{
   
    public $table = 'inf_carrier_feedback';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $fillable = [
    
    ];

    protected $casts = [
       
    ];

    public static $rules = [];

    static function feedbackLists($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;


        $query  = self::where('carrier_id',$carrier->id)->orderBy('id','desc');

        if(isset($input['type']) && !empty($input['type'])) {
            $query->where('type',$input['type']);
        }

        if(isset($input['startTime']) && strtotime($input['startTime'])){
            $query->where('created_at','>=',$input['startTime']);
        }

        if(isset($input['endTime']) && strtotime($input['endTime'])){
            $query->where('created_at','<=',$input['endTime']);
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        $playerIds = [];
        foreach ($data as $key => $value) {
            $playerIds[] = $value->player_id;
        }

        $carrierPreFixDomains    = CarrierPreFixDomain::all();
        $carrierPreFixDomainsArr = [];
        foreach ($carrierPreFixDomains as $key => $value) {
            $carrierPreFixDomainsArr[$value->prefix] = $value->name;
        }

        $players              = Player::whereIn('player_id',$playerIds)->get();
        $extendIdsArr         = [];
        $userNameArr          = [];
        foreach ($players as $key => $value) {
            $extendIdsArr[$value->player_id] = $value->extend_id;
            $userNameArr[$value->player_id]  = $value->user_name;
        }

        foreach ($data as $k => &$v) {
            $v->extend_id     = $extendIdsArr[$v->player_id];
            $userNameArr1     = explode('_', $userNameArr[$v->player_id]);
            $v->user_name     = $userNameArr1[0];
            $v->multiple_name = $carrierPreFixDomainsArr[$userNameArr1[1]];
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
