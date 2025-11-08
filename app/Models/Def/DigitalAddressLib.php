<?php
namespace App\Models\Def;

use Illuminate\Database\Eloquent\Model;

class DigitalAddressLib extends Model
{

    public $table = 'def_digital_address_lib';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    ];

    protected $casts = [
    ];

    public static $rules = [];
}
