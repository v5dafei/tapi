<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Auth;
use Tymon\JWTAuth\Contracts\JWTSubject;

class CarrierUser extends Auth implements JWTSubject
{
    public $table = 'inf_carrier_user';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $hidden = ['password','remember_token','is_super_admin'];

    protected $primaryKey = 'id';

    public $fillable = [
        'team_id',
        'username',
        'password',
        'status',
        'login_at'
    ];

    protected $casts = [
        'team_id'    => 'integer',
        'status'     => 'integer',
        'username'   => 'string',
        'password'   => 'string',
        'login_at'   =>'string',
        'is_super_admin' => 'boolean'
    ];

    public static $requestAttributes = [
        'username'=> '客服账号',
        'password' => '密码',
        'team_id' => '所属部门',
        'status' => '状态',
    ];

    public  $rules = [
        'team_id'            => 'required|exists:inf_carrier_service_team,id',
        'username'           => 'required|min:4|max:11|string',
        'password'           => 'required|min:32|max:32|string',
    ];

    public $messages = [
        'team_id.required'        => '角色ID必须填写',
        'team_id.exists'          => '角色ID不存在',
        'username.required'       => '帐号必须填写',
        'username.min'            => '帐号必须大于等于4个字符',
        'username.max'            => '帐号必须小于等于11个字符',
        'password.required'       => '密码必须填写',
        'password.min'            => '密码长度必须等于32个字府',
        'password.max'            => '密码长度必须等于32个字府'
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
