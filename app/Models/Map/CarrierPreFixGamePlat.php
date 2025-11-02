<?php

namespace App\Models\Map;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CarrierPreFixGamePlat extends Model
{
    const STATUS_AVAILABLE = 1;
    const STATUS_CLOSED    = 0;

    public $table = 'map_carrier_prefix_game_plats';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
       
    ];

    protected $casts = [
       
    ];

    public static $rules = [

    ];
}
