<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Auth;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Player extends Auth implements JWTSubject
{
    public $table = 'inf_player';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'player_id';
    
    protected $casts = [
       
    ];

    public static $rules = [
    ];

    protected $hidden = [
    ];

    public $fillable = [
        
    ];

    public function getIdAttribute()
    {
        return $this->player_id;
    }

    public function getZhuId()
    {
        return $this->player_id;
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
