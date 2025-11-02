<?php

namespace App\Models\Map;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PayFactoryBankCode extends Model
{

    public $table = 'map_pay_factory_bank_code';
    
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
