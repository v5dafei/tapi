<?php
/**
 * 聊天室红包相关常量声明
 */

namespace App\Utils\Enum\Chat;

use App\Utils\Enum\RedisKeyEnum;

class RedBagEnum
{

    # 任务处理锁
    const TIMER_TASK_LOCK = 'timer_task_lock';
    # 待处理定时红包队列
    const TIMER_TASK_QUEUE = 'timer_task_queue';
    # 已处理循环红包缓存
    const TIMER_CYCLE_QUEUE = 'timer_cycle_queue';
    # 已处理延迟红包缓存
    const TIMER_DELAY_QUEUE = 'timer_delay_queue';


    # 待抢红包队列
    const BOOT_GRAB_RED_BAG_QUEUE = 'bot_grab_queue'; // 'RedBag_bot_grab_queue'


    /**
     * 缓存KEY生成
     * @param string $key
     * @param mixed  $unique
     * @return string
     * @throws \Core\Exception\ErrMsg
     */
    public static function genRedisKey ( $key, $unique = '' ) {
        return RedisKeyEnum::gen(RedisKeyEnum::KEY_RED_BAG, $key . $unique);
    }

    public static function getBotGrabRadBagQueueKey() {
        return self::genRedisKey(self::BOOT_GRAB_RED_BAG_QUEUE);
    }

    /**
     * 处理机器人待抢红包队列
     */
    public static function handlerBotGrabRadBagQueue($action = 'set', $data = []) {
        $res = null;

        switch ($action) {
            case 'set':
                $res = '';
                break;
            case 'get':
                $res = '';
                break;
            case 'lpop':
                $res = '';
                break;
            case 'rpush':
                $res = '';
                break;
            default:
                break;
        }
        return $res;
    }
}