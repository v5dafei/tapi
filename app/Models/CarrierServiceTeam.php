<?php

namespace App\Models;

use App\Models\RolesModel\Permission;
use App\Models\RolesModel\PermissionGroup;
use App\Scopes\CarrierScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarrierServiceTeam extends Model
{

    public $table = 'inf_carrier_service_team';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'carrier_id',
        'team_name',
        'remark',
        'status'
    ];

    protected $casts = [
        'id' => 'integer',
        'carrier_id' => 'integer',
        'team_name' => 'string',
        'remark' => 'string'
    ];

    public static $rules = [
        'remark'=>'string',
    ];

    public function teamPermissions()
    {
        return $this->belongsToMany(Permission::class,'inf_carrier_service_team_role','team_id','permission_id');
    }

    public function teamRoles()
    {
        return $this->hasMany(CarrierServiceTeamRole::class,'team_id','id');
    }

    public function scopeByCarrierId(Builder $query,$carrier_id)
    {
        return $query->where('carrier_id',$carrier_id);
    }

    public function scopeAdministrator(Builder $query)
    {
        return $query->where('is_administrator',1);
    }

    public function scopeNoAdministrator(Builder $query)
    {
        return $query->where('is_administrator',0);
    }

    public function saveItem($carrierId)
    {
        $input = request()->all();
        if(!isset($input['team_name']) || empty($input['team_name'])){
            return '对不起参数错误';
        }

        if(isset($this->team_name) && $this->team_name=='超级管理员'){
            return '超级管理员不能编辑';
        }
        $this->team_name = $input['team_name'];
        if($this->id){
            $this->team_name = $input['team_name'];
            
        } else {
            $this->team_name  = $input['team_name'];
        }

        $this->save();
        return true;
    }
}
