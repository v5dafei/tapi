<?php

namespace App\Models\Map;

use Illuminate\Database\Eloquent\Model;

class CarrierPlayerLevelBankCardMap extends Model
{

    public $table = 'map_carrier_player_level_pay_channel';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'carrier_player_level_id',
        'carrier_pay_channle_id'
    ];

    protected $casts = [
        'map_id' => 'integer',
        'carrier_player_level_id' => 'integer',
        'carrier_pay_channle_id' => 'integer'
    ];

    public static $rules = [
        
    ];


    public function carrierBankCards()
    {
        return $this->hasOne(\App\Models\CarrierPayChannel::class,'id','carrier_pay_channle_id');
    }

    public function carrierPlayerLevel()
    {
        return $this->hasOne(\App\Models\CarrierPlayerGrade::class,'id','carrier_player_level_id');
    }
}
