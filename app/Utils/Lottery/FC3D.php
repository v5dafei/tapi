<?php
/**
 * 福彩3D辅助类
 * Date: 2019/7/15
 * Time: 21:02
 */

namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class FC3D extends Lottery implements LotteryInterface
{
    const SUM_BIG_SMALL_VALUE = 14; // 总和大小区隔值

    /**
     * 获取开奖结果
     *
     * @param        $nums
     * @param        $client
     * @param string $issue
     * @return string
     * @author Michael
     * @time   2019/8/19 13:19
     */
    public function getGameResult ( $nums, $client, $issue = '' ) {

        !is_array($nums) && $nums = explode(',', strval($nums));
        if ( 3 !== count($nums) ) {
            return '';
        }

        # 跨度
        $data['span'] = $this->checkSpan($nums);
        # 3连
        $data['threeNums'] = $this->checkThreeNums([ $nums[0], $nums[1], $nums[2] ]);
        # 总和
        $data['sum'] = $this->getNumSum($nums);
        # 总和大小
        $data['sumBigOrSmall'] = $this->checkBigSmall($data['sum'], self::SUM_BIG_SMALL_VALUE);
        # 总和单双
        $data['sumOddOrEven'] = $this->checkOddEven($data['sum']);
        # 龙虎和
        $data['longHuHe'] = $this->checkLongHuHe($nums[0], $nums[2]);

        # 手机端
        if ( in_array($client, [ self::CLIENT_TYPE_APP, self::CLIENT_TYPE_WAP ]) ) {
            return $data['sum'] . ',' . $data['sumBigOrSmall'] . ',' . $data['sumOddOrEven'] . ',' . $data['longHuHe'];
        }

        return implode(',', $data);
    }
}