<?php

namespace App\Models\Map;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PlayerWebsite extends Model
{

    public $table = 'map_player_website';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
        
    ];

    protected $casts = [
        
    ];

    public $rules = [
    ];
}
