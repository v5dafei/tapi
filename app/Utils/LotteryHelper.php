<?php
/**
 * Created by PhpStorm.
 * Author: benjamin
 * Date: 2021/4/26/0026
 * Time: 20:57
 */

namespace App\Utils;


use App\Models\Bet\Lottery;
use App\Services\Lottery\LotteryService;

class LotteryHelper
{

    static function totalBetMoney ( $alias = '' ) {
        $alias = !empty($alias) ? trim($alias) . '.' : '';

        # 早期处理
//    $where = "{$alias}money*{$alias}totalNums";

        # 越南彩计算修复
//    $where = "CASE WHEN {$alias}betInfo REGEXP '[|;]' THEN {$alias}totalMoney ELSE {$alias}money*{$alias}totalNums END";

        # 最终处理
        $where = "{$alias}money*{$alias}total_nums*{$alias}multiple";

        return $where;
    }

    //生成随机开奖号码
    static function create_kj_number ( $lott_id ) {
        /* 大乐透 */
        if ( $lott_id == 12 ) {
            $kj = $kj2 = $qian = $hou = array();
            for ( $i = 0; $i <= 34; $i++ ) {
                $qian[] = $i;
            }
            for ( $i = 0; $i <= 11; $i++ ) {
                $hou[] = $i;
            }
            $qianAry = array_rand($qian, 5);
            $houAry  = array_rand($hou, 2);
            sort($qianAry);
            sort($houAry);
            $kj = array_merge($qianAry, $houAry);
            foreach ( $kj as $k => $v ) {
                $kj2[] = sprintf('%02d', $v + 1);
            }
            return $kj2;
        }

        /* 七星彩 */
        if ( $lott_id == 2 ) {
            $kj = $kj2 = $qian = $hou = array();
            for ( $i = 0; $i <= 9; $i++ ) {
                $qian[] = $i;
            }
            for ( $i = 0; $i <= 14; $i++ ) {
                $hou[] = $i;
            }
            $qianAry = array_rand($qian, 6);
            $houAry  = (array)array_rand($hou, 1);
            shuffle($qianAry);
            $kj = array_merge($qianAry, $houAry);
            return $kj;
        }

        /* 胡志明VIP */
        if ( $lott_id == 30 ) {
            $kj = self::create_hzm_number();
            return $kj;
        }
        /* 河内VIP */
        if ( $lott_id == 31 ) {
            $kj = self::create_hn_number();
            return $kj;
        }

//        /* 其他彩种 */
//        require $GLOBALS['conf']['web_path'] . '/config/config.limit.php';
//        $dataRule = $kj_number_limit[$lott_id];
        $dataRule = LotteryService::getOpenDataRule($lott_id);
        //将最小、最大之间组成数组，注意键、值必须相同
        $num_ary = array();
        for ( $i = $dataRule['min']; $i <= $dataRule['max']; $i++ ) {
            $num_ary[$i] = $i;
        }

        //生成号码
        $kj1 = array();
        if ( !empty($dataRule['repeat']) ) {
            //每个号码分别随机，即各个球可以重复，注意不能用键值否则会造成重复键值
            for ( $i = 0; $i < $dataRule['numbers']; $i++ ) {
                $rand  = array_rand($num_ary, 1);
                $kj1[] = $rand;
            }
        } else {
            //随机取出多个号码
            $kj1 = array_rand($num_ary, $dataRule['numbers']);
            if ( $dataRule['numbers'] == 1 ) $kj1 = array( $kj1 );
        }

        //号码补零
        $kj2 = array();
        if ( count($kj1) > 0 ) {
            foreach ( $kj1 as $val2 ) {
                if ( $dataRule['add0'] > 0 ) {
                    $kj2[] = sprintf('%0' . $dataRule['add0'] . 'd', $val2);
                } else {
                    $kj2[] = $val2;
                }
            }
        }
        //排序
        if ( !empty($dataRule['asort']) ) {
            sort($kj2);
        } else {
            shuffle($kj2);
        }

        //注意返回的是数组，不是开奖号码
        return $kj2;
    }

    //生成胡志明VIP开奖号码
    static function create_hzm_number () {
        $data    = array();
        $data[0] = self::create_rand_number(6);
        $data[1] = self::create_rand_number(5);
        $data[2] = self::create_rand_number(5);
        $data[3] = self::create_rand_number(5) . ',' . self::create_rand_number(5);
        $data[4] = self::create_rand_number(5) . ',' . self::create_rand_number(5) . ',' . self::create_rand_number(5) . ',' . self::create_rand_number(5) . ',' . self::create_rand_number(5) . ',' . self::create_rand_number(5) . ',' . self::create_rand_number(5);
        $data[5] = self::create_rand_number(4);
        $data[6] = self::create_rand_number(4) . ',' . self::create_rand_number(4) . ',' . self::create_rand_number(4);
        $data[7] = self::create_rand_number(3);
        $data[8] = self::create_rand_number(2);
        return $data;
    }

    //生成河内VIP开奖号码
    static function create_hn_number () {
        $data    = array();
        $data[0] = self::create_rand_number(5);
        $data[1] = self::create_rand_number(5);
        $data[2] = self::create_rand_number(5) . ',' . self::create_rand_number(5);
        $data[3] = self::create_rand_number(5) . ',' . self::create_rand_number(5) . ',' . self::create_rand_number(5) . ',' . self::create_rand_number(5) . ',' . self::create_rand_number(5) . ',' . self::create_rand_number(5);
        $data[4] = self::create_rand_number(4) . ',' . self::create_rand_number(4) . ',' . self::create_rand_number(4) . ',' . self::create_rand_number(4);
        $data[5] = self::create_rand_number(4) . ',' . self::create_rand_number(4) . ',' . self::create_rand_number(4) . ',' . self::create_rand_number(4) . ',' . self::create_rand_number(4) . ',' . self::create_rand_number(4);
        $data[6] = self::create_rand_number(3) . ',' . self::create_rand_number(3) . ',' . self::create_rand_number(3);
        $data[7] = self::create_rand_number(2) . ',' . self::create_rand_number(2) . ',' . self::create_rand_number(2) . ',' . self::create_rand_number(2);
        return $data;
    }

    //生成指定长度开奖号码
    static function create_rand_number ( $length ) {
        $num_ary = array();
        for ( $i = 0; $i <= 9; $i++ ) {
            $num_ary[] = $i;
        }
        $kj1 = array();
        for ( $i = 0; $i < $length; $i++ ) {
            $rand  = array_rand($num_ary, 1);
            $kj1[] = $rand;
        }
        $kj2 = array();
        if ( count($kj1) > 0 ) {
            foreach ( $kj1 as $val2 ) {
                $kj2[] = $val2;
            }
        }
        shuffle($kj2);
        $kj3 = '';
        foreach ( $kj2 as $v ) {
            $kj3 .= $v;
        }
        return $kj3;
    }

    //难度转百分比
    static function hard2rate ( $hard_level ) {
        return (($hard_level - 5) * 2) . '%';
    }

    /**
     * @title  计算中奖金额（单注）
     * @param int   $winNum  中奖次数
     * @param array $betData 投注数据
     * @return float|int
     * @author benjamin
     */
    public static function getWinMoney ( $winNum, $betData, $overwriteOdds = false ) {
        $winMoney = 0;
        $betOdds  = $overwriteOdds !== false ? $overwriteOdds : $betData['odds'];

        # 和局
        if ( $winNum == '-1' ) {
            if ( $betData['money'] * $betData['total_nums'] > 0 ) {
                $winMoney = $betData['money'] * $betData['total_nums'];
            }

        } # 三军
        elseif ( $betData['group_name'] == '三军' ) {
            $winMoney = $betData['money'] * $betOdds + $betData['money'] * ($winNum - 1);
        } # 六合彩正肖
        elseif ( $betData['group_name'] == '正肖' && $winNum > 1 ) {
            # 投注金额 X 赔率 + 投注金额 X (赔率 - 1) X (中奖生肖个数 - 1)
            $winMoney = $betData['money'] * $betOdds + $betData['money'] * ($betOdds - 1) * ($winNum - 1);
        } # 其他
        else {
            // 越南彩票
            $row      = Lottery::findByPk($betData['lott_id'], 'lott_id,from_lott');
            $fromType = isset($row['from_lott']) && $row['from_lott'] ? $row['from_lott'] : $betData['lott_id'];
            if ( in_array($fromType, [ 30, 31 ]) ) {
                $winMoney = self::getWinAmountYNCP($fromType, $betData, $winNum);
            } else {
                $winMoney = $winNum * $betData['money'] * $betOdds;
            }
            if ( $fromType == 32 ) {
                $winMoney = $winMoney > 50000 ? 50000 : $winMoney;
            }
        }

        return $winMoney;
    }

    public static function getWinAmountYNCP ( $fromType, $bet, $zjCount ) {
        $times = 1;

        // 南方彩票
        if ( 30 == $fromType ) {
            if ( $bet['bet_data'] == '批号2' ) {
                $times = 18;
            }
            if ( $bet['bet_data'] == '批号3' ) {
                $times = 17;
            }
            if ( $bet['bet_data'] == '批号4' ) {
                $times = 16;
            }
            if ( $bet['bet_data'] == '标题尾巴' ) {
                $times = 2;
            }
            if ( $bet['bet_data'] == '3尾巴的尽头' ) {
                $times = 2;
            }
        }

        // 北方彩票
        if ( 31 == $fromType ) {
            if ( $bet['bet_data'] == '批号2' ) {
                $times = 27;
            }
            if ( $bet['bet_data'] == 'Lot2第一个号码' ) {
                $times = 23;
            }
            if ( $bet['bet_data'] == '批号3' ) {
                $times = 23;
            }
            if ( $bet['bet_data'] == '批号4' ) {
                $times = 20;
            }
            if ( $bet['bet_data'] == '主张7' ) {
                $times = 4;
            }
        }


        $money    = $bet['money'];
        $multiple = (int)($bet['total_money'] / $money / $bet['total_nums']);

        return $zjCount * ($money / $times) * $bet['odds'] * $multiple;
    }
}