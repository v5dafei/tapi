<?php

namespace App\Models\RolesModel;

use Illuminate\Database\Eloquent\Model;
use App\Models\RolesModel\Permission;
use App\Models\RolesModel\Role;

class PermissionServiceTeam extends Model
{

    public $table      = 'permission_service_team';
    
    public $timestamps = false;

    public $fillable = [
        'permission_id',
        'service_team_id'
    ];

    protected $hidden = ['created_at','updated_at'];

    protected $casts = [
        'permission_id'         => 'integer',
        'service_team_id'       => 'integer'
    ];

    public static $rules = [
    ];
}
