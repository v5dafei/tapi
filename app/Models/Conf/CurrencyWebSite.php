<?php

namespace App\Models\Conf;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

class CurrencyWebSite extends BaseModel
{

    protected $table = 'conf_currency_web_site';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    ];

    protected $casts = [
    ];
}
