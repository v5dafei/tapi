<?php
/**
 * 彩票公共类
 */

namespace App\Utils\Lottery;


class Lottery
{

//    const CLIENT_TYPE_PC  = 'pc';
//    const CLIENT_TYPE_WAP = 'wap';
//    const CLIENT_TYPE_APP = 'app';

    /**
     *  获取总和
     *
     * @param $nums
     * @return int
     * @author Michael
     * @time   2019/7/17 16:05
     */
    public function getNumSum ( $nums ) {
        return array_sum($nums);
    }

    /**
     * 判断大小
     *
     * @param $value
     * @param $flag
     * @return string
     * @author Michael
     * @time   2019/7/17 19:17
     */
    public function checkBigSmall ( $value, $flag ) {
        if ( intval($value) >= intval($flag) ) return '大';

        return '小';
    }

    /**
     * 大小和
     *
     * @param $value
     * @param $heValue
     * @return string
     * @author Michael
     * @time   2019/7/18 14:41
     */
    public function checkBigSmallHe ( $value, $heValue ) {
        $value = intval($value);
        if ( $value === $heValue ) return '和';
        if ( $value > $heValue ) return '大';
        return '小';
    }

    /**
     * 判断尾数大小
     *
     * @param $value
     * @param $flag
     * @return string
     * @author Michael
     * @time   2019/7/18 14:53
     */
    public function checkUnitsBigSmall ( $value, $flag ) {
        $array = str_split($value);
        $units = $array[count($array) - 1];
        if ( $units >= $flag ) return '尾大';
        return '尾小';
    }

    /**
     * 判断单双
     *
     * @param $value
     * @return string
     * @author Michael
     * @time   2019/7/17 19:24
     */
    public function checkOddEven ( $value ) {
        if ( 1 === intval($value) % 2 ) return '单';

        return '双';
    }

    /**
     * 判断单双和
     *
     * @param $value
     * @param $heValue
     * @return string
     * @author Michael
     * @time   2019/7/18 14:45
     */
    public function checkOddEvenHe ( $value, $heValue ) {
        $value = intval($value);

        if ( $value === $heValue ) return '和';
        if ( 1 === $value % 2 ) return '单';

        return '双';
    }

    /**
     * 判断龙虎和
     *
     * @param $value1
     * @param $value2
     * @return string
     * @author Michael
     * @time   2019/7/17 19:27
     */
    public function checkLongHuHe ( $value1, $value2 ) {
        if ( intval($value1) === intval($value2) ) return '和';
        if ( intval($value1) > intval($value2) ) return '龙';
        return '虎';
    }

    /**
     * 判断三个号的组合类型
     *
     * @param $nums
     * @return string
     * @author Michael
     * @time   2019/7/17 21:26
     */
    public function checkThreeNums ( $nums ) {
        if ( $this->checkSameNum($nums) ) {
            return '豹子';
        } else if ( $this->checkStraight($nums) ) {
            return '顺子';
        } else if ( 1 === $this->checkPair($nums) ) {
            return '对子';
        } else if ( $this->checkHalfStraight($nums) ) {
            return '半顺';
        } else {
            return '杂六';
        }
    }

    /**
     * 判断五个号的组合类型
     *
     * @param $nums
     * @return string
     * @author Michael
     * @time   2019/7/17 21:30
     */
    public function checkFiveNums ( $nums ) {
        # 顺子
        $straights = [ '01234', '12345', '23456', '34567', '45678', '56789', '67890', '78901', '89012', '90123' ];
        if ( in_array(implode('', $nums), $straights) ) return '顺子';

        sort($nums);

        $array = array_unique($nums);

        $numStr = implode('', $nums);

        if ( 1 === count($array) ) return '五条';
        if ( 2 === count($array) && 0 === $this->checkPair($nums) ) return '四条';
        if ( $this->checkFullHouse($nums) ) return '葫芦';
        if ( $this->checkSet($nums) ) return '三条';
        if ( 2 === $this->checkPair($nums) ) return '两对';
        if ( 1 === $this->checkPair($nums) ) return '一对';

        return '散号';
    }

    /**
     * 判断牛牛
     *
     * @param $nums
     * @return string
     * @author Michael
     * @time   2019/7/17 21:30
     */
    public function checkNiuNiu ( $nums ) {

        # 判断是否有牛
        $haveNiu = $this->checkHaveNiu($nums);

        #  计算牛的数字
        $niuValue = array_sum($nums) % 10;

        if ( !$haveNiu ) return '没牛';
        if ( 0 === $niuValue ) return '牛牛';
        if ( 1 === $niuValue ) return '牛1';
        if ( 2 === $niuValue ) return '牛2';
        if ( 3 === $niuValue ) return '牛3';
        if ( 4 === $niuValue ) return '牛4';
        if ( 5 === $niuValue ) return '牛5';
        if ( 6 === $niuValue ) return '牛6';
        if ( 7 === $niuValue ) return '牛7';
        if ( 8 === $niuValue ) return '牛8';
        if ( 9 === $niuValue ) return '牛9';
    }

    /**
     * 判断牛牛大小
     *
     * @param $nums
     * @return string
     * @author Michael
     * @time   2019/8/19 12:45
     */
    public function checkNiuNiuBigSmall ( $nums ) {

        # 获取牛牛值
        $niuValue = $this->getNiuValue($nums);

        if ( 6 <= $niuValue ) return '牛大';
        if ( 6 > $niuValue ) return '牛小';
    }

    public function checkNiuNiuOddEven ( $nums ) {
        # 获取牛牛值
        $niuValue = $this->getNiuValue($nums);

        if ( 0 === $niuValue % 2 ) return '牛双';
        if ( 1 === $niuValue % 2 ) return '牛单';
    }

    public function niuNiuPk ( $dealer, $player ) {
        # 获取牛牛值
        $dealerNiuValue = $this->getNiuValue($dealer);
        $playerNiuValue = $this->getNiuValue($player);

        # 谁牛大谁赢
        if ( $dealerNiuValue > $playerNiuValue ) return 1;
        if ( $dealerNiuValue < $playerNiuValue ) return 0;
        # 牛相等时，牛六以下（含牛六）庄家赢
        if ( $playerNiuValue <= 6 ) return 1;
        # 牛相等时，牛六以上（不含牛六）判断第一张牌大小
        if ( $dealer[0] > $player[0] ) return 1;
        if ( $dealer[0] < $player[0] ) return 0;
        return 1;
    }

    /**
     * 获取牛牛值
     *
     * @param $nums
     * @return int
     * @author Michael
     * @time   2019/7/21 19:18
     */
    public function getNiuValue ( $nums ) {
        # 判断是否有牛
        $haveNiu = $this->checkHaveNiu($nums);
        if ( !$haveNiu ) return 0;

        #  计算牛的数字
        $value = array_sum($nums) % 10;
        if ( 0 === $value ) return 10;

        return $value;
    }


    /**
     * 判断是否有牛
     *
     * @param $nums
     * @return bool
     * @author Michael
     * @time   2019/7/17 21:47
     */
    public function checkHaveNiu ( $nums ) {
        
        $index = [ [ 0, 1, 2 ], [ 0, 1, 3 ], [ 0, 1, 4 ], [ 0, 2, 3 ], [ 0, 2, 4 ], [ 0, 3, 4 ], [ 1, 2, 3 ], [ 1, 2, 4 ], [ 1, 3, 4 ], [ 2, 3, 4 ] ];
        foreach ( $index as $value ) {
            $tmp = [
                $nums[$value[0]],
                $nums[$value[1]],
                $nums[$value[2]]
            ];
            if ( 0 === array_sum($tmp) % 10 ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断所有号码相同的情况
     *
     * @param $nums
     * @return bool
     * @author Michael
     * @time   2019/7/17 20:16
     */
    public function checkSameNum ( $nums ) {
        $tmp = $nums[0];
        foreach ( $nums as $value ) {
            if ( $tmp != $value ) {
                return false;
            }
        }

        return true;
    }

    /**
     * 判断顺子
     *
     * @param $nums
     * @return bool
     * @author Michael
     * @time   2019/7/17 20:32
     */
    public function checkStraight ( $nums ) {
        sort($nums);
        if ( $nums[0] == 0 && $nums[1] == 1 && $nums[2] == 9 ) {
            return true;
        }
        if ( $nums[0] == 0 && $nums[1] == 8 && $nums[2] == 9 ) {
            return true;
        }
        foreach ( $nums as $key => $value ) {
            if ( $key > count($nums) - 2 ) continue;
            if ( -1 !== $value - $nums[$key + 1] ) {
                return false;
            }
        }

        return true;
    }

    /**
     * 判断有几个一对
     *
     * @param $nums
     * @return bool
     * @author Michael
     * @time   2019/7/17 20:17
     */
    public function checkPair ( $nums ) {
        $nums  = array_count_values($nums);
        $count = 0;
        foreach ( $nums as $value ) {
            if ( 2 === $value ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 检查半顺
     *
     * @param $nums
     * @return bool
     * @author Michael
     * @time   2019/7/17 20:57
     */
    public function checkHalfStraight ( $nums ) {
        sort($nums);

        $tmp = 0;
        0 == $nums[0] && $tmp++;
        1 == $nums[1] && $tmp++;
        9 == $nums[2] && $tmp++;
        if ( 2 === $tmp ) return true;

        for ( $i = 0; $i < count($nums) - 1; $i++ ) {
            if ( 1 === $nums[$i + 1] - $nums[$i] ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查葫芦
     *
     * @param $nums
     * @return bool
     * @author Michael
     * @time   2019/7/18 12:55
     */
    public function checkFullHouse ( $nums ) {
        $array = array_count_values($nums);
        $str   = implode('', $array);
        if ( in_array($str, [ '23', '32' ]) ) {
            return true;
        }
        return false;
    }

    /**
     * 检查三条
     *
     * @param $nums
     * @return bool
     * @author Michael
     * @time   2019/7/18 12:57
     */
    public function checkSet ( $nums ) {
        $array = array_count_values($nums);
        $str   = implode('', $array);
        if ( in_array($str, [ '113', '311', '131' ]) ) {
            return true;
        }

        return false;
    }

    /**
     * 检查跨度
     *
     * @param $nums
     * @return mixed
     * @author Michael
     * @time   2019/7/18 14:16
     */
    public function checkSpan ( $nums ) {
        sort($nums);

        $count = count($nums);

        return $nums[$count - 1] - $nums[0];
    }
}