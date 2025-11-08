<?php

namespace App\Models;

use App\Utils\Arr\ArrHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Cache\CarrierCache;
use App\Models\Conf\CarrierPayChannel;
use App\Models\CarrierBankCard;

class PlayerCommission extends Model
{
    public $table    = 'report_player_commission';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
       
    ];

    protected $casts = [
    ];

    public $rules = [
        
    ];

    public $messages = [
        
    ];
}
