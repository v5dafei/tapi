<?php

namespace App\Utils\Enum;

use App\Exceptions\ErrMsg;

/**
 * REDIS缓存键统一管理，所有键必须在这里声明常量
 */
class RedisKeyEnum
{
    const PREFIX      = 'Api';
    const MODEL_ONE   = 'ModelOne';
    const MODEL_LIST  = 'ModelArr';
    const NORMAL_ONE  = 'NormalStr';
    const NORMAL_LIST = 'NormalArr';

    /**
     * 特定的键或前缀
     */
    # 接口请求限制前缀
    const KEY_REQUEST_LIMIT = 'RequestLimit';
    # 系统全局设置
    const KEY_SYSTEM_SETTINGS = 'SystemAppSettings';
    # 聊天室相关设置
    const KEY_CHAT_SETTINGS = 'ChatAppSettings';
    # 客户端IP相关信息
    const KEY_IP_INFO = 'IpInfo';
    # 长龙列表数据缓存
    const KEY_GAME_CHANGLONG = 'ChangLongList';
    # 长龙当前数据缓存
    const KEY_GAME_CHANGLONG_STAT = 'ChangLongStat';
    # 红包相关
    const KEY_RED_BAG = 'RedBag';
    # 计划任务限制
    const KEY_CRON_TASK_LIMIT = 'CronTaskLimit';

    /**
     * 通用 REDIS-KEY 生成
     *
     * @param string $key    键或前缀
     * @param null   $unique 额外唯一标识
     * @param bool   $isHot  是否热点KEY（会被切割分散到不同服务节点）
     * @throws ErrMsg
     * @return string
     * @author benjamin
     */
    public static function gen ( $key, $unique = null, $isHot = false ) {
        self::checkKey($key);
        $hotKey = $isHot ? 'Hot' : '';
        $unique = $unique ? '_' . $unique : '';
        return self::PREFIX . '_' . $hotKey . $key . $unique;
    }

    # 模型数据
    public static function genModelKeyByOne ( $unique, $key = self::MODEL_ONE ) {
        return self::gen($key, $unique);
    }

    public static function genModelKeyByList ( $unique, $key = self::MODEL_LIST ) {
        return self::gen($key, $unique);
    }

    # 普通数据
    public static function genNormalKeyByStr ( $unique, $key = self::NORMAL_ONE ) {
        return self::gen($key, $unique);
    }

    public static function genNormalKeyByList ( $unique, $key = self::NORMAL_LIST ) {
        return self::gen($key, $unique);
    }

    /**
     * @title  对设置KEY 键/前缀或类型进行检查
     * @author benjamin
     */
    private static function checkKey ( $type ) {
        $class      = new self();
        $Reflection = new \ReflectionClass($class);

        $constants = $Reflection->getConstants();
        if ( !empty($constants) ) {
//            $constants = array_flip($constants);
            if ( array_search($type, $constants) === false ) {
                throw new ErrMsg('请正常配置REDIS：键名/前缀!');
            }
        }
    }

}