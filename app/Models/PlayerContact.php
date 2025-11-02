<?php

namespace App\Models;

use App\Utils\Arr\ArrHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class PlayerContact extends Model
{
    public $table    = 'inf_player_contact';

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
