<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Auth;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class AdminUser extends Auth implements JWTSubject
{
    use Notifiable;

    public $table = 'inf_admin_user';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
        'username',
        'password',
        'mobile',
        'email',
        'status',
        'create_time',
        'last_login_time',
        'login_ip',
        'parent_id',
        'remember_token'
    ];

    protected $casts = [
        'id'       => 'integer',
        'username' => 'string',
        'password' => 'string',
        'mobile'   => 'string',
        'email'    => 'string',
        'login_ip' => 'integer',
        'parent_id'=> 'integer'
    ];

    public static $rules = [

    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

}
