<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Log\Carrier;
use Illuminate\Support\Facades\DB;

class Currency extends Model
{
   
    public $table = 'def_currency';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $fillable = [
    
    ];

    protected $casts = [
       
    ];

    public static $rules = [];
}
