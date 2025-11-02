<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Def\MainGamePlat;
use App\Models\CarrierPreFixDomain;
use App\Models\Carrier;
use App\Models\Player;

class PlayerBetFlow extends Model
{
    public $table = 'log_player_bet_flow';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'carrier_id',
        'player_id',
        'main_game_plat_id',
        'game_flow_code',
        'game_status',
        'bet_amount',
        'available_bet_amount',
        'company_win_amount',
        'bet_info',
        'bet_flow_available',
        'bet_time',
        'stat_time',
        'game_name',
        'game_category',
        'is_tester',
        'issue',
        'opendata',
        'whether_recharge',
        'is_trygame'
    ];

    protected $casts = [
    ];

    public static $rules = [];

    public static function betflowList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query3 = self::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(bet_amount) as bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'));

        $query4 =self::select('log_player_bet_flow.user_name','log_player_bet_flow.player_id','log_player_bet_flow.id','log_player_bet_flow.isFeatureBuy','log_player_bet_flow.multi_spin_game','log_player_bet_flow.id','log_player_bet_flow.prefix','log_player_bet_flow.game_flow_code','log_player_bet_flow.user_name as game_user_name','log_player_bet_flow.game_category','log_player_bet_flow.main_game_plat_code','log_player_bet_flow.game_name','log_player_bet_flow.bet_time','log_player_bet_flow.bet_amount','log_player_bet_flow.available_bet_amount','log_player_bet_flow.company_win_amount','log_player_bet_flow.game_status','log_player_bet_flow.bet_flow_available','log_player_bet_flow.issue','log_player_bet_flow.opendata','log_player_bet_flow.bet_info')
            ->orderBy('log_player_bet_flow.bet_time','desc');

        $query2 = self::select('log_player_bet_flow.id')->orderBy('log_player_bet_flow.bet_time','desc');
        $query1 = self::select('log_player_bet_flow.id');

        if(isset($input['game_flow_code']) && trim($input['game_flow_code']) != ''){
            $query1->where('log_player_bet_flow.game_flow_code',$input['game_flow_code']);
            $query2->where('log_player_bet_flow.game_flow_code',$input['game_flow_code']);
            $query3->where('log_player_bet_flow.game_flow_code',$input['game_flow_code']);
        }

        if(isset($input['player_id']) && trim($input['player_id']) != '' ) {
            $query1->where('log_player_bet_flow.player_id',$input['player_id']);
            $query2->where('log_player_bet_flow.player_id',$input['player_id']);
            $query3->where('log_player_bet_flow.player_id',$input['player_id']);
        }

        if(isset($input['extend_id']) && trim($input['extend_id']) != '' ) {
            $playerIds = Player::where('extend_id',$input['extend_id'])->pluck('player_id')->toArray();
            $query1->whereIn('log_player_bet_flow.player_id',$playerIds);
            $query2->whereIn('log_player_bet_flow.player_id',$playerIds);
            $query3->whereIn('log_player_bet_flow.player_id',$playerIds);
        }

        if(isset($input['user_name']) && trim($input['user_name']) != '' ) {
            $query1->where('log_player_bet_flow.user_name',$input['user_name']);
            $query2->where('log_player_bet_flow.user_name',$input['user_name']);
            $query3->where('log_player_bet_flow.user_name',$input['user_name']);
        }

        if(isset($input['game_status']) && in_array($input['game_status'],[0,1,2])) {
            $query1->where('log_player_bet_flow.game_status',$input['game_status']);
            $query2->where('log_player_bet_flow.game_status',$input['game_status']);
            $query3->where('log_player_bet_flow.game_status',$input['game_status']);
        }

        if(isset($input['min_amount']) && !empty($input['min_amount'])){
            $query1->where('log_player_bet_flow.company_win_amount','>=',$input['min_amount']);
            $query2->where('log_player_bet_flow.company_win_amount','>=',$input['min_amount']);
            $query3->where('log_player_bet_flow.company_win_amount','>=',$input['min_amount']);
        }

        if(isset($input['max_amount']) && !empty($input['max_amount'])){
            $query1->where('log_player_bet_flow.company_win_amount','<=',$input['max_amount']);
            $query2->where('log_player_bet_flow.company_win_amount','<=',$input['max_amount']);
            $query3->where('log_player_bet_flow.company_win_amount','<=',$input['max_amount']);
        }

        if(isset($input['startTime']) &&  strtotime($input['startTime'])) {
            $query1->where('log_player_bet_flow.bet_time','>=',strtotime($input['startTime']));
            $query2->where('log_player_bet_flow.bet_time','>=',strtotime($input['startTime']));
            $query3->where('log_player_bet_flow.bet_time','>=',strtotime($input['startTime']));
        }

        if(isset($input['endTime']) && strtotime($input['endTime']) ) {
            $query1->where('log_player_bet_flow.bet_time','<',strtotime($input['endTime']));
            $query2->where('log_player_bet_flow.bet_time','<',strtotime($input['endTime']));
            $query3->where('log_player_bet_flow.bet_time','<',strtotime($input['endTime']));
        }

        if(isset($input['prefix']) && $input['prefix']) {
            $query1->where('log_player_bet_flow.prefix',$input['prefix']);
            $query2->where('log_player_bet_flow.prefix',$input['prefix']);
            $query3->where('log_player_bet_flow.prefix',$input['prefix']);
        }

        if(isset($input['game_category']) && in_array($input['game_category'],[1,2,3,4,5,6,7])){
            $query1->where('log_player_bet_flow.game_category',$input['game_category']);
            $query2->where('log_player_bet_flow.game_category',$input['game_category']);
            $query3->where('log_player_bet_flow.game_category',$input['game_category']);
        }

        if(isset($input['main_game_plat_id']) && trim($input['main_game_plat_id']) != '' ) {
            $query1->where('log_player_bet_flow.main_game_plat_id',$input['main_game_plat_id']);
            $query2->where('log_player_bet_flow.main_game_plat_id',$input['main_game_plat_id']);
            $query3->where('log_player_bet_flow.main_game_plat_id',$input['main_game_plat_id']);
        }

        if(isset($input['isFeatureBuy']) && in_array($input['isFeatureBuy'],[0,1])) {
            $query1->where('log_player_bet_flow.isFeatureBuy',$input['isFeatureBuy']);
            $query2->where('log_player_bet_flow.isFeatureBuy',$input['isFeatureBuy']);
            $query3->where('log_player_bet_flow.isFeatureBuy',$input['isFeatureBuy']);
        }

        if(isset($input['multi_spin_game']) && in_array($input['multi_spin_game'],[0,1])) {
            $query1->where('log_player_bet_flow.multi_spin_game',$input['multi_spin_game']);
            $query2->where('log_player_bet_flow.multi_spin_game',$input['multi_spin_game']);
            $query3->where('log_player_bet_flow.multi_spin_game',$input['multi_spin_game']);
        }

        if(isset($input['main_game_plat_code']) && trim($input['main_game_plat_code']) != '' ) {
            $query1->where('log_player_bet_flow.main_game_plat_code',$input['main_game_plat_code']);
            $query2->where('log_player_bet_flow.main_game_plat_code',$input['main_game_plat_code']);
            $query3->where('log_player_bet_flow.main_game_plat_code',$input['main_game_plat_code']);
        }

        if(isset($input['is_material']) && in_array($input['is_material'],[0,1])) {
            $query1->where('log_player_bet_flow.is_material',$input['is_material']);
            $query2->where('log_player_bet_flow.is_material',$input['is_material']);
            $query3->where('log_player_bet_flow.is_material',$input['is_material']);
        }

        $logPlayerBetFlowIds = $query2->skip($offset)->take($pageSize)->pluck('id')->toArray();

        $total         = $query1->count();
        $statTotal     = $query3->first();
        $item          = $query4->whereIn('log_player_bet_flow.id',$logPlayerBetFlowIds)->get();
        $mainGamePlats = MainGamePlat::all();
        $plats         = [];

        $mianGamePlatsArr = [];
        foreach ($mainGamePlats as  $value) {
            $row                        = [];
            $row['main_game_plat_code'] = $value->main_game_plat_code;
            $row['value']               = $value->alias;
            $plats[]                    = $row;

            $mianGamePlatsArr[$value->main_game_plat_code] = $value->alias;
        }

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }

        foreach ($item as $key => &$value) {
          $value->multiple_name = $carrierPreFixDomainArr[$value->prefix];
          $value->alias         = $mianGamePlatsArr[$value->main_game_plat_code];

          if(is_string($value->available_bet_amount)){
                $value->available_bet_amount = bcdiv($value->available_bet_amount,1,2);
           }

            if(is_string($value->bet_amount)){
                $value->bet_amount = bcdiv($value->bet_amount,1,2);
           }

            if(is_string($value->company_win_amount)){
                $value->company_win_amount = bcdiv($value->company_win_amount,1,2);
           }
        }

        return ['statTotal'=>$statTotal,'itme' => $item,  'plats' => $plats, 'total' => $total,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
