<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Cache\CarrierCache;

class CarrierDigitalAddress extends Model
{
    public $table    = 'inf_carrier_digital_address';

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

    public function digitalAdd($user,$carrier)
    {
        $input = request()->all();

        $language = CarrierCache::getLanguageByPrefix($user->prefix);

        if(!isset($input['status']) || !in_array($input['status'], [0,1])){
            return config('language')[$language]['error181'];
        }

        if(!isset($input['type']) || !in_array($input['type'], [1,2])){
            return config('language')[$language]['error185'];
        }

        if(!isset($input['sort']) || !is_numeric($input['sort'])){
            return config('language')[$language]['error250'];
        }

        if(!isset($input['address']) || empty($input['address'])){
            return config('language')[$language]['error183'];
        }

        $existDigitalAddress =  self::where('carrier_id',$carrier->id)->where('type',$input['type'])->where('status',1)->first();

        if($this->id){
            if($existDigitalAddress && $input['status']==1 && $existDigitalAddress->id != $this->id){
                return config('language')[$language]['error186'];
            }
        } else {
            if($existDigitalAddress && $input['status']==1){
                return config('language')[$language]['error186'];
            }
        }

        $this->carrier_id = $carrier->id;
        $this->address    = $input['address'];
        $this->type       = $input['type'];
        $this->status     = $input['status'];
        $this->sort       = $input['sort'];
        $this->adminId    = $user->id;
        $this->save();

        return true;
    }
}
