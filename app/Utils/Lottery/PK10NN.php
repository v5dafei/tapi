<?php
/**
 * PK10牛牛辅助类
 * Date: 2019/7/15
 * Time: 21:09
 */
namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class PK10NN extends Lottery implements LotteryInterface
{
    /**
     * 获取开奖结果
     *
     * @param        $nums
     * @param        $client
     * @param string $issue
     * @return string
     * @author Michael
     * @time   2019/8/19 12:43
     */
    public function getGameResult($nums, $client, $issue='') {

        !is_array($nums) && $nums = explode(',', strval($nums));
        if (10 !== count($nums)) {
            return '';
        }

//        foreach ($nums as $key => $num) {
//            $data['num'. ($key+1)] = $num;
//        }
        # 庄家
        $data['dealer'] = $this->checkNiuNiu([$nums[0], $nums[1], $nums[2], $nums[3], $nums[4]]);
        #闲一
        $data['player1'] = $this->checkNiuNiu([$nums[1], $nums[2], $nums[3], $nums[4], $nums[5]]);
        #闲二
        $data['player2'] = $this->checkNiuNiu([$nums[2], $nums[3], $nums[4], $nums[5], $nums[6]]);
        #闲三
        $data['player3'] = $this->checkNiuNiu([$nums[3], $nums[4], $nums[5], $nums[6], $nums[7]]);
        #闲四
        $data['player4'] = $this->checkNiuNiu([$nums[4], $nums[5], $nums[6], $nums[7], $nums[8]]);
        #闲五
        $data['player5'] = $this->checkNiuNiu([$nums[5], $nums[6], $nums[7], $nums[8], $nums[9]]);

        return implode(',', $data);
    }

    public function getWinningPlayers($nums) {
        $nums = array_filter(explode(',', $nums));

        $dealer = [$nums[0], $nums[1], $nums[2], $nums[3], $nums[4]];
        $player1 = [$nums[1], $nums[2], $nums[3], $nums[4], $nums[5]];
        $player2 = [$nums[2], $nums[3], $nums[4], $nums[5], $nums[6]];
        $player3 = [$nums[3], $nums[4], $nums[5], $nums[6], $nums[7]];
        $player4 = [$nums[4], $nums[5], $nums[6], $nums[7], $nums[8]];
        $player5 = [$nums[5], $nums[6], $nums[7], $nums[8], $nums[9]];


        $players = [];
        if (0 === $this->niuNiuPk($dealer, $player1))   $players[] = 1;
        if (0 === $this->niuNiuPk($dealer, $player2))   $players[] = 2;
        if (0 === $this->niuNiuPk($dealer, $player3))   $players[] = 3;
        if (0 === $this->niuNiuPk($dealer, $player4))   $players[] = 4;
        if (0 === $this->niuNiuPk($dealer, $player5))   $players[] = 5;

        return $players;
    }
}