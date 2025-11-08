<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class CarrierActivityPlayerLuckDraw extends Model
{
    public $table = 'inf_carrier_activity_player_luck_draw';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
       
    ];

    protected $casts = [
       
    ];

    public static $rules = [

    ];

}
