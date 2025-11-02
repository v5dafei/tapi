<?php
/**
 * PC蛋蛋辅助类
 * Date: 2019/7/15
 * Time: 21:06
 */

namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class PCDD extends Lottery implements LotteryInterface
{
    const SUM_BIG_SMALL_VALUE = 14;    // 总和大小间隔值

    # 极大
    static $maxSumMaps = [ 23, 24, 25, 26, 27 ];
    # 极小
    static $minSumMaps = [ 0, 1, 2, 3, 4 ];
    # 红波
    static $redBalls = [ 3, 6, 9, 12, 15, 18, 21, 24 ];
    # 绿波
    static $greenBalls = [ 1, 4, 07, 10, 16, 19, 22, 25 ];
    # 蓝波
    static $blueBalls = [ 2, 5, 8, 11, 17, 20, 23, 26 ];

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
        if ( 3 !== count($nums) ) {
            return '';
        }

        # 总和
        $data['sum'] = $this->getNumSum($nums);
        # 总和大小
        $data['sumBigOrSmall'] = $this->checkBigSmall($data['sum'], self::SUM_BIG_SMALL_VALUE);
        # 总和单双
        $data['sumOddOrEven'] = $this->checkOddEven($data['sum']);
        # 总和极值
        $data['sumMinOrMax'] = $this->checkMinMax($data['sum']);
        # 色波
        $data['sumColor'] = $this->checkColor($data['sum']);

        # 手机端
        if ( in_array($client, [ self::CLIENT_TYPE_APP, self::CLIENT_TYPE_WAP ]) ) {
            return $data['sum'] . ',' . $data['sumBigOrSmall'] . ',' . $data['sumOddOrEven'];
        }

        return implode(',', $data);
    }

    /**
     * 判断极大极小
     *
     * @param $sum
     * @return string
     * @author Michael
     * @time   2019/7/18 16:05
     */
    public function checkMinMax ( $sum ) {
        if ( in_array($sum, self::$maxSumMaps) ) return '极大';
        if ( in_array($sum, self::$minSumMaps) ) return '极小';

        return '--';
    }

    /**
     * 检查色波
     *
     * @param $sum
     * @return string
     * @author Michael
     * @time   2019/7/18 16:07
     */
    public function checkColor ( $sum ) {
        if ( in_array($sum, self::$redBalls) ) return '红波';
        if ( in_array($sum, self::$greenBalls) ) return '绿波';
        if ( in_array($sum, self::$blueBalls) ) return '蓝波';

        return '--';
    }
}