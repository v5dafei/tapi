<?php
/**
 * 七星彩辅助类
 * Date: 2019/7/15
 * Time: 21:10
 */
namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class QXC extends Lottery implements LotteryInterface
{
    const SUM_BIG_SMALL_VALUE = 34; // 总和大小区隔值

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
    public function getGameResult($nums, $client, $issue='') {
        !is_array($nums) && $nums = explode(',', strval($nums));
        if (7 !== count($nums)) {
            return '';
        }

        $data['sum'] = $this->getNumSum($nums);
        $data['sumBigOrSmall'] = $this->checkBigSmall($data['sum'], self::SUM_BIG_SMALL_VALUE);
        $data['sumOddOrEven'] = $this->checkOddEven($data['sum']);
        $data['sumLongHuHe'] = $this->checkLongHuHe($nums[0], $nums[5]);

        return implode(',', $data);
    }
}