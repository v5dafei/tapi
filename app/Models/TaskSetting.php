<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class TaskSetting extends Model
{
    public $table    = 'inf_task_setting';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
       
    ];

    protected $casts = [
    ];

    public $rules = [
        
    ];

    public $messages = [
        
    ];

    public static function taskList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::where('carrier_id',$carrier->id)->orderBy('sort','asc');

        if(isset($input['game_category']) && in_array($input['game_category'],[1,2,3,4,5,6,7])){
            $query->where('game_category',$input['game_category']);
        }

        if(isset($input['prefix']) || !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        $total         = $query->count();
        $item          = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }

        foreach ($item as $key => &$value) {
            $value->multiple_name = $carrierPreFixDomainArr[$value->prefix];
        }

        return ['item' => $item, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ];
    }

    public function taskAdd($carrier)
    {
        $input = request()->all();
        if(!isset($input['prefix']) || empty($input['prefix'])){
            return '对不起，站点不能为空';
        }

        if(!isset($input['game_category']) || !in_array($input['game_category'],[1,2,3,4,5,6,7])){
            return '对不起，游戏分类取值不正确';
        }

        if(!isset($input['amount']) || !is_numeric($input['amount']) || $input['amount']<0){
            return '对不起，奖励金额取值不正确';
        }

        if(!isset($input['available_bet_amount']) || !is_numeric($input['available_bet_amount']) || $input['available_bet_amount']<0){
            return '对不起，投注额取值不正确';
        }

        if(!isset($input['giftmultiple']) || !is_numeric($input['giftmultiple']) || $input['giftmultiple']<1){
            return '对不起，流水倍数取值不正确';
        }

        if(!isset($input['status']) || !in_array($input['status'],[0,1])){
            return '对不起，状态取值不正确';
        }

        if(!isset($input['sort']) || !is_numeric($input['sort']) || $input['sort']<0 || intval($input['sort']) != $input['sort']){
            return '对不起，关卡取值不正确';
        }

        $this->prefix                = $input['prefix'];
        $this->game_category         = $input['game_category'];
        $this->amount                = $input['amount'];
        $this->available_bet_amount  = $input['available_bet_amount'];
        $this->status                = $input['status'];
        $this->sort                  = $input['sort'];
        $this->giftmultiple          = $input['giftmultiple'];
        $this->carrier_id            = $carrier->id;
        $this->save();

        return true;
    }
}
