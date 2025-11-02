<?php

namespace App\Models;

use App\Models\Def\MainGamePlat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PlayerGameCollect extends Model
{
    public $table = 'inf_player_game_collect';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
    ];

    protected $casts = [
    ];
}
