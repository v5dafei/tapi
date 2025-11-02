<?php

/**
 * UG颜色彩
 */

namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class UGYSC extends Lottery implements LotteryInterface
{

    /**
     * 获取开奖结果
     */
    public function getGameResult ( $nums, $client, $issue = '' ) {
        if ( in_array($nums, ["1", "3", "7", "9"]) ) {
            return "cyan";
        } elseif ( in_array($nums, ["5"]) ) {
            return "cyanpurple";
        } elseif ( in_array($nums, ["2", "4", "6", "8"]) ) {
            return "red";
        } elseif ( in_array($nums, ["0"]) ) {
            return "redpurple";
        }
        return '';
    }
}