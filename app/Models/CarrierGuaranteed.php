<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Models\CarrierPreFixDomain;

class CarrierGuaranteed extends Model
{
    use Notifiable;

    public $table = 'inf_carrier_guaranteed';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $fillable = [
       
    ];

    protected $casts = [
       
    ];

    public $rules = [
    ]; 

    public $messages = [
        
    ];

    public function guaranteedAdd($carrier)
    {
        $input = request()->all();

        if(!isset($input['level']) || empty($input['level'])){
            return '对不起，等级不能为空';
        }

        if(!isset($input['performance']) || !is_numeric($input['performance']) || $input['performance']<0){
            return '对不起，业绩不能为空';
        }

        if(!isset($input['quota']) || !is_numeric($input['quota']) || $input['quota']<0){
            return '对不起，返佣额度不能为空';
        }

        if(!isset($input['sort']) || empty($input['sort'])){
            return '对不起，排序不能为空';
        }

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return '对不起，网站不能为空';
        }

        if(!isset($input['game_category'])){
            return '对不起，游戏分类不能为空';
        }

        $this->level       = $input['level'];
        $this->game_category  = $input['game_category'];
        $this->carrier_id  = $carrier->id;
        $this->performance = $input['performance'];
        $this->quota       = $input['quota'];
        $this->sort        = $input['sort'];
        $this->prefix      = $input['prefix'];
        $this->save();

        return true;
    }

    public static function guaranteedList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::where('carrier_id',$carrier->id)->orderBy('sort','asc');

        if(isset($input['prefix']) && trim($input['prefix']) !='') {
            $query->where('prefix',$input['prefix']);
        }

        if(isset($input['game_category']) && is_numeric($input['game_category'])) {
            $query->where('game_category',$input['game_category']);
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($data as $k => $v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
