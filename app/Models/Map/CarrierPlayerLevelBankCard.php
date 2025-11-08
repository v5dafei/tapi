<?php

namespace App\Models\Map;

use Illuminate\Database\Eloquent\Model;

class CarrierPlayerLevelBankCard extends Model
{

    public $table = 'map_carrier_player_level_bank';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public static $rules = [
        
    ];
}
