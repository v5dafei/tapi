<?php

namespace App\Models\Def;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Models\Map\CarrierGamePlat;
use App\Models\Map\CarrierGame;
use App\Models\Def\MainGamePlat;
use App\Models\PlayerRecent;
use App\Models\Carrier;
use App\Lib\Cache\GameCache;
use App\Models\PlayerGameCollect;

class Game extends Model
{

    const STATUS_AVAILABLE = 1;
    const STATUS_CLOSED    = 0;

    public $table    = 'def_games';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'game_id';


    public $fillable = [
        'main_game_plat_id',
        'game_name',
        'status',
        'game_code',
        'game_moblie_code',
        'format',
        'is_recommend',
        'is_hot',
        'is_pool',
        'record_match_code',
        'sort',
        'pageview',
        'game_category'
    ];

    protected $casts = [
        'game_id' => 'string',
        'game_plat_id' => 'integer',
        'format'    => 'integer',
        'game_name' => 'string',
        'game_code' => 'string',
    ];

    public $rules = [
        'main_game_plat_id' => 'required|exists:def_main_game_plats,main_game_plat_id',
        'game_category'     => 'required|in:1,2,3,4,5,6,7',
        'game_name'         => 'required|min:1|max:16',
        'pageview'          => 'required|int|min:0',
        'is_recommend'      => 'required|in:1,0',
        'is_hot'            => 'required|in:1,0',
        'is_pool'           => 'required|in:1,0',
        'sort'              => 'required|int'
    ];

    public $messages = [
        'main_game_plat_id.required'                   => '游戏平台必须填写',
        'main_game_plat_id.exists'                     => '游戏平台不存在',
        'game_category.required'                       => '游戏分类必须填写',
        'game_category.in'                             => '游戏分类不存在',
        'game_name.required'                           => '游戏名称必须填写',
        'game_name.min'                                => '游戏名称必须填写',
        'game_name.max'                                => '游戏名称必须小于17个字符',
        'pageview.required'                            => '人气必须填写',
        'pageview.int'                                 => '人气必须为整数',
        'pageview.min'                                 => '人气必须大于等于0',
        'is_recommend.required'                        => '推荐必须填写',
        'is_recommend.in'                              => '推荐取值不正确',
        'is_hot.required'                              => '热门必须填写',
        'is_hot.in'                                    => '热门取值不正确',
        'is_pool.in'                                   => '奖池取值不正确',
        'is_pool.required'                             => '奖池必须填写',
        'sort.required'                                => '排序必须填写',
        'sort.int'                                     => '排序必须为整数',
    ];

    public function saveItem()
    {
        $input     = request()->all();
        $validator = Validator::make($input, $this->rules, $this->messages);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $mainGamePlat               = MainGamePlat::where('main_game_plat_id',$input['main_game_plat_id'])->first();
        $this->main_game_plat_code  = $mainGamePlat->main_game_plat_code;
        $this->main_game_plat_id    = $input['main_game_plat_id'];
        $this->game_name            = $input['game_name'];
        $this->game_category        = $input['game_category'];
        $this->game_code            = is_null($input['game_code'])?'':$input['game_code'];
        $this->game_moblie_code     = is_null($input['game_moblie_code'])?'':$input['game_moblie_code'];
        $this->pageview             = $input['pageview'];
        $this->is_recommend         = $input['is_recommend'];
        $this->is_hot               = $input['is_hot'];
        $this->is_pool              = $input['is_pool'];
        $this->sort                 = $input['sort'];
        $this->record_match_code    = is_null($input['record_match_code'])?'':$input['record_match_code'];
        $this->save();

        return true;
    }

    static function getList()
    {
        $input          = request()->all();
        $query          = self::orderBy('game_id','asc');
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['main_game_plat_id']) && trim($input['main_game_plat_id']) != '') {
            $query->where('main_game_plat_id',$input['main_game_plat_id']);
        }

        if(isset($input['format']) && in_array($input['format'],[0,1,2])) {
            $query->where('format',$input['format']);
        }

        if(isset($input['status']) && trim($input['status']) != '') {
            $query->where('status',$input['status']);
        }

        if(isset($input['game_category']) && trim($input['game_category']) != '') {
            $query->where('game_category',$input['game_category']);
        }

        if(isset($input['game_name']) && trim($input['game_name']) != '') {
            $query->where('game_name','like','%'.$input['game_name'].'%');
        }

        if(isset($input['is_offline']) && in_array($input['is_offline'],[0,1])){
            $query->where('is_offline',$input['is_offline']);
        }

        $total  = $query->count();
        $data   = $query->skip($offset)->take($pageSize)->get();

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function gameChangeStatus()
    {
        $this->status = $this->status ? 0:1;

        if(!$this->status) {
            CarrierGame::where('game_id',$this->game_id)->update(['status'=>0]);
        }

        $carriers = Carrier::all();
        foreach ($carriers as $key => $value) {
            GameCache::flushCarrierGame($value->id);
        }

        $this->save();

        return true;
    }

    public function gameCarriers()
    {
        $allcarrier     = Carrier::all();
        $selectcarriers = CarrierGame::where('game_id',$this->game_id)->get();

        return ['game'=>$this,'allcarriers'=>$allcarrier,'selectcarriers'=> $selectcarriers];
    }

    public function gameCarriersSave()
    {
        $carrierIdsArray = request()->get('carriers_ids',[]);

        if(!is_array($carrierIdsArray)) {
            return '对不起,运营商参数不正确!';
        }
    
        $currCarrierGamePlat = CarrierGame::where('game_id',$this->game_id)->pluck('carrier_id')->toArray();
        $deleCarrierIds      = array_diff($currCarrierGamePlat,$carrierIdsArray);
        $addCarrierIds       = array_diff($carrierIdsArray,$currCarrierGamePlat);

        //删除
        CarrierGame::whereIn('carrier_id',$deleCarrierIds)->where('game_id',$this->game_id)->delete();

        $game    = Game::where('game_id',$this->game_id)->first();
        $gameIds = Game::where('main_game_plat_id',$game->main_game_plat_id)->pluck('game_id')->toArray();

        foreach ($deleCarrierIds as $key => $value) {
             $count = CarrierGame::where('carrier_id',$value)->whereIn('game_id',$gameIds)->count();
             if(!$count) {
                CarrierGamePlat::where('carrier_id',$value)->where('game_plat_id',$game->main_game_plat_id)->delete();
             }
        }
       
        //添加
        $mainGamePlat     = MainGamePlat::where('main_game_plat_id',$game->main_game_plat_id)->first();

        foreach ($addCarrierIds as $key => $value) {
           $count = CarrierGamePlat::where('game_plat_id',$game->main_game_plat_id)->where('carrier_id',$value)->count();
           if(!$count) {
                $carrierGamePlat               = new CarrierGamePlat();
                $carrierGamePlat->carrier_id   = $value;
                $carrierGamePlat->game_plat_id = $game->main_game_plat_id;
                $carrierGamePlat->status       = $mainGamePlat->status;
                $carrierGamePlat->sort         = $mainGamePlat->sort;
                $carrierGamePlat->save();
           }
                $carrierGame                   = new  CarrierGame();
                $carrierGame->carrier_id       = $value;
                $carrierGame->game_id          = $this->game_id;
                $carrierGame->display_name     = $game->game_name;
                $carrierGame->sort             = $game->sort;
                $carrierGame->status           = $game->status;
                $carrierGame->is_recommend     = $game->is_recommend;
                $carrierGame->is_hot           = $game->is_hot;
                $carrierGame->game_category    = $game->game_category;
                $carrierGame->game_plat_id     = $game->main_game_plat_id;
                $carrierGame->save();
        } 

        return true;
    }

    public function recomandcardList($carrier,$input)
    {
        return GameCache::recomandCard($carrier->id,$input);
    }
    
    public function recomandElectronicList($carrier,$input)
    {
        return GameCache::recomandElectronic($carrier->id,$input);
    }

    public function electronicCategoryList($carrier,$prefix)
    {
        return GameCache::electronicCategoryList($carrier->id,$prefix);
    }

    public function electronicList($carrier,$prefix=null,$user=null)
    {
        $input = request()->all();

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['type']) && $input['type']=='history') {
            $gameIds               = PlayerRecent::where('player_id',$user->player_id)->pluck('game_id')->toArray();
            $mainGamePlatIds       = PlayerRecent::where('player_id',$user->player_id)->pluck('main_game_plat_id')->toArray();
            $mainGamePlatIds       = array_unique($mainGamePlatIds);
            $carrierGamePlat       = CarrierGamePlat::where('carrier_id',$carrier->id)->whereIn('game_plat_id',$mainGamePlatIds)->whereIn('status',[1,2])->get();
            $gamePlatIds           = CarrierGamePlat::where('carrier_id',$carrier->id)->whereIn('game_plat_id',$mainGamePlatIds)->whereIn('status',[1,2])->pluck('game_plat_id')->toArray();
            $carrierGamePlatStatus = [];

            foreach ($carrierGamePlat as $key => $value) {
                $carrierGamePlatStatus[$value->game_plat_id] = $value->status;
            }

            $existGameIds          = CarrierGame::where('carrier_id',$carrier->id)->where('game_category',2)->whereIn('game_id',$gameIds)->whereIn('status',[1,2])->whereIn('game_plat_id',$gamePlatIds)->pluck('game_id')->toArray();
            $carrierGame           = CarrierGame::where('carrier_id',$carrier->id)->where('game_category',2)->whereIn('game_id',$gameIds)->whereIn('status',[1,2])->get();
            $carrierGameStatus     = [];

            foreach ($carrierGame as $key => $value) {
                $carrierGameStatus[$value->game_id] = $value->status;
            }

            if(isset($input['is_mobile']) && empty($input['is_mobile'])) {

                $query = PlayerRecent::select('def_games.*','def_games.game_name as display_name')
                    ->leftJoin('def_games','def_games.game_id','inf_player_recent.game_id')
                    ->where('inf_player_recent.game_category',2)
                    ->where('inf_player_recent.player_id',$user->player_id)
                    ->where('inf_player_recent.game_moblie_code','<>','')
                    ->whereIn('inf_player_recent.game_id',$existGameIds)
                    ->groupBy('inf_player_recent.game_id')
                    ->orderBy('inf_player_recent.created_at','desc');
            } else {
                $query = PlayerRecent::select('def_games.*','def_games.game_name as display_name')
                    ->leftJoin('def_games','def_games.game_id','inf_player_recent.game_id')
                    ->where('inf_player_recent.game_category',2)
                    ->where('inf_player_recent.player_id',$user->player_id)
                    ->where('inf_player_recent.game_code','<>','')
                    ->whereIn('inf_player_recent.game_id',$existGameIds)
                    ->groupBy('inf_player_recent.game_id')
                    ->orderBy('inf_player_recent.created_at','desc');
            }

            if(isset($input['main_game_plat_id']) && !empty($input['main_game_plat_id'])) {
                $query->where('def_games.main_game_plat_id',$input['main_game_plat_id']);
            }

            if(isset($input['game_name']) && !empty($input['game_name'])) {
                $query->where('def_games.game_name','like','%'.$input['game_name'].'%');
            }

            $total          = $query->count();
            $list           = $query->skip($offset)->take($pageSize)->get();

            foreach ($list as $key => &$value) {
                if($carrierGameStatus[$value->game_id]==2 || $carrierGamePlatStatus[$value->main_game_plat_id]==2){
                    $value->status = 2;
                } else{
                    $value->status = 1;
                }
            }

            $playerRecent   = ['data' => $list->toArray(), 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];

            return $playerRecent;
        } elseif(isset($input['type']) && $input['type']=='collect'){
            if($user){
                $playerGameCollects = PlayerGameCollect::select('def_games.main_game_plat_id','inf_player_game_collect.game_id','inf_player_game_collect.id')
                    ->leftJoin('def_games','def_games.game_id','=','inf_player_game_collect.game_id')
                    ->where('inf_player_game_collect.player_id',$user->player_id)
                    ->where('inf_player_game_collect.game_category',2)->get();

                $carrierGamePlat       = CarrierGamePlat::where('carrier_id',$carrier->id)->whereIn('status',[1,2])->get();
                $carrierGame           = CarrierGame::where('carrier_id',$carrier->id)->where('game_category',2)->whereIn('status',[1,2])->get();

                $carrierGamePlatStatus = [];
                foreach ($carrierGamePlat as $key => $value) {
                    $carrierGamePlatStatus[$value->game_plat_id] = $value->status;
                }

                $carrierGameStatus = [];
                foreach ($carrierGame as $key => $value) {
                    $carrierGameStatus[$value->game_id] = $value->status;
                }

                $gameCollectIds = [];
                foreach ($playerGameCollects as $key => $value) {
                    if(isset($carrierGamePlatStatus[$value->main_game_plat_id]) && isset($carrierGameStatus[$value->game_id])){
                        $gameCollectIds[] = $value->id;
                    }
                }

                $query = PlayerGameCollect::select('def_games.*','def_games.game_name as display_name')
                    ->leftJoin('def_games','def_games.game_id','=','inf_player_game_collect.game_id')
                    ->where('inf_player_game_collect.id',$gameCollectIds)
                    ->orderBy('inf_player_game_collect.created_at','desc');

                if(isset($input['game_name']) && !empty($input['game_name'])) {
                    $query->where('def_games.game_name','like','%'.$input['game_name'].'%');
                }

                $total          = $query->count();
                $list           = $query->skip($offset)->take($pageSize)->get();

                foreach ($list as $key => &$value) {
                if($carrierGameStatus[$value->game_id]==2 || $carrierGamePlatStatus[$value->main_game_plat_id]==2){
                        $value->status = 2;
                    } else{
                        $value->status = 1;
                    }
                }

                return ['data' => $list->toArray(), 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];

            } else{
                return ['data' => [], 'total' => 0, 'currentPage' => 1, 'totalPage' => 0];
            }
            
        } else{
            return GameCache::electronic($carrier->id,$input,$prefix);
        }
    }

    public static function  getPcLiveLobby()
    {
        return self::where(function($query){
            $query->where('main_game_plat_code','bbin')->where('game_code','live');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','og')->where('game_code','lotty');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ab')->where('game_code',100);
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ag')->where('game_code','Asia_Gaming_Lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','dg')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','sexy')->where('game_code','MX-LIVE-001');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','bg')->where('game_code',1);
        })->orWhere(function($query){
            $query->where('main_game_plat_code','n2')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','sa')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','pmzr')->where('game_code','zrlobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','og')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','xg')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','cq9')->where('game_code','CA01');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','we')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','evo')->where('game_code','lobby');
        })->pluck('game_id')->toArray();
    }

    public static function  getMoblieLiveLobby()
    {
        return self::where(function($query){
            $query->where('main_game_plat_code','bbin')->where('game_moblie_code','live');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','og')->where('game_moblie_code','lotty');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ab')->where('game_moblie_code',100);
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ag')->where('game_moblie_code','Asia_Gaming_Lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','dg')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','sexy')->where('game_moblie_code','MX-LIVE-001');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','bg')->where('game_moblie_code',1);
        })->orWhere(function($query){
            $query->where('main_game_plat_code','n2')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','sa')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','pmzr')->where('game_moblie_code','zrlobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','cq9')->where('game_moblie_code','CA01');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','og')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','xg')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','we')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','evo')->where('game_moblie_code','lobby');
        })->pluck('game_id')->toArray();
    }

    public static function getPcSportLobby()
    {
        return self::where(function($query){
            $query->where('main_game_plat_code','oneworks')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','pb')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','bti')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','asia365')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','cmd')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','im')->where('game_code','IMSB');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','pmty')->where('game_code','sportlobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','newbb')->where('game_code','nball');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','s128')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','fb')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','im')->where('game_code','IMSB');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ds88')->where('game_code','lobby');
        })->pluck('game_id')->toArray();
    }


    public static function getMobileSportLobby()
    {
        return self::where(function($query){
            $query->where('main_game_plat_code','oneworks')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','pb')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','bti')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','asia365')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','cmd')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','pmty')->where('game_moblie_code','sportlobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','im')->where('game_moblie_code','IMSB');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','newbb')->where('game_moblie_code','nball');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','s128')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','fb')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','im')->where('game_moblie_code','IMSB');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ds88')->where('game_moblie_code','lobby');
        })->pluck('game_id')->toArray();
    }

    public static function getPcCardLobby()
    {
        return self::where(function($query){
            $query->where('main_game_plat_code','ky')->where('game_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ly')->where('game_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','nw')->where('game_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','lc')->where('game_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','tianyou')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','mp')->where('game_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','v8')->where('game_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','vg')->where('game_code','1000');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','kx')->where('game_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','pmqp')->where('game_code','qplobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','fl')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','tianyuqp')->where('game_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','kp')->where('game_code','main');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','cf')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','yg')->where('game_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','yoo')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','wd')->where('game_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','baison')->where('game_code','1000');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','lg')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','bole')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','gd')->where('game_code','xgd_lobby');
        })->pluck('game_id')->toArray();
    }

    public static function getMobileCardLobby()
    {
        return self::where(function($query){
            $query->where('main_game_plat_code','ky')->where('game_moblie_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ly')->where('game_moblie_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','nw')->where('game_moblie_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','lc')->where('game_moblie_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','tianyou')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','mp')->where('game_moblie_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','v8')->where('game_moblie_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','vg')->where('game_moblie_code','1000');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','kx')->where('game_moblie_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','pmqp')->where('game_moblie_code','qplobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','fl')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','kp')->where('game_moblie_code','main');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','tianyuqp')->where('game_moblie_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','cf')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','yg')->where('game_moblie_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','yoo')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','wd')->where('game_moblie_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','lg')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','bole')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','baison')->where('game_moblie_code','1000');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','gd')->where('game_moblie_code','xgd_lobby');
        })->pluck('game_id')->toArray();
    }

    public static function getPcLotteryLobby()
    {
         return self::where(function($query){
            $query->where('main_game_plat_code','vr')->where('game_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','tcg')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ig')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','bbin')->where('game_code','Ltlottery');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','bbin')->where('game_code','xlottery');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','pmcp')->where('game_code','lotterylobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','gg')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ap')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','jz')->where('game_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','cq9')->where('game_code','AA01');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','im')->where('game_code','imlotto30001');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','wg')->where('game_code','lottlobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','sg')->where('game_code','AULUCKY5');
        })->pluck('game_id')->toArray();
    }

    public static function getMobileLotteryLobby()
    {
        return self::where(function($query){
            $query->where('main_game_plat_code','vr')->where('game_moblie_code','0');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','tcg')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ig')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','bbin')->where('game_moblie_code','Ltlottery');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','bbin')->where('game_moblie_code','xlottery');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','pmcp')->where('game_moblie_code','lotterylobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','gg')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','cq9')->where('game_moblie_code','AA01');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','jz')->where('game_moblie_code','lobby');  
        })->orWhere(function($query){
            $query->where('main_game_plat_code','ap')->where('game_moblie_code','lobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','im')->where('game_moblie_code','imlotto30001');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','wg')->where('game_moblie_code','lottlobby');
        })->orWhere(function($query){
            $query->where('main_game_plat_code','sg')->where('game_moblie_code','AULUCKY5');
        })->pluck('game_id')->toArray();
    }

    public static function getPcFish()
    {
        return self::where('game_category',7)->pluck('game_id')->toArray();
    }

    public static function getMobileFish()
    {
        return self::where('game_category',7)->pluck('game_id')->toArray();
    }

    public static function getEsportLobby()
    {
        return self::where(function($query){
            $query->where('main_game_plat_code','tf')->orWhere('main_game_plat_code','ksesports')->orWhere('main_game_plat_code','pmdj');
        })->where('game_code','lobby')->pluck('game_id')->toArray();
    }
    
    public static function getMoblieesportlobby()
    {
        return self::where(function($query){
            $query->where('main_game_plat_code','tf')->orWhere('main_game_plat_code','ksesports')->orWhere('main_game_plat_code','pmdj');
        })->where('game_moblie_code','lobby')->pluck('game_id')->toArray();
    }

    public function liveList($carrier,$input,$prefix)
    {
        return GameCache::live($carrier->id,$input,$prefix);
    }

    public function sportList($carrier,$input,$prefix)
    {
        return GameCache::sport($carrier->id,$input,$prefix);
    }

    public function esportList($carrier,$input)
    {
        return GameCache::esport($carrier->id,$input);
    }

    public function fishList($carrier,$input,$prefix)
    {
        return GameCache::fish($carrier->id,$input,$prefix);
    }

    public function cardList($carrier,$input,$prefix)
    {
        return GameCache::card($carrier->id,$input,$prefix);
    }

    public function cardSubList($carrier)
    {
        return GameCache::cardSubList($carrier->id);
    }

    public function lotteryList($carrier,$input,$prefix)
    {
        return GameCache::lotteryList($carrier->id,$input,$prefix);
    }

    public function platsList($carrier)
    {
       return GameCache::getPlatsList($carrier->id,request()->all());
    }

    public function hotGameList($carrier,$input,$prefix){
        return GameCache::hotGameList($carrier->id,$input,$prefix);
    }

    public function getLotteryCode($carrier)
    {
        
    }

    public function mainGamePlat() 
    {
        return $this->belongsTo(MainGamePlat::class,'main_game_plat_id','main_game_plat_id');
    }

    public function carrierGamePlat() 
    {
        return $this->belongsTo(CarrierGamePlat::class,'main_game_plat_id','game_plat_id');
    }

    public function scopeOpen(Builder $query) 
    {
        return $query->where('status' , self::STATUS_AVAILABLE);
    }

    public function scopeInIds(Builder $query, $ids) 
    {
        return $query->whereIn('game_id',$ids);
    }
}
