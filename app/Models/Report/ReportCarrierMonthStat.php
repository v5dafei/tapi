<?php

namespace App\Models\Report;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReportCarrierMonthStat extends Model
{
    public $table    = 'report_carrier_month_stat';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
    ];

    protected $casts = [
    ];
}
