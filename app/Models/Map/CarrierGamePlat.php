<?php

namespace App\Models\Map;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Def\MainGamePlat;

class CarrierGamePlat extends Model
{
    const STATUS_AVAILABLE = 1;
    const STATUS_CLOSED    = 0;

    public $table = 'map_carrier_game_plats';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'carrier_id',
        'status',
        'game_plat_id',
        'sort'
    ];

    protected $casts = [
        'id'           => 'integer',
        'carrier_id'   => 'integer',
        'game_plat_id' => 'integer',
        'sort'         => 'integer'
    ];

    public static $rules = [

    ];

    public static $requestAttributes = [
        'status' => '状态',
        'sort'   => '排序'
    ];

    public static function updateRules($current_carrier_id,$id)
    {
        return array_merge(self::$rules,[
            'status'    => 'boolean|required',
            'sort'      => 'integer|min:1|max:99|required',
        ]);
    }

    public function gamePlat()
    {
        return $this->hasOne(MainGamePlat::class,'main_game_plat_id','game_plat_id');
    }

    public function scopeOpen(Builder $query)
    {
        return $query->where('status' , self::STATUS_AVAILABLE);
    }

    public function scopeByCarrierId(Builder $query, $carrierId)
    {
        return $query->where('carrier_id',$carrierId);
    }

    public function scopeByGamePlats(Builder $query, $gamePlatIds)
    {
        return $query->whereIn('game_plat_id',$gamePlatIds);
    }

    public static function statusMeta()
    {
        return [ self::STATUS_AVAILABLE => '正常', self::STATUS_CLOSED => '关闭'];
    }
}
