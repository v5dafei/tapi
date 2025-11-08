<?php
/**
 * 重庆时时彩辅助类
 * Date: 2019/7/15
 * Time: 20:54
 */

namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class CQSSC extends Lottery implements LotteryInterface
{
    const SUM_BIG_SMALL_VALUE = 23; // 总和大小区隔值

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
        if ( 5 !== count($nums) ) {
            return '';
        }

        $data['sum']           = $this->getNumSum($nums);
        $data['sumBigOrSmall'] = $this->checkBigSmall($data['sum'], self::SUM_BIG_SMALL_VALUE);
        $data['sumOddOrEven']  = $this->checkOddEven($data['sum']);
        $data['longHuHe']      = $this->checkLongHuHe($nums[0], $nums[4]);
        $data['frontThree']    = $this->checkThreeNums([ $nums[0], $nums[1], $nums[2] ]);
        $data['middleThree']   = $this->checkThreeNums([ $nums[1], $nums[2], $nums[3] ]);
        $data['behindThree']   = $this->checkThreeNums([ $nums[2], $nums[3], $nums[4] ]);
        $data['bullfight']     = $this->checkNiuNiu($nums);
        $data['allIn']         = $this->checkFiveNums($nums);

        return implode(',', $data);
    }
}