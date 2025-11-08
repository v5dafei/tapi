<?php

namespace App\Models\RolesModel;

use Illuminate\Database\Eloquent\Model;
use App\Models\RolesModel\PermissionRole;
use App\Models\RolesModel\PermissionGroup;

class Permission extends Model
{

    public $table = 'permissions';
    
    public $timestamps = true;

    public $fillable = [
        'name',
        'description',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id'          => 'integer',
        'name'        => 'string',
        'description' => 'string',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime'
    ];

    protected $hidden = ['created_at','updated_at'];

    public static $rules = [
        'name'         => 'required|unique:inf_admin_permissions',
        'display_name' => 'required|unique:inf_admin_permissions',
        'description'  => 'required|unique:inf_admin_permissions'
    ];

    public function roles()
    {
        return $this->hasMany(PermissionRole::class,'permission_id','id');
    }

    public function permissionGroup()
    {
        return $this->belongsTo(PermissionGroup::class,'group_id','id');
    }

    public function saveItem()
    {
        $input = request()->all();
        if(!isset($input['name']) || empty($input['name']) || !isset($input['description']) || empty($input['description']) || !isset($input['frontroute']) ||empty($input['frontroute'])||!isset($input['group_id']) || empty($input['group_id'])){
            return '对不起参数不能为空';
        }

        $permissionGroup = PermissionGroup::where('id',$input['group_id'])->first();
        if(!$permissionGroup){
            return '对不起关联菜单不存在';
        }

        $existPermission   = self::where('name',$input['name'])->orWhere('frontroute',$input['frontroute'])->first();
        if($this->id && isset($existPermission->id) && $this->id != $existPermission->id){
            return '对不起此路由或此前端路由已存在';
        }
        
        $this->group_id    = $input['group_id'];
        $this->name        = $input['name'];
        $this->description = $input['description'];
        $this->frontroute  = $input['frontroute'];
        $this->save();

        return true;
    }
}
