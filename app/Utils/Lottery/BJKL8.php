<?php
/**
 * 北京快乐8
 * Date: 2019/7/15
 * Time: 20:53
 */

namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class BJKL8 extends Lottery implements LotteryInterface
{
    const SUM_HE_VALUE = 810;    // 总和和值

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
        if ( 20 !== count($nums) ) {
            return '';
        }

        # 总和
        $data['sum'] = $this->getNumSum($nums);
        # 总和大小
        $data['sumBigOrSmall'] = $this->checkBigSmallHe($data['sum'], self::SUM_HE_VALUE);
        # 总和单双
        $data['sumOddOrEven'] = $this->checkOddEven($data['sum']);
        # 总和五行
        $data['sumFiveElement'] = $this->checkSumFiveElement($data['sum']);
        # 前后和
        $data['frontBehindSum'] = $this->checkFrontBehindSum($nums);
        # 单双和
        $data['oddEvenSum'] = $this->checkOddEvenSum($nums);

        return implode(',', $data);
    }

    /**
     * 判断总和五行
     *
     * @param $value
     * @return string
     * @author Michael
     * @time   2019/7/18 15:42
     */
    public function checkSumFiveElement ( $value ) {
        $value = intval($value);
        if ( 210 <= $value && 695 >= $value ) return '金';
        if ( 696 <= $value && 763 >= $value ) return '木';
        if ( 764 <= $value && 855 >= $value ) return '水';
        if ( 856 <= $value && 923 >= $value ) return '火';
        if ( 924 <= $value && 1410 >= $value ) return '土';

        return '未知';
    }

    /**
     * 判断前后和
     *
     * @param $nums
     * @return string
     * @author Michael
     * @time   2019/7/18 15:49
     */
    public function checkFrontBehindSum ( $nums ) {
        $frontCount  = 0;
        $behindCount = 0;
        foreach ( $nums as $value ) {
            $value = intval($value);
            $value <= 40 && $frontCount++;
            $value >= 41 && $behindCount++;
        }

        if ( $frontCount === $behindCount ) return '前后(和)';
        if ( $frontCount > $behindCount ) return '前(多)';
        if ( $frontCount < $behindCount ) return '后(多)';
    }

    /**
     * 判断单双和
     *
     * @param $nums
     * @return string
     * @author Michael
     * @time   2019/7/18 15:52
     */
    public function checkOddEvenSum ( $nums ) {
        $oddCount  = 0;
        $evenCount = 0;
        foreach ( $nums as $value ) {
            $value = intval($value);
            if ( 1 === $value % 2 ) {
                $oddCount++;
            } else {
                $evenCount++;
            }
        }

        if ( $oddCount === $evenCount ) return '单双(和)';
        if ( $oddCount > $evenCount ) return '单(多)';
        if ( $oddCount < $evenCount ) return '双(多)';
    }
}