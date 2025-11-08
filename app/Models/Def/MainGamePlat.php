<?php

namespace App\Models\Def;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Map\CarrierGamePlat;
use App\Models\Map\CarrierGame;
use App\Models\Def\Game;
use App\Models\Carrier;
use App\Models\PlayerGameAccount;
use App\Lib\Cache\GameCache;

class MainGamePlat extends Model
{
    const STATUS_AVAILABLE = 1;
    const STATUS_CLOSED    = 0;

    const MA       = 'main';
    const AG       = 'ag';
    const PT       = 'pt';
    const MG       = 'mg';
    const JDB      = 'jdb';
    const KY       = 'ky';
    const BBIN     = 'bbin';
    const DS       = 'ds';
    const TCG      = 'tcg';
    const OG       = 'og';
    const SA       = 'sa';
    const SUNBET   = 'sunbet';
    const PB       = 'pb';
    const RMG      = 'rmg';
    const AB       = 'ab';
    const ICG      = 'icg';
    const CG       = 'cg';
    const ONEWORKS = 'oneworks';
    const AE       = 'ae';
    const TTG      = 'ttg';
    const VR       = 'vr';
    const LC       = 'lc';
    const LY       = 'ly';
    const NW       = 'nw';
    const TIANHAO  = 'tianhao';
    const CQ9      = 'cq9';
    const DG       = 'dg';
    const SEXY     = 'sexy';

    public $table = 'def_main_game_plats';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'main_game_plat_id';

    public $fillable = [
        'main_game_plat_name',
        'status',
        'main_game_plat_code',
        'account_pre',
        'alias',
        'sort',
    ];

    /**
     * 主游戏平台代码
     * @var array
     */
    public static $gamePlatCode = [
    	self::PB,
        self::TCG,
        self::AG,
        self::BBIN,
        self::SUNBET,
        self::OG,
        self::SA,
        self::CG,
        self::MG,
        self::PT,
        self::KY,
        self::DS,
        self::JDB,
        self::RMG,
        self::AB,
        self::MA,
        self::ICG,
        self::ONEWORKS,
        self::AE,
        self::TTG,
        self::VR,
        self::LC,
        self::LY,
        self::NW,
        self::TIANHAO,
        self::CQ9,
        self::DG,
        self::SEXY,
    ];

    protected $casts = [
        'main_game_plat_id' => 'integer',
        'main_game_plat_name' => 'string'
    ];

    public static $rules = [

    ];

    public function gamePlatChangeStatus()
    {
        $input['status'] = $this->status ? 0 : 1;
        $gameIds         = Game::where('main_game_plat_id',$this->main_game_plat_id)->pluck('game_id')->toArray();

        Game::where('main_game_plat_id',$this->main_game_plat_id)->update(['status'=>$input['status']]);

        if($input['status'] == 0) {
            CarrierGamePlat::where('game_plat_id',$this->main_game_plat_id)->update(['status'=>$input['status']]);
            CarrierGame::whereIn('game_id',$gameIds)->update(['status'=>0]);
        }
        
        $this->status = $input['status'];
        $this->save();

        $carriers = Carrier::all();

        foreach ($carriers as $key => $value) {
            GameCache::flushCarrierGame($value->id);
        }

        return true;
    }

    public function gameplatDel()
    {
        $carrierGamePlat = CarrierGamePlat::where('game_plat_id',$this->main_game_plat_id)->first();

        if($carrierGamePlat) {
            return '对不起,此平台有商户在使用不能删除';
        }

        Game::where('main_game_plat_id',$this->main_game_plat_id)->delete();
        $this->delete();

        return true;
    }

    public function saveItem()
    {
        $input            = request()->all();
        $mainGamePlat     = self::where('main_game_plat_code',$input['main_game_plat_code'])->first();

        if($this->main_game_plat_id) {
            if($mainGamePlat && $mainGamePlat->main_game_plat_id != $this->main_game_plat_id) {
                return '对不起,此平台已存在';
            }
        } else {
            if($mainGamePlat) {
                return '对不起,此平台已存在';
            }
        }

        $this->main_game_plat_code = $input['main_game_plat_code'];
        $this->alias               = $input['alias'];
        $this->sort                = $input['sort'];
        $this->save();
        
        return true;
    }

    public function scopeActive(Builder $query) {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function PlayerGameAccount() {
        return $this->hasMany(PlayerGameAccount::class, 'main_game_plat_id', 'main_game_plat_id');
    }

}
