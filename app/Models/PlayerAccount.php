<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PlayerAccount extends BaseModel
{
    public $table = 'inf_player_account';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'player_id';

    const COLUMNS = [
        'auth' => '*',
        'info' => "*",
    ];

    public static $rules = [
    ];

    public $fillable = [
        'player_id',
        'carrier_id',
        'top_id',
        'parent_id',
        'rid',
        'balance',
        'frozen',
        'is_tester',
        'level',
        'user_name'
    ];

    protected $casts = [

    ];


    /**
     * 读写锁Redis实现
     * @param int    $player_id
     * @param string $action
     * @return mixed
     * @throws \Exception
     */
    static function rwLock ( int $player_id, $action = 'set' ) {
        $cacheKey = "player_" . $player_id;

        if ( $action === 'set' ) {
            if ( !cache()->add($cacheKey, 1, now()->addMinutes(1)) ) {
                return returnApiJson(config('language')[self::getMerInfo('language')]['error20'], 0);
            }
        }

        if ( $action === 'del' ) {
            cache()->forget($cacheKey);
        }
    }

    static function getUserById ( $id, $condition = [] ) {
        # 查询后再处理的条件
//        $other = [ 'status' => 1 ];
        $other = [];

//        if ( !empty($condition['data']) ) {
//            $condition['data']['uid'] = $id;
//        }

        # 合并并组装条件
        $conditions = [
            'data' => [ 'player_id' => $id ], 'columns' => self::COLUMNS['info'],
//            'rwLock'  => true,  // 读写锁：使用锁不要用从库，不支持跨库事务
        ];

        $conditions = self::getConditions(array_merge($conditions, $condition));

        # 用户获取及过滤
        $user = self::findOneBySql($conditions['sql'], $conditions['bind']);
        if ( !empty($other) && !empty($user) ) {
            foreach ( $other as $field => $val ) {
                if ( isset($user[$field]) && $val[$field] != $user[$field] ) return null;
            }
        }

        return $user;
    }

    public static function getUserList ($condition) {
        return self::getDataList($condition);
    }
    
    /**
     * @title  检查余额是否足够
     * @param $balance
     * @param $orderAmount
     * @return bool
     * @author benjamin
     */
    public static function checkMoneyEnough ( $balance, $orderAmount ) {
        $balance     = (string)$balance;
        $orderAmount = (string)$orderAmount;

        # 余额是否大于0
        if ( bccomp($balance, '0') !== 1 ) {
            return false;
        }

        # 是否大于等于订单金额
        if ( bccomp($balance, $orderAmount) === -1 ) {
            return false;
        }

        return true;
    }
}
