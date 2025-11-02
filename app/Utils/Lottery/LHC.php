<?php
/**
 * 六合彩辅助类
 * Date: 2019/7/15
 * Time: 20:52
 */

namespace App\Utils\Lottery;

use App\Services\Context;
use App\Utils\Enum\ClientEnum;
use App\Exceptions\ErrMsg;
use App\Utils\Date\Lunar;

class LHC extends Lottery implements LotteryInterface
{

    use Context;

    const SUM_BIG_SMALL_VALUE = 175; // 总和大小区隔值

    private $settings = [];

    public function __construct () {
        $this->settings = self::getConfig('lott', 'lhc', []);
    }

    /**
     * 获取开奖结果
     *
     * @param        $nums
     * @param        $client
     * @param string $issue
     * @return string
     * @throws ErrMsg
     * @author Michael
     * @time   2019/7/18 18:57
     * @since  1.0.0
     */
    public function getGameResult ( $nums, $client, $issue = '' ) {
        !is_array($nums) && $nums = explode(',', strval($nums));
        if ( 7 !== count($nums) ) {
            return '';
        }

        # 手机端
        if ( in_array($client, [ ClientEnum::CLIENT_TYPE_APP, ClientEnum::CLIENT_TYPE_WAP ]) ) {
            # 生肖
            $zodiacs = $this->getZodiacsByNums($nums, $issue);

            return implode(',', $zodiacs);
        }

        # PC端

        # 总和
        $data['sum'] = $this->getNumSum($nums);
        # 总和大小
        $data['sumBigOrSmall'] = $this->checkBigSmall($data['sum'], self::SUM_BIG_SMALL_VALUE);
        # 总和单双
        $data['sumOddOrEven'] = $this->checkOddEven($data['sum']);
        # 七色波
        $data['colorSeven'] = $this->checkSevenColors($nums);
        # 特码单双
        $data['tmOddOrEven'] = $this->checkTMOddEven($nums[6]);
        # 特码大小
        $data['tmBigOrSmall'] = $this->checkTMBigSmall($nums[6]);
        # 特码合单双
        $data['tmSumOddOrEven'] = $this->checkTMSumOddEven($nums[6]);
        # 特码合大小
        $data['tmSumBigOrSmall'] = $this->checkTMSumBigSmall($nums[6]);
        # 特码合大小尾
        $data['tmSumUnits'] = $this->checkTMUnits($nums[6]);

        return $data;
    }

    /**
     * 判断特码大小
     *
     * @param $num
     * @return string
     * @author Michael
     * @time   2019/7/18 16:25
     */
    public function checkTMBigSmall ( $num ) {
        $num = intval($num);

        if ( 49 === $num ) return '和';

        if ( 25 <= $num ) return '大';

        return '小';
    }

    /**
     * 判断特码单双
     *
     * @param $num
     * @return string
     * @author Michael
     * @time   2019/7/18 16:27
     */
    public function checkTMOddEven ( $num ) {
        $num = intval($num);

        if ( 49 === $num ) return '和';

        if ( 1 === $num % 2 ) return '单';

        return '双';
    }

    /**
     * 判断特码合大小
     *
     * @param $num
     * @return string
     * @author Michael
     * @time   2019/7/18 16:37
     */
    public function checkTMSumBigSmall ( $num ) {
        $num = intval($num);
        if ( 49 === $num ) return '和';

        $array = str_split($num);
        $sum   = array_values($array);
        if ( 7 <= $sum ) return '大';
        return '小';
    }

    /**
     * 判断特码合单双
     *
     * @param $num
     * @return string
     * @author Michael
     * @time   2019/7/18 16:37
     */
    public function checkTMSumOddEven ( $num ) {
        $num = intval($num);
        if ( 49 === $num ) return '和';

        $array = str_split($num);
        $sum   = array_values($array);
        if ( 1 === $sum % 2 ) return '单';
        return '双';
    }

    /**
     * 判断特码合大小尾
     *
     * @param $num
     * @return string
     * @author Michael
     * @time   2019/7/18 16:34
     */
    public function checkTMUnits ( $num ) {
        $num = intval($num);
        if ( 49 === $num ) return '和';

        $array = str_split($num);
        $units = $array[count($array) - 1];
        if ( $units >= 5 ) return '尾大';
        return '尾小';
    }

    /**
     * 判断七色波
     *
     * @param $nums
     * @return string
     * @author Michael
     * @time   2019/7/18 16:57
     */
    public function checkSevenColors ( $nums ) {
        $red   = 0;
        $green = 0;
        $blue  = 0;

        #正码
        foreach ( $nums as $key => $num ) {
            if ( $key == count($nums) - 1 ) continue;
            $num = intval($num);
            in_array($num, $this->settings['redBalls']) && $red = $red + 1;
            in_array($num, $this->settings['greenBalls']) && $green = $green + 1;
            in_array($num, $this->settings['blueBalls']) && $blue = $blue + 1;
        }

        # 特码
        $tm = intval(end($nums));
        in_array($tm, $this->settings['blueBalls']) && $red += 1.5;
        in_array($tm, $this->settings['greenBalls']) && $green += 1.5;
        in_array($tm, $this->settings['blueBalls']) && $blue += 1.5;

        if ( 3 == $blue && 3 == $green && 1.5 == $red ) return '和';
        if ( 3 == $blue && 3 == $red && 1.5 == $green ) return '和';
        if ( 3 == $green && 3 == $red && 1.5 == $blue ) return '和';

        if ( $blue > $green && $blue > $red ) return '蓝波';
        if ( $green > $blue && $green > $red ) return '绿波';
        if ( $red > $green && $red > $blue ) return '红波';

        return '--';
    }

    /**
     * 根据阳历日期获取生肖
     *
     * @param $solarDate
     * @return mixed
     * @author Michael
     * @time   2019/7/25 20:35
     */
    public function getZodiacBySolarDate ( $solarDate ) {
        $zodiacs = $this->settings['zodiacs'];

        $lunar     = new Lunar();
        $lunarDate = $lunar->S2L($solarDate);
        $lunarYear = date('Y', $lunarDate);

        $key = (intval($lunarYear) - 1900) % 12;
        return $zodiacs[$key];
    }

    /**
     * 根据阳历日期获取生肖号码数组
     *
     * @param $solarDate
     * @return array
     * @author Michael
     * @time   2019/7/25 21:19
     */
    public function getZodiacNumsBySolarDate ( $solarDate ) {
        $zodiacs    = $this->settings['zodiacs'];
        $zodiacNums = $this->settings['zodiacNums'];

        # 获取生肖
        $zodiac = $this->getZodiacBySolarDate($solarDate);

        # 获取新的生肖数组
        $index = 0;
        foreach ( $zodiacs as $key => $value ) {
            if ( $zodiac == $value ) {
                $index = $key;
                break;
            }
        }

        $newZodiacs = [];
        for ( $i = $index; $i >= 0; $i-- ) {
            $newZodiacs[] = $zodiacs[$i];
        }

        for ( $j = 11; $j > $index; $j-- ) {
            $newZodiacs[] = $zodiacs[$j];
        }

        # 获取生肖号码数组
        $nums = [];
        foreach ( $newZodiacs as $zodiacKey => $value ) {
            $nums[$value] = $zodiacNums[$zodiacKey];
        }

        return $nums;
    }

    /**
     * 根据期号获取生肖号码数组
     *
     * @param $issue
     * @return array
     * @author Michael
     * @time   2019/7/25 22:47
     */
    public function getZodiacNumsByIssue ( $issue ) {
        $solarDate = $this->getSolarDateByIssue($issue);

        return $this->getZodiacNumsBySolarDate($solarDate);
    }

    /**
     * 格式化生肖号码组
     *
     * @param $openTime
     * @param $isInstant
     * @return array
     * @author Michael
     * @time   2019/7/27 13:27
     */
    public function formatZodiacNumsBySolarDate ( $openTime, $isInstant=0 ) {
        $zodiacKeys = $this->settings['zodiacKeys'];

        $solarDate = $isInstant ? date('Ymd') : date('Ymd', strtotime($openTime));
        $data = $this->getZodiacNumsBySolarDate($solarDate);

        # 重组数组
        $array = [];
        foreach ( $data as $key => $value ) {
            foreach ( $zodiacKeys as $zodiacKey => $zodiacName ) {
                if ( $key == $zodiacName ) {
                    $array[] = [
                        'key'  => $zodiacKey,
                        'name' => $zodiacName,
                        'nums' => $value,
                    ];
                }
            }
        }

        return $array;
    }

    /**
     * 根据开奖期号获取阳历日期
     *
     * @param $issue
     * @return array
     * @author Michael
     * @time   2019/7/18 18:43
     */
    public function getSolarDateByIssue ( $issue ) {
        # 香港六合彩
        if ( preg_match("/^[0-9]{7}$/", $issue) ) {
            return $issue;
        } # 自营彩，期数1901020001格式
        elseif ( preg_match("/^[0-9]{10}$/", $issue) ) {
            return '20' . substr($issue, 0, 6);
        } # 自营彩，期数201901020001格式
        elseif ( preg_match("/^[0-9]{12}$/", $issue) ) {
            return substr($issue, 0, 8);
        }

        return '';
    }

    /**
     * 获取开奖号码对应的生肖
     *
     * @param $nums
     * @param $issue
     * @return array
     * @author Michael
     * @time   2019/7/18 18:41
     */
    public function getZodiacsByNums ( $nums, $issue ) {
        $zodiacs = [];
        foreach ( $nums as $num ) {
            $zodiacs[] = $this->getZodiacByNum($num, $issue);
        }

        return $zodiacs;
    }

    /**
     * 根据号码获取生肖
     *
     * @param $num
     * @param $issue
     * @return array|int|string
     * @author Michael
     * @time   2019/7/25 22:00
     */
    public function getZodiacByNum ( $num, $issue ) {
        # 获取生肖号码数组
        $zodiacNums = $this->getZodiacNumsBySolarDate($issue);

        foreach ( $zodiacNums as $zodiac => $nums ) {
            if ( in_array($num, $nums) ) {
                return $zodiac;
            }
        }

        return [];
    }

    /**
     * 根据期号获取五行号码
     *
     * @param $issue
     * @return array
     * @author Michael
     * @time   2019/7/27 12:40
     */
    public function getFiveElementsByIssue ( $issue ) {

        $solarDate = $this->getSolarDateByIssue($issue);

        return $this->getFiveElementsBySolarDate($solarDate);
    }

    /**
     * 格式化五行数据
     *
     * @param $openTime
     * @param $isInstant
     * @return array
     * @author Michael
     * @time   2019/7/27 13:36
     */
    public function formatFiveElementsBySolarDate ( $openTime, $isInstant=0 ) {

        $solarDate = $isInstant ? date('Ymd') : date('Ymd', strtotime($openTime));

        # 根据期号获取五行
        $fiveElements = $this->getFiveElementsBySolarDate($solarDate);

        $data = [];
        foreach ( $fiveElements as $key => $value ) {

            switch ( $key ) {
                case 'metal':
                    $tmp['name'] = '金';
                    break;
                case 'wood':
                    $tmp['name'] = '木';
                    break;
                case 'water':
                    $tmp['name'] = '水';
                    break;
                case 'fire':
                    $tmp['name'] = '火';
                    break;
                case 'earth':
                    $tmp['name'] = '土';
                    break;
                default:
                    $tmp['name'] = '';
            }
            $tmp['key']  = $key;
            $tmp['nums'] = $value;
            $data[] = $tmp;
        }

        return $data;
    }

    /**
     * 根据阳历日期获取五行号码
     *
     * @param $solarDate
     * @return array
     * @author Michael
     * @time   2019/7/27 12:40
     */
    public function getFiveElementsBySolarDate ( $solarDate ) {
        $fiveElements = $this->settings['fiveElements'];
        $year         = date('Y', strtotime($solarDate));
        return isset($fiveElements[$year]) ? $fiveElements[$year] : [];
    }

}