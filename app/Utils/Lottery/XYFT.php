<?php
/**
 * 幸运飞艇辅助类
 * Date: 2019/7/15
 * Time: 21:11
 */

namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class XYFT extends Lottery implements LotteryInterface
{
    const SUMGY_BIG_SMALL_VALUE = 12;  // 总和大小区间值

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
        if ( 10 !== count($nums) ) {
            return '';
        }

        # 冠亚军号码
        $gyNums = [$nums[0], $nums[1]];
        # 冠亚军和
        $data['sumGY'] = $this->getNumSum($gyNums);
        # 冠亚军和大小
        $data['sumGYBigOrSmall'] = $this->checkBigSmall($data['sumGY'], self::SUMGY_BIG_SMALL_VALUE);
        # 冠亚军单双
        $data['sumGYOddOrEven'] = $this->checkOddEven($data['sumGY']);
        # 冠军龙虎
        $data['num1LongHu'] = $this->checkLongHuHe($nums[0], $nums[9]);
        # 亚军龙虎
        $data['num2LongHu'] = $this->checkLongHuHe($nums[1], $nums[8]);
        # 第三名龙虎
        $data['num3LongHu'] = $this->checkLongHuHe($nums[2], $nums[7]);
        # 第四名龙虎
        $data['num4LongHu'] = $this->checkLongHuHe($nums[3], $nums[6]);
        # 第五名龙虎
        $data['num5LongHu'] = $this->checkLongHuHe($nums[4], $nums[5]);

        return implode(',', $data);
    }
}