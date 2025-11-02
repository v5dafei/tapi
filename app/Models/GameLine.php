<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameLine extends Model
{
    public $table    = 'def_game_line';

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
