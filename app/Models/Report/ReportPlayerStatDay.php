<?php

namespace App\Models\Report;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Map\CarrierGame;
use App\Models\Def\Game;

class ReportPlayerStatDay extends Model
{
    public $table    = 'report_player_stat_day';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
        'carrier_id',
        'rid',
        'top_id',
        'parent_id',
        'player_id',
        'is_tester',
        'user_name',
        'level',
        'have_bet',
        'recharge_amount',
        'recharge_count',
        'first_recharge_count',
        'withdraw_amount',
        'available_bets',
        'win_amount',
        'dividend',
        'gift',
        'team_first_register',
        'team_have_bet',
        'team_recharge_amount',
        'team_recharge_count',
        'team_first_recharge_count',
        'team_withdraw_amount',
        'team_available_bets',
        'team_win_amount',
        'team_dividend',
        'team_gift',
        'lottery_available_bets',
        'lottery_commission',
        'team_lottery_available_bets',
        'team_lottery_commission',
        'put_reward',
        'team_put_reward',
        'get_reward',
        'team_get_reward',
        'day',
        'prefix',
        'stock',
        'team_stock',
        'page_recharge_amount',
        'page_team_recharge_amount',
        'change_stock',
        'change_team_stock'
    ];

    protected $casts = [
    ];
}