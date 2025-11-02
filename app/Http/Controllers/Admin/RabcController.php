<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Authenticatable;
use App\Http\Controllers\Admin\BaseController;
use App\Models\RolesModel\PermissionGroup;
use App\Models\RolesModel\Permission;

class RabcController extends BaseController
{
    use Authenticatable;

    public function menusList() 
    {
        $menus = PermissionGroup::where('parent_id',0)->get();
        foreach ($menus as $key => $value) {
          $sonmenus = PermissionGroup::where('parent_id',$value->id)->get();
          foreach ($sonmenus as $k => $v) {
            $sonmenu['id']              = $v->id;
            $sonmenu['group_name']      = $v->group_name;
            $sonmenu['sort']            = $v->sort;
            $sonmenu['parent_id']       = $v->parent_id;
            $sonmenus[$k]               = $sonmenu;
          }
          $menu['id']                = $value->id;
          $menu['group_name']        = $value->group_name;
          $menu['sort']              = $value->sort;
          $menu['parent_id']         = $value->parent_id;
          $menu['sonmenu']           = $sonmenus;
          $menus[$key]               = $menu;
        }
        return returnApiJson('操作成成', 1, $menus);
    }

    public function menusAdd($menuId=0)
    {
      if($menuId){
          $permissionGroup = PermissionGroup::where('id',$menuId)->first();
      } else {
          $permissionGroup = new PermissionGroup();
      }
      $output = $permissionGroup->saveItem();
      if($output===true){
        return returnApiJson('操作成成', 1);
      } else {
        return returnApiJson($output, 0);
      }
    }

    public function menusDel($menuId)
    {
       $permissionGroup = PermissionGroup::where('parent_id',$menuId)->first();
       if($permissionGroup){
          return returnApiJson('对不起此菜单还有下级菜单无法删除', 0);
       }

       $permission      = Permission::where('group_id',$menuId)->first();
       if($permission){
          return returnApiJson('对不起此菜单还有路由无法删除', 0);
       }

       PermissionGroup::where('id',$menuId)->delete();

       return returnApiJson('操作成成', 1);
    }

    public function premissionList()
    {
        $input       = request()->all();
        $query       = Permission::select('permissions.*', 'permission_group.group_name')->leftJoin('permission_group', 'permission_group.id', '=', 'permissions.group_id')->orderBy('permissions.id', 'DESC');

        $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
        $offset      = ($currentPage - 1) * $pageSize;

        if(isset($input['group_id']) && !empty($input['group_id'])){
          $query->where('permissions.group_id',$input['group_id']);
        }

        $permissionGroups    = PermissionGroup::select('id','group_name')->get();
        $permissionGroupArrs = [];

        foreach ($permissionGroups as $key => $value) {
          $permissionGroupArrs[$value->id] = $value->group_name;
        }
        $total            = $query->count();
        $items            = $query->skip($offset)->take($pageSize)->get();

        return [ 'data' => $items, 'total' => $total, 'menus'=>$permissionGroupArrs,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ];
    }

    public function premissionDel($permissionId)
    {
        $permission = Permission::where('id',$permissionId)->first();
        if(!$permission){
          return returnApiJson('对不起，这条数据不存在', 0);
        }
        $permission->delete();
         return returnApiJson('操作成功', 1);
    }

    public function premissionAdd($permissionId=0)
    {
      if($permissionId){
          $permission = Permission::where('id',$permissionId)->first();
          if(!$permission){
            return returnApiJson('对不起，引路由不存在', 0);
          }
      } else {
          $permission = new Permission();
      }
      $output = $permission->saveItem();
      if($output===true){
        return returnApiJson('操作成功', 1);
      } else {
        return returnApiJson($output, 0);
      }
    }
}
