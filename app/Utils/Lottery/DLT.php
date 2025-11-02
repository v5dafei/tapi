<?php
/**
 * 大乐透辅助类
 * Date: 2020/3/20
 * Time: 18:30
 */

namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class DLT extends Lottery implements LotteryInterface
{
    const TEMA_BIG_SMALL_VALUE = 7;  // 特码大小区间值
    const SUMHQ_BIG_SMALL_VALUE = 13; // 后区大小区间值
    const SUMHQ_ODD_EVEN_HE_VALUE = 23; //后区单双和值

    /**
     * 获取开奖结果
     *
     * @param $nums
     * @param $client
     * @return string
     * @throws ErrMsg
     * @author Matt
     * @time   2020/3/20 18:30
     */
    public function getGameResult ( $nums, $client, $issue='' ) {
        !is_array($nums) && $nums = explode(',', strval($nums));
        if ( 7 !== count($nums) ) {
            return '';
        }

        # 特码大小
        $data['temaBigOrSmall'] = $this->checkBigSmall($nums[6], self::TEMA_BIG_SMALL_VALUE);
        # 特码单双
        $data['temaOddOrEven'] = $this->checkOddEven($nums[6]);
        # 后区和大小
        $hqNums = [$nums[5], $nums[6]];
        $sumHQ = $this->getNumSum($hqNums);
        $data['sumHQBigOrSmall'] = $this->checkHQBigSmallHe($sumHQ, self::SUMHQ_BIG_SMALL_VALUE);
        # 后区和单双
        $data['sumHQOddOrEven'] = $this->checkOddEvenHe($sumHQ, self::SUMHQ_ODD_EVEN_HE_VALUE);

        return implode(',', $data);
    }

    /**
     * 后区大小和
     *
     * @param $value
     * @param $flag
     * @return string
     * @author Matt
     * @time   2020/3/20 18:30
     */
    public function checkHQBigSmallHe ( $value, $flag) {
        $value = intval($value);
        if ( 23 === $value ) return '和';
        if ( $value >= $flag ) return '大';
        return '小';
    }
}