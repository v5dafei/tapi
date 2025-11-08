<?php

namespace App\Models\Def;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{

    public $table = 'def_domain';

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
