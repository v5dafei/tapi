<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Def\MainGamePlat;
use App\Models\Carrier;

class PlayerBetFlowMiddle extends Model
{
    public $table = 'log_player_bet_flow_middle';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'player_id',
        'carrier_id',
        'day',
        'game_category',
        'bet_amount',
        'available_bet_amount',
        'company_win_amount',
        'stat_time'
    ];

    protected $casts = [
    ];

    public static $rules = [];
}
