<?php

namespace App\Models\Map;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Def\MainGamePlat;
use App\Models\Def\Game;
use App\Models\Carrier;

class CarrierGame extends Model
{

    const STATUS_AVAILABLE = 1;
    const STATUS_CLOSED    = 0;

    public $table = 'map_carrier_games';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'carrier_id',
        'game_id',
        'display_name',
        'sort',
        'status',
        'is_recommend',
        'is_hot',
        'game_category'
    ];

    protected $casts = [
        'id'           => 'integer',
        'carrier_id'   => 'integer',
        'game_id'      => 'string',
        'display_name' => 'string',
        'sort'         => 'integer',
        'status'       => 'integer'
    ];

    public function scopeOpen(Builder $query)
    {
        return $query->where('status' , self::STATUS_AVAILABLE);
    }

    public function scopeByGameIds(Builder $query,$gameIds)
    {
        return $query->whereIn('game_id',$gameIds);
    }

    public function scopeByCarrierIds(Builder $query, $carrierIds)
    {
        return $query->whereIn('carrier_id',$carrierIds);
    }

    public static $rules = [
        'status' => 'boolean',
    ];

    public static function updateRules($current_carrier_id,$id)
    {
        return array_merge(self::$rules,['display_name' => 'required|max:20','sort' => 'integer|min:1|max:99|required']);
    }

    public function game() 
    {
        return $this->hasOne(Game::class,'game_id','game_id');
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class,'carrier_id','id');
    }
}
