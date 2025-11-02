<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
//use Illuminate\Database\Eloquent\Model;
use App\Models\Def\Development;
use App\Models\Def\MainGamePlat;
use App\Models\CarrierPreFixDomain;
use App\Models\CarrierUser;
use App\Models\CarrierActivity;

use App\Models\BaseModel as Model;

class PlayerTransfer extends Model
{

    const TABLE_PK   = 'id';
    const TABLE_NAME = 'inf_player_transfer';

    public $table = 'inf_player_transfer';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
    ];

    protected $casts = [
    ];

    const FUND_GROUP = [
        1 => '存款',
        2 => '提款',
        3 => '彩金',
        4 => '彩票投注',
        5 => '彩票返点/真人返点/退水/反水',
        6 => '真人下注/转换/反水/补单',
        7 => '其他下注',
        8 => '全民竞猜',
        9 => '聊天室红包',
    ];

    const FUND_TYPE = [
        225 => [ 'id' => 225, 'name' => '红包：发红包', 'group' => 9 ],
        226 => [ 'id' => 226, 'name' => '红包：抢红包', 'group' => 9 ],
        227 => [ 'id' => 227, 'name' => '红包：过期退回', 'group' => 9 ],
        228 => [ 'id' => 228, 'name' => '扫雷红包：发红包', 'group' => 9 ],
        229 => [ 'id' => 229, 'name' => '扫雷红包：抢红包', 'group' => 9 ],
        230 => [ 'id' => 230, 'name' => '扫雷红包：过期退回', 'group' => 9 ],
        231 => [ 'id' => 231, 'name' => '扫雷红包：中雷赔付', 'group' => 9 ],
        232 => [ 'id' => 232, 'name' => '扫雷红包：中雷获赔', 'group' => 9 ],
        233 => [ 'id' => 233, 'name' => '扫雷红包：幸运奖励', 'group' => 9 ],
        235 => [ 'id' => 235, 'name' => '扫雷红包：多雷奖励', 'group' => 9 ],
        236 => [ 'id' => 236, 'name' => '任务系统：周俸禄', 'group' => 11 ],
        237 => [ 'id' => 237, 'name' => '任务系统：月俸禄', 'group' => 11 ],

        'redbag_1'    => [ 'code' => 'send_redbag', 'name' => '普通红包：发红包', 'group' => 9 ],    // 红包：发红包
        'redbag_2'   => [ 'code' => 'grab_red_bag', 'name' => '普通红包：抢红包', 'group' => 9 ],    // 红包：抢红包
        'redbag_3' => [ 'code' => 'revoke_red_bag', 'name' => '普通红包：过期退回', 'group' => 9 ],    // 红包：过期退回
        'redbag_mine_1'  => [ 'code' => 'send_mine_redbag', 'name' => '扫雷红包：发红包', 'group' => 9 ],    // 扫雷红包：发红包
        'redbag_mine_2'  => [ 'code' => 'grab_mine_red_bag', 'name' => '扫雷红包：抢红包', 'group' => 9 ],    // 扫雷红包：抢红包
        'redbag_mine_3'  => [ 'code' => 'revoke_mine_red_bag', 'name' => '扫雷红包：过期退回', 'group' => 9 ],    // 扫雷红包：过期退回
        'redbag_mine_4'  => [ 'code' => 'hit_mine_indemnify', 'name' => '扫雷红包：中雷赔付', 'group' => 9 ],    // 扫雷红包：中雷赔付
        'redbag_mine_5'  => [ 'code' => 'hit_mine_get_indemnify', 'name' => '扫雷红包：中雷获赔', 'group' => 9 ],    // 扫雷红包：中雷获赔
        'redbag_mine_6'  => [ 'code' => 'lucky_mine_reward', 'name' => '扫雷红包：幸运奖励', 'group' => 9 ],    // 扫雷红包：幸运奖励
        'redbag_mine_7'  => [ 'code' => 'more_mine_reward', 'name' => '扫雷红包：多雷奖励', 'group' => 9 ],    // 扫雷红包：多雷奖励
        'redbag_pass_1'  => [ 'code' => 'send_pass_redbag', 'name' => '口令红包：发红包', 'group' => 9 ],    // 口令红包：发红包
        'redbag_pass_2'  => [ 'code' => 'grab_pass_red_bag', 'name' => '口令红包：抢红包', 'group' => 9 ],    // 口令红包：抢红包
        'redbag_pass_3'  => [ 'code' => 'revoke_pass_red_bag', 'name' => '口令红包：过期退回', 'group' => 9 ],    // 口令红包：过期退回
    ];

    public static function transferList ( $carrier ) {
        $input = request()->all();
        $query = self::where('carrier_id', $carrier->id)->where('is_tester', 0)->orderBy('id', 'desc');

        $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
        $offset      = ($currentPage - 1) * $pageSize;

        if ( isset($input['id']) && trim($input['id']) != '' ) {
            $query->where('id', (int)$input['id']);
        }

        if ( isset($input['user_name']) && trim($input['user_name']) != '' ) {
            $query->where('user_name','like', '%'.$input['user_name'].'%');
        }

        if ( isset($input['player_id']) && trim($input['player_id']) != '' ) {
            $query->where('player_id', $input['player_id']);
        }

        if ( isset($input['parent_id']) && trim($input['parent_id']) != '' ) {
            $query->where('parent_id', $input['parent_id']);
        }

        if ( isset($input['sign']) && trim($input['sign']) != '' ) {
            $query->where('type', $input['sign']);
        }

        if ( isset($input['startTime']) && strtotime($input['startTime']) ) {
            if(strlen($input['startTime'])==10){
                $query->where('created_at', '>=', $input['startTime'] . ' 00:00:00');
            } else {
                $query->where('created_at', '>=', $input['startTime']);
            }
        } else {
            $query->where('created_at', '>=', date('Y-m-d'));
        }

        if ( isset($input['endTime']) && strtotime($input['endTime']) ) {
            if(strlen($input['startTime'])==10){
                $query->where('created_at', '<=', $input['endTime'] . ' 23:59:59');
            } else {
                $query->where('created_at', '<=', $input['endTime']);
            }
        }

        $totalAmount = $query->sum('amount');
        $total       = $query->count();
        $data        = $query->skip($offset)->take($pageSize)->get();


        $mainGamePlats    = MainGamePlat::all();
        $mainGamePlatsArr = [];

        foreach ( $mainGamePlats as $key => $value ) {
            $mainGamePlatsArr[$value->main_game_plat_id] = $value->alias;
        }

        $carrierUsers      = CarrierUser::where('username','<>','super_admin')->get();
        $carrierUsersArr    = [];
        $carrierUsersArr[0] = '系统';
        foreach ( $carrierUsers as $key => $value ) {
            $carrierUsersArr[$value->id] = $value->username;
        }

        $carrierActivitys    = CarrierActivity::all();
        $carrierActivitysArr = [];

        foreach ( $carrierActivitys as $key => $value ) {
            $carrierActivitysArr[$value->id] = $value->name;
        }

        return [ 'mainGamePlatsArr' => $mainGamePlatsArr, 'carrierUsersArr' => $carrierUsersArr, 'carrierActivitysArr' => $carrierActivitysArr, 'data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)),'totalAmount'=>$totalAmount];
    }

    public static function transferTypeList () {
        return Development::select('sign', 'name')->get();
    }

    /**
     * 增加资金变动日志
     *
     * @param     $playerAccount
     * @param     $amount
     * @param int $mode 1=加钱，2=扣钱，3=不变
     * @param     $type
     * @param     $type_name
     * @return int
     */
    public static function addTransferLog ( $playerAccount, $amount, $mode = 1, $type, $type_name = null ) {
        $playerTransfer = [];

        $playerTransfer['carrier_id']     = $playerAccount['carrier_id'];
        $playerTransfer['rid']            = $playerAccount['rid'];
        $playerTransfer['top_id']         = $playerAccount['top_id'];
        $playerTransfer['parent_id']      = $playerAccount['parent_id'];
        $playerTransfer['player_id']      = $playerAccount['player_id'];
        $playerTransfer['is_tester']      = $playerAccount['is_tester'];
        $playerTransfer['level']          = $playerAccount['level'];
        $playerTransfer['user_name']      = $playerAccount['user_name'];
        $playerTransfer['before_balance'] = $playerAccount['balance'];

        if ( $mode == 1 ) {
            $playerTransfer['balance'] = $playerAccount['balance'] + bcmul($amount, 10000);
        }
        if ( $mode == 2 ) {
            $playerTransfer['balance'] = $playerAccount['balance'] - bcmul($amount, 10000);
        }
        if ( $mode == 3 ) {
            $playerTransfer['balance'] = $playerAccount['balance'];
        }

//        if ( empty($type_name) ) {
//            $type_name = !empty(self::FUND_TYPE[$type_name][])
//        }

        $playerTransfer['before_frozen_balance'] = $playerAccount['frozen'];
        $playerTransfer['frozen_balance']        = $playerAccount['frozen'];

        $playerTransfer['before_agent_frozen_balance'] = $playerAccount['agentfrozen'];
        $playerTransfer['agent_balance']        = $playerAccount['agentbalance'];

        $playerTransfer['before_agent_balance'] = $playerAccount['agentbalance'];
        $playerTransfer['agent_frozen_balance']  = $playerAccount['agentfrozen'];

        $playerTransfer['mode']      = $mode;
        $playerTransfer['type']      = $type;
        $playerTransfer['type_name'] = $type_name;
        $playerTransfer['day_m']     = date('Ym', time());
        $playerTransfer['day']       = date('Ymd', time());
        $playerTransfer['amount']    = bcmul($amount, 10000);

        $playerTransfer[self::CREATED_AT] = date('Y-m-d H:i:s', time());
        $playerTransfer[self::UPDATED_AT] = date('Y-m-d H:i:s', time());

        return self::insert2($playerTransfer);
    }

    public static function registerGiftList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::where('carrier_id',$carrier->id)->where('type','register_gift')->orderBy('id','desc');

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('player_id',$input['player_id']);
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