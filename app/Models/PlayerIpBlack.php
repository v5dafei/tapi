<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class PlayerIpBlack extends Model
{
    public $table    = 'inf_player_ip_black';

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

    public function playerIpblackUpdate()
    {
        $input = request()->all();
        if(!array_key_exists('ips',$input)){
            return '对不起,IP不能为空';
        } else {
            if(empty($input['ips'])){
               $this->ips = '';
            } else {
               $ipsArr = explode(',',$input['ips']);
               foreach ($ipsArr as $key => $value) {
                   $isIp = filter_var($value, FILTER_VALIDATE_IP);
                   if(!$isIp){
                      return '对不起,ip取值不下确';
                   }
               }
               $this->ips = $input['ips'];
            }

            $this->save();
            return true;
        }
    }
}
