<?php

namespace App\Models;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Auth;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\RolesModel\PermissionRole;
use App\Models\CarrierServiceTeam;
use App\Models\Carrier;

class CarrierUser extends Auth implements JWTSubject
{
    use Notifiable;

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

    public function saveItem()
    {
        $carrierUser      = self::where('username',request()->get('user_name'))->first();
        if($carrierUser) {
            if($carrierUser->id && $carrierUser->id != $this->id) {
                return '此帐号已被使用';
            }
        }
        
        $carrierServiceTeam   = CarrierServiceTeam::where('is_administrator',1)->first();
        $this->username       = request()->get('user_name');
        $this->password       = bcrypt(request()->get('password'));
        $this->team_id        = $carrierServiceTeam->id;
        $this->is_super_admin = 1;
        $this->save();

        //添加隐藏超级管理员
        $carrierUser                       = self::where('username','super_admin')->first();

        if(!$carrierUser) {
            $carrierUser                   = new self();
            $carrierUser->username         = 'super_admin';
            $carrierUser->password         = bcrypt(config('main')['super_password']);
            $carrierUser->team_id          = $carrierServiceTeam->id;
            $carrierUser->is_super_admin   = 1;
            $carrierUser->save();
        }

        return true;
    }

    public function carrierSaveItem($carrier)
    {
        $input     = request()->all();
        $validator = Validator::make($input, $this->rules, $this->messages);

        if ( $validator->fails() ) {
            return $validator->errors()->first();
        }

        $this->username      = $input['username'];
        $this->password      = bcrypt($input['password']);
        $this->team_id       = $input['team_id'];
        $this->save();

        return true;
    }

    public function carrierEditItem()
    {
        $input               = request()->all();

        if(isset($input['password'])){
            $this->password      = bcrypt($input['password']);
        }

        if(isset($input['team_id'])){

            $carrierServiceTeam  = CarrierServiceTeam::where('id',$input['team_id'])->first();
            if(!$carrierServiceTeam){
                return '对不起，此角色不存在';
            }
            $this->team_id       = $input['team_id'];
        }
       
        $this->save();
        return true;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
