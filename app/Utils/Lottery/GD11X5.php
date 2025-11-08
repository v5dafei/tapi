<?php
/**
 * 广东11选5
 * Date: 2019/7/15
 * Time: 21:03
 */

namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class GD11X5 extends Lottery implements LotteryInterface
{
    const SUM_BIG_SMALL_HE_VALUE = 30;  // 总和大小和值
    const SUM_UNITS_VALUE = 5;          // 总和尾数大小间隔值

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
    public function getGameResult ( $nums, $client, $issue = '' ) {
        !is_array($nums) && $nums = explode(',', strval($nums));
        if ( 5 !== count($nums) ) {
            return '';
        }

        # 总和
        $data['sum'] = $this->getNumSum($nums);
        # 总和大小
        $data['sumBigOrSmall'] = $this->checkBigSmallHe($data['sum'], self::SUM_BIG_SMALL_HE_VALUE);
        # 总和单双
        $data['sumOddOrEven'] = $this->checkOddEven($data['sum']);
        # 总和尾大小
        $data['sumUnits'] = $this->checkUnitsBigSmall($data['sum'], self::SUM_UNITS_VALUE);
        # 龙虎
        $data['longHuHe'] = $this->checkLongHuHe($nums[0], $nums[4]);
        # 第1球大小
        $data['num1BigOrSmall'] = $this->checkNumBigSmallHe($nums[0]);
        # 第2球大小
        $data['num2BigOrSmall'] = $this->checkNumBigSmallHe($nums[1]);
        # 第3球大小
        $data['num3BigOrSmall'] = $this->checkNumBigSmallHe($nums[2]);
        # 第4球大小
        $data['num4BigOrSmall'] = $this->checkNumBigSmallHe($nums[3]);
        # 第5球大小
        $data['num5BigOrSmall'] = $this->checkNumBigSmallHe($nums[4]);

        # 手机端
        if ( in_array($client, [ self::CLIENT_TYPE_APP, self::CLIENT_TYPE_WAP ]) ) {
            return $data['sum'] . ',' . $data['sumBigOrSmall'] . ',' . $data['sumOddOrEven'] . ',' . $data['sumUnits'] . ',' . $data['longHuHe'];
        }

        return implode(',', $data);
    }

    public function checkNumBigSmallHe ( $num ) {
        $num = intval($num);

        if ( 11 === $num ) return '和';
        if ( 6 <= $num ) return '大';

        return '小';
    }

}