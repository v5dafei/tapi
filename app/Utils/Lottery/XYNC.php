<?php
/**
 * 幸运农场辅助类
 * Date: 2019/7/15
 * Time: 21:11
 */

namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class XYNC extends Lottery implements LotteryInterface
{
    const SUM_HE_VALUE = 84;    // 总和和值
    const SUM_UNITS_VALUE = 5;  // 总和尾数大小间隔值

    /**
     * 获取开奖结果
     *
     * @param $nums
     * @param $client
     * @return string
     * @throws ErrMsg
     * @author Michael
     * @time   2019/7/18 13:53
     */
    public function getGameResult ( $nums, $client, $issue='' ) {
        !is_array($nums) && $nums = explode(',', strval($nums));
        if ( 8 !== count($nums) ) {
            return '';
        }

        # 总和
        $data['sum'] = $this->getNumSum($nums);
        # 总和大小
        $data['sumBigOrSmall'] = $this->checkBigSmallHe($data['sum'], self::SUM_HE_VALUE);
        # 总和单双
        $data['sumOddOrEven'] = $this->checkOddEven($data['sum']);
        # 总和尾大小
        $data['sumUnits'] = $this->checkUnitsBigSmall($data['sum'], self::SUM_UNITS_VALUE);
        # 1VS8龙虎
        $data['num1LongHu'] = $this->checkLongHuHe($nums[0], $nums[7]);
        # 2VS7龙虎
        $data['num2LongHu'] = $this->checkLongHuHe($nums[1], $nums[6]);
        # 3VS6龙虎
        $data['num3LongHu'] = $this->checkLongHuHe($nums[2], $nums[5]);
        # 4VS5龙虎
        $data['num4LongHu'] = $this->checkLongHuHe($nums[3], $nums[4]);

        return implode(',', $data);
    }
}