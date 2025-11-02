<?php

namespace App\Models\Conf;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

class CarrierMultipleFront extends BaseModel
{
    
    protected $table = 'conf_carrier_multiple_front';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    ];

    protected $casts = [
    ];
}
