<?php
/**
 * @title 数据净化器
 * @link  https://www.php.net/manual/zh/function.filter-var.php
 * @link  https://php.net/manual/zh/filter.filters.sanitize.php
 */

namespace App\Utils;


use App\Exceptions\ErrMsg;

class Filter
{

    private static $instance = [];

    /**
     * @title  单例通用插件
     * @return mixed
     * @author benjamin
     */
    public static function getInstance () {
        $class = static::class;
        $k     = md5($class);
        if ( empty(self::$instance[$k]) ) {
            self::$instance[$k] = new $class();
        }
        return self::$instance[$k];
    }

    /**
     * @title  数据清理调度
     * @param string       $input
     * @param string|array $util
     * @return mixed
     * @throws ErrMsg
     * @author benjamin
     */
    public static function sanitize ( $input, $util ) {
        $utils = is_array($util) ? $util : (array)$util;

        foreach ( $utils as $util ) {
            if ( !method_exists(self::getInstance(), $util) ) {
                throw new ErrMsg('数据清理器参数异常：清理方法：' . $util . ' 不存在！');
            }

            $input = Filter::$util($input);
            #todo  清理失败是否报错？
            if ( empty($input) ) {
                throw new ErrMsg($util . '：数据清理失败！');
            }
        }

        return $input;
    }

    /**
     * @title 去除标签以及删除或编码不需要的字符
     * <code>
     *  "<b>Bill Gates<b>"  => "Bill Gates";
     * </code>
     */
    public static function string ( $input ) {
        return filter_var($input, FILTER_SANITIZE_STRING);
    }

    /**
     * @title 该过滤器用于对 "<>& 以及 ASCII 值在 32 值以下的字符进行转义
     * <code>
     *  "Is Peter <smart> & funny?" => "Is Peter &lt;smart&gt; &amp; funny?";
     * </code>
     */
    public static function specialChars ( $input ) {
        return filter_var($input, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
     * @title  非字母数字净化
     * @param $input
     * @return string|string[]|null
     */
    public static function alnum ( $input ) {
        return preg_replace('/[^0-9A-Za-z]/', '', $input);
    }

    /**
     * @title  删除字符串中所有非法的 URL 字符
     * @tips   该过滤器允许所有的字符、数字以及 $-_.+!*'(),{}|\\^~[]`"><#%;/?:@&=。
     * <code>
     *  "http://www.w3schooêèél.coêèém.cn" => "http://www.w3school.com.cn";
     * </code>*
     * @param $input
     * @return mixed
     */
    public static function url ( $input ) {
        return filter_var($input, FILTER_SANITIZE_URL);
    }

    /**
     * @title  删除字符串中所有非法的 e-mail 字符
     * @tips   该过滤器允许所有的字符、数字以及 $-_.+!*'(),{}|\\^~[]`"><#%;/?:@&=。
     * <code>
     *  "some(one)@exa\\mple.com" => "someone@example.com"
     * </code>*
     * @param $input
     * @return mixed
     */
    public static function email ( $input ) {
        return filter_var(
            $input,
            FILTER_SANITIZE_EMAIL,
            FILTER_FLAG_EMAIL_UNICODE
        );
    }

    /**
     * @title  对IP进行过滤
     * @param $input
     * @author benjamin
     */
    public static function ip ( $input ) {

    }


    /**
     * 过滤字符串空格
     * @param $data
     * @author benjamin
     * @return string|null
     */
    public static function trim ( $data ) {
        if ( empty($data) ) return $data;

        $result = null;
        if ( is_array($data) ) {
            foreach ( $data as $k => $v ) {
                $result[$k] = self::trim($v);
            }
        } else {
            $result = trim($data);
        }
        return $result;
    }

    /**
     * 过滤文本内的恶意脚本
     * @param $text
     * @return string|string[]|null
     */
    public static function getSafeText($text) {

        # 标签实体化
        $text = htmlspecialchars($text);

        # 恶意脚本过滤
        $text = self::removeXSS($text);

        return $text;
    }

    /**
     * @title  过滤所有html, 使用（sting-filter 一样的效果）
     * @param $input
     * @return mixed|string|string[]|null
     * @author benjamin
     */
    public static function cleanHtml ( $input ) {
        $input = str_replace("\r", "", $input);//过滤换行
        $input = str_replace("\n", "", $input);//过滤换行
        $input = str_replace("\t", "", $input);//过滤换行
        $input = str_replace("\r\n", "", $input);//过滤换行
        $input = preg_replace("/\s+/", " ", $input);//过滤多余回车
        $input = preg_replace("/<[ ]+/si", "<", $input); //过滤<__("<"号后面带空格)
        $input = preg_replace("/<\!--.*?-->/si", "", $input); //过滤html注释
        $input = preg_replace("/<(\!.*?)>/si", "", $input); //过滤DOCTYPE
        $input = preg_replace("/<(\/?html.*?)>/si", "", $input); //过滤html标签
        $input = preg_replace("/<(\/?head.*?)>/si", "", $input); //过滤head标签
        $input = preg_replace("/<(\/?meta.*?)>/si", "", $input); //过滤meta标签
        $input = preg_replace("/<(\/?body.*?)>/si", "", $input); //过滤body标签
        $input = preg_replace("/<(\/?link.*?)>/si", "", $input); //过滤link标签
        $input = preg_replace("/<(\/?form.*?)>/si", "", $input); //过滤form标签
        $input = preg_replace("/cookie/si", "COOKIE", $input); //过滤COOKIE标签
        $input = preg_replace("/<(applet.*?)>(.*?)<(\/applet.*?)>/si", "", $input); //过滤applet标签
        $input = preg_replace("/<(\/?applet.*?)>/si", "", $input); //过滤applet标签
        $input = preg_replace("/<(style.*?)>(.*?)<(\/style.*?)>/si", "", $input); //过滤style标签
        $input = preg_replace("/<(\/?style.*?)>/si", "", $input); //过滤style标签
        $input = preg_replace("/<(title.*?)>(.*?)<(\/title.*?)>/si", "", $input); //过滤title标签
        $input = preg_replace("/<(\/?title.*?)>/si", "", $input); //过滤title标签
        $input = preg_replace("/<(object.*?)>(.*?)<(\/object.*?)>/si", "", $input); //过滤object标签
        $input = preg_replace("/<(\/?objec.*?)>/si", "", $input); //过滤object标签
        $input = preg_replace("/<(noframes.*?)>(.*?)<(\/noframes.*?)>/si", "", $input); //过滤noframes标签
        $input = preg_replace("/<(\/?noframes.*?)>/si", "", $input); //过滤noframes标签
        $input = preg_replace("/<(i?frame.*?)>(.*?)<(\/i?frame.*?)>/si", "", $input); //过滤frame标签
        $input = preg_replace("/<(\/?i?frame.*?)>/si", "", $input); //过滤frame标签
        $input = preg_replace("/<(script.*?)>(.*?)<(\/script.*?)>/si", "", $input); //过滤script标签
        $input = preg_replace("/<(\/?script.*?)>/si", "", $input); //过滤script标签
        $input = preg_replace("/javascript/si", "Javascript", $input); //过滤script标签
        $input = preg_replace("/vbscript/si", "Vbscript", $input); //过滤script标签
        $input = preg_replace("/on([a-z]+)\s*=/si", "On\\1=", $input); //过滤script标签
        $input = preg_replace("/&#/si", "&＃", $input); //过滤script标签，如javAsCript:alert();
        //使用正则替换
        $pat   = "/<(\/?)(script|i?frame|style|html|body|li|p|i|map|title|img|link|span|u|font|table|tr|b|marquee|td|strong|div|a|meta|\?|\%)([^>]*?)>/isU";
        $input = preg_replace($pat, "", $input);

        return $input;
    }

    public static function removeXSS($val) {
        // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
        // this prevents some character re-spacing such as <java\0script>
        // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
        $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

        // straight replacements, the user should never need these since they're normal characters
        // this prevents like <IMG SRC=@avascript:alert('XSS')>
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

            // @ @ search for the hex values
            $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
            // @ @ 0{0,7} matches '0' zero to seven times
            $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
        }

        // now the only remaining whitespace attacks are \t, \n, and \r
        $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);

        $found = true; // keep replacing as long as the previous round replaced something
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
                $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
                if ($val_before == $val) {
                    // no replacements were made, so exit the loop
                    $found = false;
                }
            }
        }
        return $val;
    }

    /**
     * sql语句过滤 + 异常上报
     * @param $sql
     * @return string|string[]|null
     * @throws ErrMsg
     */
    static function sql_filter($sql, $params = []){

        $sql = trim($sql);
        $before = $sql;
        $isAlert = false;
        $index = 0;

        if (preg_match("/UPDATE([\s\S]*)ssc_bet_order([\s\S]*)SET([\s\S]*)(bet_data|bet_issue|bet_info|played_group_id|played_id|group_name|total_nums|total_money|bet_time|sign)/i", $sql)){
            $sql = '';
            $isAlert=true;
            $index = 1;
        }
//        if (preg_match("/REPLACE([\s\S]*)(inf_player_transfer|ssc_bet_order)/i", $sql)){
//            $sql = '';
//            $isAlert=true;
//            $index = 2;
//        }
        if (preg_match("/DELETE([\s\S]*)FROM([\s\S]*)ssc_bet_order/i", $sql) && !preg_match("/is_delete([\s\S]*)FROM([\s\S]*)ssc_bet_order/i", $sql) &&
            (!empty($_SERVER['PHP_SELF'])
                && $_SERVER['PHP_SELF']!='/index.php/cron/purge_session.do'
                && $_SERVER['PHP_SELF']!='/index.php/cron/backup_bets05.do'
                && $_SERVER['PHP_SELF']!='/index.php/cron/backup_bets04.do'
                && $_SERVER['PHP_SELF']!='/fhptbet.php/business/batchNumSubmit'
            )){
            $sql = '';
            $isAlert=true;
            $index = 3;
        }
        if (preg_match("/DELETE/i", $sql) && preg_match("/ssc_member_recharge_thisisallowed/i", $sql)){
            $sql = preg_replace("/ssc_member_recharge_thisisallowed/i", "ssc_member_recharge", $sql);
            $isAlert=true;
            $index = 4;
        }
//        if ( preg_match("/outfile|load_file|infile|alter([\s]*)table|truncate|trigger|references|replace([\s\S]*)into/i", $sql)
        if ( preg_match("/outfile|load_file|infile|alter([\s]*)table|truncate|trigger|references([\s\S]*)into/i", $sql)
            && (!empty($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF'] != '/index.php/sports/syncTableSportsMatch')
        ) {
            $sql     = '';
            $isAlert = true;
            $index = 5;
        }
        if (preg_match("/UPDATE([\s\S]*)ssc_member_message([\s\S]*)SET([\s\S]*)(bet_data|bet_issue|bet_info|played_group_id|played_id|group_name|total_nums|total_money|bet_time|sign)/i", $sql)){
            $sql = '';
            $isAlert=true;
            $index = 6;
        }

        # 上报告警群
        if ( $isAlert ) {
            $info = [
                'beforeSql' => $before,
                'afterSql'  => $sql,
                'bindData'  => $params,
                'index'     => $index
            ];
            \App\Utils\File\Logger::write('敏感SQL告警：' . json_encode($info, JSON_UNESCAPED_UNICODE) . PHP_EOL, 'bet_alert', \App\Utils\File\Logger::LEVEL_ERR);
        }

        return $sql;
    }

    /**
     * 参数过滤
     * @param $str
     * @return array|string
     */
    static function param_filter($str) {

        $filter = array(
            'select([\s\S]+)concat',
            'select([\s\S]+)from',
            'select([\s\S]+)sleep',
            'union([\s\S]+)select',
            'execute',
            'insert([\s\S]+)into',
            'replace([\s\S]+)into',
            'update([\s\S]+)set',
            'delete([\s\S]+)from',
            'create([\s\S]+)table',
            'drop([\s\S]+)table',
            'truncate',
            'show([\s\S]+)table',
            'information_schema([\s\S]+)',
            'bin([\s\S]+)bash',
            'fwrite([\s\S]+)',
            'fputs([\s\S]+)',
        );
        #意义不明，遂注释，徒增消耗
//    $filter_lower = array();
//    foreach ($filter as $f){
//        $filter_lower[] = strtolower($f);
//    }

        $isAlert = false;
        if (is_array($str)){
            $oldParams = $str;
            $newParams = [];

            foreach ($str as $key=>$val){
                $newkey = self::param_filter($key);
                $val = self::param_filter($val);
                unset($str[$key]);
                $str[$newkey] = $val;
            }
            $newParams = $str;
        }else{
            $oldParams = $str;
            $newParams = '';

            if (preg_match("/".implode('|', $filter)."/i", urldecode($str))){
                $str = preg_replace("/".implode('|', $filter)."/i", "***", urldecode($str));
                $isAlert = true;
            }
            if (preg_match("/".implode('|', $filter)."/i", base64_decode($str))){
                $str = base64_encode(preg_replace("/".implode('|', $filter)."/i", "***", base64_decode($str)));
                $isAlert = true;
            }
            $str = preg_replace("/".implode('|', $filter)."/i", "***", $str);

            # 三方通知参数特殊处理
            if ( !empty($_SERVER['REQUEST_URI']) && preg_match("/mobile\/userrech\/onlinePay.do\/notify/i", $_SERVER['REQUEST_URI']) ) {
                $str = addslashes($str);
            } else {
                $str = addslashes(htmlspecialchars($str));
            }
        }


        # 上报告警群
        if ( $isAlert ) {
            $info = [
                'oldParams' => $oldParams,
                'curParams'  => $newParams,
            ];
            \App\Utils\File\Logger::write('敏感参数告警：' . json_encode($info, JSON_UNESCAPED_UNICODE) . PHP_EOL, 'bet_alert', \App\Utils\File\Logger::LEVEL_ERR);
        }

        return $str;
    }
}