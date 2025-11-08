<?php
/**
 * 聊天室红包相关常量声明
 */

namespace App\Utils\Enum\Chat;

use App\Utils\Enum\RedisKeyEnum;

class ChatEnum
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
    const BOOT_BET_SHARE_QUEUE = 'bot_bet_share_queue';


    /**
     * 缓存KEY生成
     * @param string $key
     * @param mixed  $unique
     * @return string
     * @throws \Core\Exception\ErrMsg
     */
    public static function genRedisKey ( $key, $unique = '' ) {
        return RedisKeyEnum::gen(RedisKeyEnum::KEY_CHAT_SETTINGS, $key . $unique);
    }

    public static function getBotBetShareQueueKey() {
        return self::genRedisKey(self::BOOT_BET_SHARE_QUEUE);
    }


}