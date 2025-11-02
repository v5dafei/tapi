<?php
/**
 * 时间助手
 *
 * @link https://www.php.net/manual/zh/function.date.php
 */

namespace App\Utils\Date;


class DateHelper
{
    const TIME_START = 'start';
    const TIME_CUR   = 'current';
    const TIME_END   = 'end';

    const DATE_TODAY     = 'today';
    const DATE_YESTERDAY = 'yesterday';

    const DATE_THIS_WEEK = 'thisWeek';
    const DATE_LAST_WEEK = 'lastWeek';
    const DATE_NEXT_WEEK = 'nextWeek';

    const DATE_THIS_MONTH = 'thisMonth';
    const DATE_LAST_MONTH = 'lastMonth';
    const DATE_NEXT_MONTH = 'nextMonth';

    const DATE_THIS_MONDAY = 'thisMonday';
    const DATE_LAST_MONDAY = 'lastMonday';
    const DATE_NEXT_MONDAY = 'nextMonday';

    /**
     * @title  星期几
     * @author benjamin
     */
    public static function whichDayByWeek ( $date = null, $isCn = false ) {
        $weekCn = [ "日", "一", "二", "三", "四", "五", "六" ];
        $day    = !empty($date) ? date('w', strtotime($date)) : date('w');
        return $isCn ? '星期' . $weekCn[$day] : $day;
    }

    /**
     * @title  获取指定区间内的日期列表
     * @param $startDate
     * @param $endDate
     * @return array
     * @author benjamin
     */
    public static function getWeekDays ( $startDate, $endDate ) {
        $days = [];

        $curDate = $startDate;
        array_push($days, $curDate);

        do {
            $nextDate = date('Y-m-d', strtotime($curDate . ' +1 days'));
            array_push($days, $nextDate);
            $curDate = $nextDate;
        } while ( $curDate !== $endDate );

        return $days;
    }

    /**
     * @title  获取日期
     * @author benjamin
     */
    public static function getDate ( $type = self::DATE_TODAY, $format = 'Y-m-d' ) {
        switch ( $type ) {
            case self::DATE_YESTERDAY:
                $date = date($format, strtotime("-1 day"));
                break;
            case self::DATE_THIS_MONDAY:
                $date = date($format, strtotime("Monday"));
                break;
            case self::DATE_LAST_MONDAY:
                $date = date($format, strtotime("last Monday"));
                break;
            case self::DATE_NEXT_MONDAY:
                $date = date($format, strtotime("next Monday"));
                break;
            default:
                $date = date($format);
                break;
        }
        return $date;
    }

    /**
     * @title  获取某个时间段的开始和结束时间
     * @author benjamin
     */
    public static function getStartAndEndTime ( $type = self::DATE_TODAY, $timeType = 'ts' ) {
        $dateRange = self::getStartAndEndDate($type);
        $format    = [
            'start' => 'Y-m-d 00:00:00',
            'end'   => 'Y-m-d 23:59:59'
        ];

        switch ( $timeType ) {
            case 'ts': // 时间戳
                $time['start'] = strtotime(date($format['start'], strtotime($dateRange['start'])));
                $time['end']   = strtotime(date($format['end'], strtotime($dateRange['end'])));
                break;
            default:
                $time['start'] = date($format['start'], strtotime($dateRange['start']));
                $time['end']   = date($format['end'], strtotime($dateRange['end']));
                break;
        }

        return $time;
    }

    /**
     * @title  获取某个时间段的开始和结束日期
     * @link   https://blog.csdn.net/luoangen/article/details/83058205
     * @author benjamin
     */
    public static function getStartAndEndDate ( $type = self::DATE_TODAY ) {
        $date   = [ 'start' => null, 'end' => null ];
        $format = [ 'start' => 'Y-m-d', 'end' => 'Y-m-d' ];

        switch ( $type ) {
            case self::DATE_YESTERDAY:
                $start = date($format['start'], strtotime("-1 day"));
                $end   = date($format['end'], strtotime("-1 day"));
                break;
            case self::DATE_THIS_WEEK:
                $date['start'] = date($format['start'], (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
                $date['end']   = date($format['end'], (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600));
                break;
            case self::DATE_LAST_WEEK:
                $date['start'] = date($format['start'], mktime(null, null, null, date('m'), date('d') - date('w') + 1 - 7, date('Y')));
                $date['end']   = date($format['end'], mktime(null, null, null, date('m'), date('d') - date('w') + 7 - 7, date('Y')));
                break;
            case self::DATE_THIS_MONTH:
                $date['start'] = date($format['start'], mktime(null, null, null, date('m'), 1, date('Y')));
                $date['end']   = date($format['end'], mktime(null, null, null, date('m'), date('t'), date('Y')));
                break;
            case self::DATE_LAST_MONTH:
                $date['start'] = date($format['start'], mktime(null, null, null, date('m') - 1, 1, date('Y')));
                $date['end']   = date($format['end'], mktime(null, null, null, date('m'), 0, date('Y')));
                break;
            default:
                $date['start'] = date($format['start']);
                $date['end']   = date($format['end']);
                break;
        }
        return $date;
    }

    /**
     * @title  获取时间戳根据日期
     * @author benjamin
     */
    public static function getTimeByDate ( $date, $type = self::TIME_CUR ) {
        switch ( $type ) {
            case self::TIME_START:
                $date = date('Y-m-d 00:00:00', strtotime($date));
                $time = strtotime($date);
                break;
            case self::TIME_END:
                $date = date('Y-m-d 23:59:59', strtotime($date));
                $time = strtotime($date);
                break;
            default:
                $date = date('Y-m-d H:i:s', strtotime($date));
                $time = strtotime($date);
                break;
        }
        return $time;
    }

    /**
     * @title  比较两个日期
     * @tips   两个数相等返回0, 左边的数比较右边的数大返回1, 否则返回-1.
     * @author benjamin
     */
    public static function compare ( $leftDate, $rightDate ) {
        $leftTime  = self::getTimeByDate($leftDate);
        $rightTime = self::getTimeByDate($rightDate);

        if ( $leftTime > $rightTime ) {
            return 1;
        } else if ( $leftTime < $rightTime ) {
            return -1;
        }
        return 0;
    }

    /**
     * 根据秒数 换算成 xx天xx小时xx分xx秒
     * @param $remain_time
     * @return string
     */
    public static function getStayTime($remain_time){
        $day = floor($remain_time / (3600*24));
        $day = $day > 0 ? $day.'天' : '';
        $hour = floor(($remain_time % (3600*24)) / 3600);
        $hour = $hour > 0 ? $hour.'小时' : '';

        $minutes = floor((($remain_time % (3600*24)) % 3600) / 60);
        $minutes = $minutes > 0 ? $minutes.'分' : '';
        $seconds = floor((($remain_time % (3600*24)) % 3600) % 60);
        $seconds = $seconds > 0 ? $seconds.'秒' : '';
        return $day.$hour.$minutes.$seconds;
    }

    /**
     * 获取毫秒时间戳
     * @return string
     * @author Michael
     * @time   2019/11/20 13:29
     */
    public static function getMicroTime() {
        list($s1, $s2) = explode(' ', microtime());
        return (string)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    public static function checkDateIsValid($date, $formats = array("Y-m-d", "Y/m/d","Y-m-d H:i:s", "Y/m/d H:i:s","Y-m-d H:i", "Y/m/d H:i")) {
        $unixTime = strtotime($date);
        if (!$unixTime) { //strtotime转换不对，日期格式显然不对。
            return false;
        }
        //校验日期的有效性，只要满足其中一个格式就OK
        foreach ($formats as $format) {
            if (date($format, $unixTime) == $date) {
                return true;
            }
        }

        return false;
    }
}