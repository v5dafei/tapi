<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class PayChannelGroup extends Model
{
    public $table    = 'inf_pay_channel_group';

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

    public function payChannelGroupAdd($carrier)
    {
        $input = request()->all();

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return '对不起，站点名称不能为空';
        }

        if(!isset($input['name']) || empty($input['name'])){
            return '对不起，支付通道分组名称不能为空';
        }

        if(!isset($input['sort']) || !is_numeric($input['sort'])){
            return '对不起，支付通道分组排序不能为空';
        }

        if(isset($input['img']) && !empty($input['img'])){
            $this->img = $input['img'];
        }

        if(isset($input['carrier_pay_channel_ids']) && !empty($input['carrier_pay_channel_ids'])){
            $this->carrier_pay_channel_ids = $input['carrier_pay_channel_ids'];
        }

        if(!isset($input['currency']) || empty($input['currency'])){
           return '对不起，币种不能为空';
        }

        $this->currency   = $input['currency'];
        $this->prefix     = $input['prefix'];
        $this->name       = $input['name'];
        $this->sort       = $input['sort'];
        $this->carrier_id = $carrier->id;
        $this->save();
        
        return true;
    }
}
