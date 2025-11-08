<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Log\Carrier;
use App\Models\CarrierPreFixDomain;

class RankingList extends Model
{
   
    public $table = 'log_ranking_list';

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

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<',date('Ymd',strtotime($input['endDate'])+86400));
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        $total                  = $query->count();
        $items                  = $query->skip($offset)->take($pageSize)->get();
        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }

        foreach ($items as $key => &$value) {
            $value->day = date('Y-m-d',strtotime($value->day));
            $value->multiple_name = $carrierPreFixDomainArr[$value->prefix];
        }
        
        return ['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function addRank($carrier)
    {
        $input = request()->all();

        if(!isset($input['day']) || !strtotime($input['day'])){
            return '对不起，日期取值不正确';
        }

        if(!isset($input['content']) || !is_array($input['content']) || count($input['content'])<1){
            return '对不起，排行榜内容取值不正确';
        }

        $rankingList = self::where('carrier_id',$carrier->id)->where('day',date('Ymd',strtotime($input['day'])))->first();

        if($this->id){
            if($rankingList && $this->id != $rankingList->id){
                return '对不起，此日期排行榜已存在';
            }
        } else{
            if($rankingList){
                return '对不起，此日期排行榜已存在不能新增';
            }
        }

        if(isset($input['content']) && count($input['content'])){
            $contentArr =  $input['content'];
            $flag       = [];

            foreach ($contentArr as $key => $value) {
                $flag[] = $value['ranking']; 
            }

            array_multisort($flag, SORT_ASC, $contentArr);
            $input['content'] = $contentArr;
        }

        if(isset($input['status']) && in_array($input['status'],[0,1])){
            $this->status = $input['status'];
        }

        $this->carrier_id = $carrier->id;
        $this->content    = json_encode($input['content']);
        $this->day        = date('Ymd',strtotime($input['day']));
        $this->save();

        return true;
    }
}
