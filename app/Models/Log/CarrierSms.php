<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Log\Carrier;

class CarrierSms extends Model
{
   
    public $table    = 'log_carrier_sms';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
    
    ];

    protected $casts = [
       
    ];

    public static $rules = [];
}
