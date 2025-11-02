<?php

namespace App\Models\Def;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Development extends Model
{

    public $table = 'def_account_change_type';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';


    public $fillable = [
       
    ];

    protected $casts = [

    ];

    public static $rules = [

    ];

    public static $aliases = [
        
    ];

}
