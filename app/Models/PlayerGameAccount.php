<?php

namespace App\Models;

use App\Models\Def\MainGamePlat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PlayerGameAccount extends Model
{
    public $table = 'inf_player_game_account';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'account_id';

    public $fillable = [
        'main_game_plat_id',
        'player_id',
        'amount',
        'is_locked',
        'account_user_name',
        'extra_field',
        'is_need_repair',
        'balance',
        'exist_transfer',
        'carrier_id'
    ];

    protected $casts = [
        'account_id'        => 'integer',
        'main_game_plat_id' => 'integer',
        'player_id'         => 'integer',
        'amount'            => 'numeric',
        'is_locked'         => 'integer',
        'is_need_repair'    => 'integer',
        'balance'           => 'numeric',
        'exist_transfer'    => 'integer',
        'carrier_id'        => 'integer'
    ];

    public function getAmountAttribute($value = null){
        return isset($value) ? floatval($value) : 0.0;
    }

    public static $rules = [

    ];

    public function scopeByPlayerId(Builder $query,$playerId)
    {
        return $query->where('player_id',$playerId);
    }

    public function scopeByMainGameId(Builder $query,$mainGameId)
    {
        return $query->where('main_game_plat_id',$mainGameId);
    }

    public function scopeByMainGamePlatIdNotIn(Builder $query,$notInIds)
    {
        return $query->whereNotIn('main_game_plat_id',$notInIds);
    }

    public function scopeRetrieveByAccountUserName(Builder $query,$accountUserName)
    {
        return $query->where('account_user_name',$accountUserName);
    }

    public function player()
    {
        return $this->belongsTo(Player::class,'player_id','player_id');
    }

    public function mainGamePlat()
    {
        return $this->belongsTo(MainGamePlat::class,'main_game_plat_id','main_game_plat_id');
    }

    public static function generateValue($length = 8, $pre = '', $carrier_id = '0')
    {
        // 密码字符集，可任意添加你需要的字符
        $chars  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXY0123456789';
        $value  = '';

        for ($i = 0; $i < $length; $i ++) {
            $value .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        $value = $pre.$carrier_id.'Z'.$value;
        $info  = self::retrieveByAccountUserName($value)->first();
        if ($info){
            return self::generateValue($length,$pre, $carrier_id);
        }
        return $value;
    }
}
