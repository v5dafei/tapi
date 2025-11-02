<?php
/**
 * @title 字符串处理工具类.
 * @link  http://www.runoob.com/php/php-ref-string.html
 * User: benjamin
 * Date: 2019/5/28/0026
 * Time: 19:27
 */

namespace App\Utils\Str;

use App\Services\System\Image;

class StrHelper
{
    const STR_USERNAME = 1;     // 用户名
    const STR_EMAIL    = 2;        // Email
    const STR_MOBILE   = 3;       // 手机号
    const STR_CARD     = 4;         // 银行卡

    /**
     * @title 计算中文字符串长度
     * @param null $string
     * @return int
     */
    public static function utf8_strlen ( $string = null ) {
        // 将字符串分解为单元
        preg_match_all(" /./us", $string, $match);
        // 返回单元个数
        return count($match[0]);
    }

    /**
     * 根据不同类型隐藏字符串部分字符
     *
     * @param string $str
     * @param int    $type
     * @return mixed
     * @author Michael
     * @time   2019/7/9 16:28
     * @since  1.0.0
     */
    public static function hideStr ( $str, $type = self::STR_USERNAME ) {
        switch ( $type ) {
            case self::STR_USERNAME:
                if ( 6 < mb_strlen($str) ) {
                    return mb_substr($str, 0, 3) . str_repeat('*', mb_strlen($str) - 6) . mb_substr($str, -3);
                } else {
                    return mb_substr($str, 0, 3) . "***";
                }
            case self::STR_EMAIL:
                $array  = explode('@', $str);
                $prefix = (mb_strlen($array[0]) < 4) ? '' : mb_substr($str, 0, 3);
                $count  = 0;
                $str    = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $str, -1, $count);
                return $prefix . $str;
            case self::STR_MOBILE:
                return mb_substr($str, 0, 3).'****'.mb_substr($str, 7);
            case self::STR_CARD:
                if ( 8 < mb_strlen($str) ) {
                    return mb_substr($str, 0, 4) . str_repeat('*', mb_strlen($str) - 8) . mb_substr($str, -4);
                } else {
                    return mb_substr($str, 0, 4) . "***";
                }
            default:
                return $str;
        }
    }

    /**
     * @title 金额格式化
     * @param int $num
     * @param int $dotnum
     * @return mixed
     * @author benjamin
     */
    public static function priceFormat ( $num = 0, $dotnum = 2 ) {
        if ( $num == '' || !is_numeric($num) ) $num = 0;
        return str_ireplace(',', '', number_format($num, $dotnum));
    }

    /**
     * @title 随机字符串生成
     * @param        $length
     * @param string $keyspace
     * @return string
     * @throws \Exception
     */
    public static function randomString ( $length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {
        $pieces = [];
        $max    = mb_strlen($keyspace, '8bit') - 1;
        for ( $i = 0; $i < $length; ++$i ) {
            $pieces [] = $keyspace[rand(0, $max)];
        }
        return implode('', $pieces);
    }

    public static function getStrCharset ( $val ) {
        return mb_detect_encoding($val, [ 'ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5' ]);
    }

    /**
     * @title  反转中英文字符串
     * @param $str
     * @return string
     * @author benjamin
     */
    public static function strrev ( $str ) {
        //先判断参数是否为字符串，且为UTF8编码
        if ( !is_string($str) || !mb_check_encoding($str, "utf-8") ) {
            return $str;
        }

        //用mb_strlen函获取算utf8字符串的长度
        $length = mb_strlen($str, "utf-8");

        //声明一个数组备用
        $arr = [];

        //将字符串拆开放入数组
        for ( $i = 0; $i < $length; $i++ ) {
            $arr[$i] = mb_substr($str, $i, 1, "utf-8");
        }

        //将数组按键名大小反转
        krsort($arr);

        //将数组中单个字符元素重新组合成字符串
        $str = implode("", $arr);

        //将翻转后的字符串返回
        return $str;
    }


    /**
     * @title 随机字符生成
     * @param     $length
     * @param int $numeric 是否是数字
     * @return string
     */
    public static function random ( $length, $numeric = 0 ) {
        PHP_VERSION < '4.2.0' ? mt_srand((double)microtime() * 1000000) : mt_srand();
        $seed = base_convert(md5(print_r($_SERVER, 1) . microtime()), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
        $hash = '';
        $max  = strlen($seed) - 1;
        for ( $i = 0; $i < $length; $i++ ) {
            $hash .= $seed[mt_rand(0, $max)];
        }
        return $hash;
    }

    public static function getHtmlContent ( $content ) {
        $content = Image::getHtmlAfterReplaceImages($content);
        $content = htmlspecialchars_decode($content);

        return $content;
    }

    /**
     * 字符串数字转数字
     *
     * @test   ?m=test&c=regexp&a=index&act=strNum
     * @param $strNum
     * @author benjamin
     * @return int|string
     */
    public static function strNumToNum ( $strNum ) {
        # 数字字符串：+ 0 隐式转换到对应数字类型
        return is_numeric($strNum) && strpos($strNum, '0') !== 0 ? $strNum + 0 : $strNum;
    }


    /**
     * 关键词相关处理
     *
     * @param string $str           关键词
     * @param array $filterConfig   过滤配置词组
     * @param string $action        操作方法: replace-替换，error-抛出异常
     * @throws \Exception
     * @return string
     */
    public static function filterKeywords ( $str, $filterConfig = [], $action = 'replace') {

        # 敏感词汇处理
        if ( !empty($filterConfig) ) {
            $badWords = $filterConfig;
            $oldChar  = [ " ", "　", "\t", "\n", "\r" ];
            $newChar  = [ "", "", "", "", "" ];
            $badWords = implode('|', $badWords);
            if ( preg_match("/(" . $badWords . ")/is", str_replace($oldChar, $newChar, $str)) ) {
                if ( $action == 'replace' ) {
                    $str = preg_replace("/(" . $badWords . ")/is", "***", str_replace($oldChar, $newChar, $str));
                } else {
                    throw new \Exception('包含非法词汇, 请重新输入!');
                }
            }
        }

        return $str;
    }


    public static function strLenSort ( $strArr = [] ) {
        usort($strArr, function ( $a, $b ) {
            return strlen($b) - strlen($a);
        });
        return $strArr;
    }

    /**
     * 字符串连接符过滤
     */
    public static function filterConnector () { }

    /**
     * 全角和半角转换函数
     * 半角和全角转换函数，第二个参数如果是0,则是半角到全角；如果是1，则是全角到半角
     *
     * @param     $str
     * @param int $args2
     * @return bool|mixed
     */
    public static function SBC_DBC ( $str, $args2 = 1 ) {
        $DBC = [ // 全角
            '０', '１', '２', '３', '４',
            '５', '６', '７', '８', '９',
            'Ａ', 'Ｂ', 'Ｃ', 'Ｄ', 'Ｅ',
            'Ｆ', 'Ｇ', 'Ｈ', 'Ｉ', 'Ｊ',
            'Ｋ', 'Ｌ', 'Ｍ', 'Ｎ', 'Ｏ',
            'Ｐ', 'Ｑ', 'Ｒ', 'Ｓ', 'Ｔ',
            'Ｕ', 'Ｖ', 'Ｗ', 'Ｘ', 'Ｙ',
            'Ｚ', 'ａ', 'ｂ', 'ｃ', 'ｄ',
            'ｅ', 'ｆ', 'ｇ', 'ｈ', 'ｉ',
            'ｊ', 'ｋ', 'ｌ', 'ｍ', 'ｎ',
            'ｏ', 'ｐ', 'ｑ', 'ｒ', 'ｓ',
            'ｔ', 'ｕ', 'ｖ', 'ｗ', 'ｘ',
            'ｙ', 'ｚ', '－', '　', '：',
            '．', '，', '／', '％', '＃',
            '！', '＠', '＆', '（', '）',
            '＜', '＞', '＂', '＇', '？',
            '［', '］', '｛', '｝', '＼',
            '｜', '＋', '＝', '＿', '＾',
            '￥', '￣', '｀'
        ];
        $SBC = [ // 半角
            '0', '1', '2', '3', '4',
            '5', '6', '7', '8', '9',
            'A', 'B', 'C', 'D', 'E',
            'F', 'G', 'H', 'I', 'J',
            'K', 'L', 'M', 'N', 'O',
            'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y',
            'Z', 'a', 'b', 'c', 'd',
            'e', 'f', 'g', 'h', 'i',
            'j', 'k', 'l', 'm', 'n',
            'o', 'p', 'q', 'r', 's',
            't', 'u', 'v', 'w', 'x',
            'y', 'z', '-', ' ', ':',
            '.', ',', '/', '%', '#',
            '!', '@', '&', '(', ')',
            '<', '>', '"', '\'', '?',
            '[', ']', '{', '}', '\\',
            '|', '+', '=', '_', '^',
            '$', '~', '`'
        ];
        if ( $args2 == 0 )
            return str_replace($SBC, $DBC, $str);  //半角到全角
        if ( $args2 == 1 )
            return str_replace($DBC, $SBC, $str);  //全角到半角
        else
            return false;
    }

    /**
     * 取毫秒级时间戳，默认返回普通秒级时间戳 time() 及 3 位长度毫秒字符串
     *
     * @param int  $msec_length 毫秒长度，默认 3
     * @param int  $random_length 添加随机数长度，默认 0
     * @param bool $dot 随机是否加上小数点，默认 false
     * @param int  $delay 是否延迟，传入延迟秒数，默认 0
     * @return string
     */
    public static function msectime($msec_length = 3, $random_length = 0, $dot = false, $delay = 0) {
        list($msec, $sec) = explode(' ', microtime());
        $rand     = $random_length > 0 ?
            number_format(
                mt_rand(1, (int)str_repeat('9', $random_length))
                * (float)('0.' . str_repeat('0', $random_length - 1) . '1'),
                $random_length,
                '.',
                '') : '';
        $msectime = sprintf('%.0f', (floatval($msec) + floatval($sec) + $delay) * pow(10, $msec_length));
        return $dot ? $msectime . '.' . substr($rand, 2) : $msectime . substr($rand, 2);
    }

    static function base64Decode ( $str ) {
        if ( $str == base64_encode(base64_decode($str)) ) {
            return base64_decode($str);
        }
        return $str;
    }
}