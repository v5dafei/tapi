<?php

namespace App\Models\Report;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Map\CarrierGame;
use App\Models\Def\Game;

class ReportGamePlatStatDay extends Model
{
    public $table    = 'report_gameplat_stat_day';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
    ];

    protected $casts = [
    ];
}
