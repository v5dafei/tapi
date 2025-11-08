<?php
/**
 * 江苏快3辅助类
 * Date: 2019/7/15
 * Time: 21:06
 */

namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class JSK3 extends Lottery implements LotteryInterface
{
    const SUM_BIG_SMALL_VALUE = 11; // 总和大小区隔值

    /**
     * 获取开奖结果
     *
     * @param        $nums
     * @param        $client
     * @param string $issue
     * @return string
     * @throws ErrMsg
     * @author Michael
     * @time   2019/7/18 19:12
     */
    public function getGameResult ( $nums, $client, $issue = '' ) {

        !is_array($nums) && $nums = explode(',', strval($nums));
        if ( 3 !== count($nums) ) {
            return '';
        }

        # 总和
        $data['sum'] = $this->getNumSum($nums);
        # 总和大小
        $data['sumBigOrSmall'] = $this->checkSumBigSmall($nums, $data['sum'], self::SUM_BIG_SMALL_VALUE);
        # 总和单双
        $data['sumOddOrEven'] = $this->checkOddEven($data['sum']);

        return implode(',', $data);
    }

    public function checkSumBigSmall ( $nums, $value, $flag ) {

        if ( $nums[0] == $nums[1] && $nums[1] == $nums[2] ) return '豹子';

        if ( intval($value) >= intval($flag) ) return '大';

        return '小';
    }
}