<?php

namespace App\Models\RolesModel;

use App\Models\RolesModel\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PermissionGroup extends Model
{

    public $table = 'permission_group';
    
    public $timestamps = true;

    public $fillable = [
        'group_name',
        'sort',
        'parent_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id'         => 'integer',
        'group_name' => 'string',
        'sort'       => 'integer',
        'parent_id'  => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public static $rules = [

    ];

    protected $hidden = ['created_at','updated_at'];

    public function scopeTopGroup(Builder $query)
    {
        return $query->where('parent_id', 0);
    }

    public function scopeOrderBySort(Builder $query,$type)
    {
        return $query->orderBy('sort',$type);
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class,'group_id','id');
    }

    public function groups()
    {
        return $this->hasMany(PermissionGroup::class,'parent_id','id');
    }

    public function saveItem()
    {
        $input = request()->all();
        if(!isset($input['group_name']) || empty($input['group_name']) || !isset($input['sort']) || !is_numeric($input['sort']) ){
            return '菜单名或排序字段错误';
        }

        if($input['parent_id']!=0){
            $permissionGroup = PermissionGroup::where('id',$input['parent_id'])->first();
            if(!$permissionGroup){
                return '对不起，上级菜单不存在';
            }
        }

        if($this->id){
            if($this->id==$permissionGroup->parent_id){
                return '对不起，上下级不能对调';
            }

        } 

        $this->group_name = $input['group_name'];
        $this->sort       = $input['sort'];
        $this->parent_id  = $input['parent_id']; 
        $this->created_at = date('Y-m-d H:i:s');  
        $this->save();
        
        return true;
    }
}
