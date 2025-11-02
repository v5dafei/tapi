<?php
namespace App\Models\Def;

use Illuminate\Database\Eloquent\Model;

class Alipay extends Model
{

    public $table = 'def_alipay';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    ];

    protected $casts = [
    ];

    public static $rules = [];
}
