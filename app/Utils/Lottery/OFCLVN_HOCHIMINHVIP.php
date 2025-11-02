<?php
/**
 * 胡志明VIP辅助类
 * Date: 2020/3/20
 * Time: 18:30
 */

namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class OFCLVN_HOCHIMINHVIP extends Lottery implements LotteryInterface
{
    /**
     * 获取开奖结果
     *
     * @param $nums
     * @param $client
     * @return string
     * @throws ErrMsg
     * @author sphitx
     * @time   2020/07/24 14:52
     */
    public function getGameResult ( $nums, $client, $issue='' ) {
            return '';
    }
}