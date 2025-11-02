<?php

namespace App\Models\Conf;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

class PlayerSetting extends BaseModel
{
    
    protected $table = 'conf_player_setting';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'player_id',
        'carrier_id',
        'top_id',
        'parent_id',
        'rid',
        'lottoadds',
        'is_tester',
        'user_name',
        'level',
        'video_earnings',
        'earnings',
        'guaranteed'
    ];

    protected $casts = [
    ];
}
