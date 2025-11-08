<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class CarrierHorizontalMenu extends Model
{
    use Notifiable;

    public $table = 'inf_carrier_horizontal_menu';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $fillable = [
       
    ];

    protected $casts = [
       
    ];

    public $rules = [
    ]; 

    public $messages = [
        
    ];

    public function updateHorizontalMenus($carrier,$carrierUser)
    {
        $input = request()->all();

        $data = [
            'fish'       => ['key' => 'FISH','api'=>'/api/fish/list'],
            'lottery'    => ['key' => 'LOTT','api'=>'/api/lottery/list'],
            'sport'      => ['key' => 'SPORT','api'=>'/api/sport/list'],
            'card'       => ['key' => 'PVP','api'=>'/api/card/list'],
            'esport'     => ['key' => 'ESPORT','api'=>'/api/esport/list'],
            'electronic' => ['key' => 'RNG','api'=>'/api/electronic/categorylist'],
            'live'       => ['key' => 'LIVE','api'=>'/api/live/list'],
            'hotgamelist'=> ['key' => 'HOT','api'=>'/api/hotgamelist']
        ];

        if(!isset($input['type']) || !in_array($input['type'],['fish','lottery','sport','card','esport','electronic','live','hotgamelist'])){
            return '对不起,类型取值不正确';
        }

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return '对不起,站点取值不正确';
        }
        if(isset($input['sort']) && is_numeric($input['sort'])){
            $this->sort = $input['sort'];
        }

        if(isset($input['status']) && in_array($input['status'],[0,1])){
            $this->status = $input['status'];
        }

        $this->carrier_id = $carrier->id;
        $this->prefix     = $input['prefix'];
        $this->type       = $input['type'];
        $this->key        = $data[$input['type']]['key'];
        $this->api        = $data[$input['type']]['api'];
        $this->save();

        return true;
    }
}
