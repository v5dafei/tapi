<?php

use App\Services\Context;

if (!function_exists('returnApiJson')) {
    function returnApiJson($msg, $status = 0, $data = [], $code = 200, $errorcode='') {
        if(!empty($errorcode)){
            $data['errorcode']=$errorcode;
        }

        foreach ($data as $key => &$value) {
            if(is_float($value)){
                $value =(string)$value;
            }
        }

        $data = [
            'success'       => $status ? true :false,
            'message'       => $msg,
            'data'          => $data,
            'code'          => $code
        ];
        return response()->json($data)->setEncodingOptions(JSON_UNESCAPED_UNICODE)->header('X-Frame-Options', 'ALLOW-FROM');
    }
}

if (!function_exists('returnBaseJson')) {
    function returnBaseJson($msg, $status = 0, $data = [], $code = 401) {
        $data = [
            'success'       => $status ? true :false,
            'message'       => $msg,
            'data'          => $data,
            'code'          => $code
        ];
        return response()->json($data,$code)->send();
    }
}

if (!function_exists('ErrInfoHandler2')) {
    function ErrInfoHandler2 ( $e, $type = 'ErrMsg', $appConf = [] ) {
//    $errContent = $e->getMessage();

        # 获取上一条错误信息
        if ( $e->getPrevious() ) {
//            $e = $e->getPrevious();
        }

        $debug = ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'code' => $e->getCode() ];

//        $errContent    = $e->getMessage();
        $errContent    = json_encode($debug, JSON_UNESCAPED_UNICODE);
//        var_dump($errContent, $appConf);die;

        $needSaveTypes = !empty($appConf['log']) ? $appConf['log']['needSaveTypes'] : [ 'ErrRedis', 'PDOException', 'RedisException', 'Exception', 'Throwable' ];

        # 记录日志
        if ( empty($needSaveTypes) || in_array($type, $needSaveTypes) ) {
            $logPath = 'error' . DS . date('Y-m') . DS . date('m-d') . DS;
            $logFile = $logPath . $type;
            if ( !empty($appConf['isInit']) ) {
                \App\Utils\File\Logger::write($errContent, $logFile, \App\Utils\File\Logger::LEVEL_ERR);
            }
        }

    }
}

if (!function_exists('randPassword')) {
   function randPassword()
    {
        $randStr = str_shuffle('abcdefghijklmnopqrstuvwxyz1234567890!@#$%^&*()');
        return substr($randStr, 0, 8);
    }
}

if (!function_exists('randCode')) {
   function randCode()
    {
        $randStr = str_shuffle('abcdefghijklmnopqrstuvwxyz1234567890!@#$%^&*()');
        return substr($randStr, 0, 4);
    }
}

if (!function_exists('randGiftCode')) {
   function randGiftCode()
    {
        $str      = 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randStr1 = str_shuffle($str);
        $randStr2 = str_shuffle($str);
        $randStr3 = str_shuffle($str);
        $randStr4 = str_shuffle($str);
        return substr($randStr1, 0, 1).substr($randStr2, 0, 1).substr($randStr3, 0, 1).substr($randStr4, 0, 1);
    }
}

if (!function_exists('randDomainCode')) {
   function randDomainCode()
    {
    	$str      = 'abcdefghijklmnopqrstuvwxyz1234567890';
    	$randStr1 =	str_shuffle($str);
        $randStr2 = str_shuffle($str);
        $randStr3 = str_shuffle($str);
        $randStr4 = str_shuffle($str);
        $randStr5 = str_shuffle($str);
        $randStr6 = str_shuffle($str);
        return substr($randStr1, 0, 1).substr($randStr2, 0, 1).substr($randStr3, 0, 1).substr($randStr4, 0, 1).substr($randStr5, 0, 1).substr($randStr6, 0, 1);
    }
}

if (!function_exists('randNavigationCode')) {
   function randNavigationCode()
    {
        $str      = 'abcdefghijklmnopqrstuvwxyz1234567890';
        $randStr1 = str_shuffle($str);
        $randStr2 = str_shuffle($str);
        $randStr3 = str_shuffle($str);
        $randStr4 = str_shuffle($str);
        $randStr5 = str_shuffle($str);
        $randStr6 = str_shuffle($str);
        return substr($randStr1, 0, 1).substr($randStr2, 0, 1).substr($randStr3, 0, 1).substr($randStr4, 0, 1).substr($randStr5, 0, 1).substr($randStr6, 0, 1).substr($randStr6, 0, 1).substr($randStr6, 0, 1);
    }
}


// 真实IP
if (!function_exists('real_ip')) {
    function real_ip()
    {
        return getRealIP();
    }
}

if (!function_exists('getRealIP')) {
	function getRealIP()
	{
	    static $realip = NULL;

	    if ($realip !== NULL) {
	        return $realip;
	    }

	    if (isset($_SERVER)) {
	        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

	            foreach ($arr AS $ip) {
	                $ip = trim($ip);

	                if ($ip != 'unknown') {
	                    $realip = $ip;
	                    break;
	                }
	            }
	        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
	            $realip = $_SERVER['HTTP_CLIENT_IP'];
	        } else {
	            if (isset($_SERVER['REMOTE_ADDR'])) {
	                $realip = $_SERVER['REMOTE_ADDR'];
	            } else {
	                $realip = '0.0.0.0';
	            }
	        }
	    } else {
	        if (getenv('HTTP_X_FORWARDED_FOR')) {
	            $realip = getenv('HTTP_X_FORWARDED_FOR');
	        } elseif (getenv('HTTP_CLIENT_IP')) {
	            $realip = getenv('HTTP_CLIENT_IP');
	        } else {
	            $realip = getenv('REMOTE_ADDR');
	        }
	    }
	    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
	    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

	    return $realip;
	}
}

if (! function_exists('getMonthStartEnd')) {
    function getMonthStartEnd($date=false)
    {
        if(!$date){
            $date = date('Y-m-d');
        }
       $fistday = date('Ym01',strtotime($date));
       $lastday = date('Ymd',strtotime("$fistday +1 month -1 day"));
       $fisttime = date('Y-m-01',strtotime($date)).' 00:00:00';
       $lasttime = date('Y-m-d',strtotime("$fistday +1 month -1 day")).' 23:59:59';
       
       return [$fisttime,$lasttime,$fistday,$lastday];
    }
}

if (! function_exists('getWeekStartEnd')) {
    function getWeekStartEnd($date=false)
    {
        //当前日期

        if(!$date){
            $sdefaultDate = date("Y-m-d");
        } else {
            $sdefaultDate = $date;
        }
        
        //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $first=1;

        //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $w=date('w',strtotime($sdefaultDate));

        //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $week_start=date('Y-m-d 00:00:00',strtotime("$sdefaultDate -".($w ? $w - $first : 6).' days'));

        //获取日期不带时间
        $weekstart = date('Ymd',strtotime("$sdefaultDate -".($w ? $w - $first : 6).' days'));

        //本周结束日期
        $week_end=date('Y-m-d 23:59:59',strtotime("$week_start +6 days"));

        $weekend = date('Ymd',strtotime("$week_start +6 days"));

        return [$week_start, $week_end ,$weekstart,$weekend];
    }
}

if (! function_exists('runtime')) {
    /**
     * 获取运行时间
     * @param null $startMt
     * @param null $endMt
     * @param bool $showMs
     * @return string
     * @author benjamin
     */
    function runtime ( $startMt = null, $endMt = null, $showMs = false ) {
        $startMt = !empty($startMt) ? $startMt : LARAVEL_START;
        $endMt   = !empty($endMt) ? $endMt : microtime(true);
        $diff    = ($endMt - $startMt) * 1000; // ms
        return number_format($diff, 2, '.', '') . ($showMs ? ' ms' : '');
    }
}

if (! function_exists('arraySort')) {
    /**
     * @desc php二维数组排序 按照指定的key 对数组进行排序
     * @param array $arr 将要排序的数组
     * @param string $keys 指定排序的key
     * @param string $type 排序类型 asc | desc
     * @return array
     */
    function arraySort($arr, $keys, $type = 'asc') {
        if ( !empty($arr) ) {
            $keyArray = [];
            foreach ( $arr as $value ) {
                $keyArray[] = $value[$keys];
            }
            if ( strtolower($type) == 'asc' ) {
                array_multisort($keyArray, SORT_ASC, $arr);
            } else {
                array_multisort($keyArray, SORT_DESC, $arr);
            }
        }
        return $arr;
    }
}

if(!function_exists('price_format')) {
    /**
     * @title  金额格式化
     */
    function price_format ( $num = 0, $dotnum = 2 ) {
        $num = str_ireplace(',', '', $num);
        return bcmul($num, 1, $dotnum);
    }
}

if(!function_exists('consoleLog')) {
    /**
     * @title 控制台日志
     * @param string $msg
     * @param array  $data
     * @author benjamin
     */
    function consoleLog ( $msg = '', $data = [] ) {
//        if ( defined('CLI_MODE') && CLI_MODE ) {
            echo "\r\n";
            if ( !empty($msg) ) {
                echo $msg;
            }

            echo "\r\n";
            if ( $data ) {
                var_dump($data);
            }
//        }
    }
}

if(!function_exists('getCombinationBets')) {
    /**
     * @title 控制台日志
     * @param string $msg
     * @param array  $data
     * @author benjamin
     */
    // 获取复式投注号码数组
    function getCombinationBets($balls){
        $t = func_get_args();

        if (func_num_args() == 1) {
            return call_user_func_array(__FUNCTION__, $t[0]);
        }

        $a = array_shift($t);
        if (!is_array($a)) {
            $a = array($a);
        }

        $a = array_chunk($a, 1);
        do {
            $r = array();
            $b = array_shift($t);
            if (!is_array($b)) {
                $b = array($b);
            }

            foreach ($a as $p) {
                foreach (array_chunk($b, 1) as $q) {
                    $r[] = array_merge($p, $q);
                }
            }

            $a = $r;
        } while ($t);

        return $r;
    }
}

if ( !function_exists('key_gen') ) {
    function
    key_gen ( $signType, $signAry ) {
        //���ǩ��
        if ( $signType == '0' ) {
            $datask28sagh21gdsAry = array(
                'username' => (string)strtolower($signAry['username']),
                'coin'     => (string)price_format($signAry['user_balance'], 4),
            );
            $dataSign             = jhdghgwy8dswgqw2fgfasdkh($datask28sagh21gdsAry, '0');
            //ע��ǩ��
        } elseif ( $signType == '1' ) {
            $datask28sagh21gdsAry = array(
                'uid'         => (string)$signAry['uid'],
                'lott_id'        => (string)$signAry['lott_id'],
                'played_group_id' => (string)$signAry['played_group_id'],
                'played_id'    => (string)$signAry['played_id'],
                'bet_issue'    => (string)$signAry['bet_issue'],
                'bet_data'  => (string)$signAry['bet_data'],
                'bet_time'  => (string)$signAry['bet_time'],
                'group_name'   => urlencode((string)$signAry['group_name']),
                'bet_day'   => (string)$signAry['bet_day'],
                'bet_ip'    => (string)$signAry['bet_ip'],
                'odds'        => (string)price_format($signAry['odds'], 4),
                'rebate'      => (string)price_format($signAry['rebate'], 4),
                'money'       => (string)price_format($signAry['money'], 2),
                'total_nums'   => (string)$signAry['total_nums'],
                'total_money'  => (string)price_format($signAry['total_money'], 2),
                'bet_info'     => (string)$signAry['bet_info'],
                'is_tester'    => (string)$signAry['is_tester'],
            );
            $dataSign             = jhdghgwy8dswgqw2fgfasdkh($datask28sagh21gdsAry, '1');
            //UG�ϻ���ǩ��
        } elseif ( $signType == '2' ) {
            $datask28sagh21gdsAry = array(
                'uid'        => (string)$signAry['uid'],
                'is_tester'   => (string)$signAry['is_tester'],
                'gameId'     => (string)$signAry['gameId'],
                'betMoney'   => (string)price_format($signAry['betMoney'], 4),
                'bet_time' => (string)$signAry['bet_time'],
                'bet_ip'   => (string)$signAry['bet_ip'],
            );
            $dataSign             = jhdghgwy8dswgqw2fgfasdkh($datask28sagh21gdsAry, '2');
            //��Ա��Ϣǩ��
        } elseif ( $signType == '3' ) {
            $datask28sagh21gdsAry = array(
                'is_tester'     => (string)$signAry['is_tester'],
                'password'     => (string)$signAry['password'],
                'name'         => urlencode((string)$signAry['name']),
                'username'     => (string)strtolower($signAry['username']),
                'coinPassword' => (string)$signAry['coinPassword'],
                'uid'          => (string)$signAry['uid'],
            );
            $dataSign             = jhdghgwy8dswgqw2fgfasdkh($datask28sagh21gdsAry, '3');
            //�������
        } elseif ( $signType == '4' ) {
            $datask28sagh21gdsAry = array(
                'password' => (string)$signAry['password'],
            );
            $dataSign             = jhdghgwy8dswgqw2fgfasdkh($datask28sagh21gdsAry, '4');
            //��Ա���ǩ��
        } elseif ( $signType == '5' ) {
            $datask28sagh21gdsAry = array(
                'username'    => (string)strtolower($signAry['username']),
                'coin'        => (string)price_format($signAry['coin'], 4),
                'usernameMd5' => md5((string)strtolower($signAry['username'])),
                'uid'         => (string)$signAry['uid'],
                'coinMd5'     => md5((string)price_format($signAry['coin'], 4)),
            );
            $dataSign             = jhdghgwy8dswgqw2fgfasdkh($datask28sagh21gdsAry, '3');
            //ע��ǩ��
        } elseif ( $signType == '6' ) {
            $datask28sagh21gdsAry = array(
                'played_id'       => (string)$signAry['played_id'],
                'played_group_id' => (string)$signAry['played_group_id'],
                'bet_issue'       => (string)$signAry['bet_issue'],
//                'rebate'      => (string)price_format($signAry['rebate'], 4),
                'money'           => (string)price_format($signAry['money'], 2),
                'bet_data'        => (string)$signAry['bet_data'],
                'bet_info'        => (string)$signAry['bet_info'],
                'bet_day'         => (string)$signAry['bet_day'],
                'bet_ip'          => (string)$signAry['bet_ip'],
                'is_tester'       => (string)$signAry['is_tester'],
                'total_nums'      => (string)$signAry['total_nums'],
                'total_money'     => (string)price_format($signAry['total_money'], 2),
                'bet_time'        => (string)$signAry['bet_time'],
                'uid'             => (string)$signAry['uid'],
                'lott_id'         => (string)$signAry['lott_id'],
                'group_name'      => urlencode((string)$signAry['group_name']),
                'odds'            => (string)price_format($signAry['odds'], 4),
            );
            $dataSign             = jhdghgwy8dswgqw2fgfasdkh($datask28sagh21gdsAry, '6');
        } elseif ( $signType == '7' ) {
            $datask28sagh21gdsAry = array(
                'uid'               => (string)$signAry['uid'],
                'sport_id'          => (string)$signAry['sport_id'],
                'league_id'         => (string)$signAry['league_id'],
                'match_id'          => (string)$signAry['match_id'],
                'market_group_id'   => (string)$signAry['market_group_id'],
                'market_id'         => (string)$signAry['market_id'],
                'is_inplay'         => (string)$signAry['is_inplay'],
                'bet_type'          => (string)$signAry['bet_type'],
                'bet_time'          => (string)$signAry['bet_time'],
                'bet_ip'            => (string)$signAry['bet_ip'],
                'odds'              => (string)price_format($signAry['odds'], 4),
                'money'             => (string)price_format($signAry['money'], 2),
                'market_group_code' => (string)$signAry['market_group_code'],
                'bet_flag'          => (string)$signAry['bet_flag'],
                'bet_value'         => (string)$signAry['bet_value'],
                'bet_content'       => (string)$signAry['bet_content'],
                'bet_extend'        => (string)$signAry['bet_extend'],
            );
            $dataSign             = jhdghgwy8dswgqw2fgfasdkh($datask28sagh21gdsAry, '6');
        }

        return $dataSign;
    }
}

if(!function_exists('jhdghgwy8dswgqw2fgfasdkh')) {
    function jhdghgwy8dswgqw2fgfasdkh($ary, $keyIndex){
        $keys = array(
            0		=> 'yNJH9meh4szuItHH4H43Cnjmp3eUsmu0',
            1		=> 'kihXLmtnnkbnOkKhSuLShDbN3bSUbIot',
            2		=> 'muNgJGkltD7nZKn0tGfDr7U1hnzZ7NAT',
            3		=> '2bBCZ7ECgXHZ712TckX7c7C53e76HY7G',
            4		=> 'z7MZxdowW7Mz9l6Ll936aaSliBXW4z9d',
            5		=> 'ZknN466njGKgu6CKNpK6zjzjv1hG63uz',
            6		=> 'sYFn1HIfz4D24n1dvk0bH4kfKkFFFHFk',
            7		=> 'c4wWCzZw5quiwatUUhInN9O4Nu8wjBNc',
            8		=> '18opofOj4qybPqCP8bLz31Noj32LnB28',
            9		=> '5FBf23s7jSHb0JiH5SsuJ12bb6I9bbbd',
            10		=> 'OGfZzCqc8foDC80d6N897Qvx69cnGcGx',
            11		=> '4LMYaAEym2YWwEb00yFJ92O44c4j5WA9',
            12		=> 'KwmEpMJO3mES5GNgNCEGgg5kK5CcmSK5',
            13		=> 'KM9h8M3q93kXv9mjeLhVxXHe58m5mpQ9',
            14		=> 'm2y1BI1OtO6Y6Ohoy2Y61HOUMOoT1Ibu',
            15		=> '60BsD1Lg1Bl1gxi6vCjW6NVxFLCtVSJS',
            16		=> 'Wl8gTtO550Zw185VR0f0zJjO8WaalF8V',
            17		=> 'Oxf3FSEtGEFMETvh3vTOO4ToHELXRo3n',
            18		=> 'VslhP7VpIPlxlF56FOfLiu2p5FA7hsp3',
            19		=> '46Xx04UH0XX10eZ4489U7BagGb41bEwA',
            20		=> 'vl35ZGyX320s035y59hgHLh3209Lz1Tk',
            21		=> 'fA1rCam91d6z9aEa2hxd9Mh9RrmDl9D2',
            22		=> 'sO08Rp8j5rz0b8C023xs0xbzbb80Ponv',
            23		=> '4gEYfmfz3dYE777bbddb77gydyFhW3Me',
            24		=> 'qJOQw3j1xxOq3Xh1oNXo1mOMOeqQ1wQp',
            25		=> 'lqJl7RxlpkMKXrfR1LZhlE5b191fP9Zf',
            26		=> 'v6JInw6CuivOnCq6PrOVrIIVC3wcCCi3',
            27		=> 'quI26b9Q1o16oAHRQtG86c6h1ueoR6C1',
            28		=> 'k052E5X2a7764cE6pB0EKy75X8c7K6EH',
            29		=> 'SrG0BHq0RGcKzOGQlzHGK4YBgSORq4lD',
            30		=> 'Naa9D6zD82d0zudab5DDW65d20n2ct69',
            31		=> 'Zt9TnnU5BR5iU431RuF1166bB9w11n5b',
            32		=> 'MFUsoLuzZb0FF6fUM1uM0ZsPbWSGGpfF',
            33		=> '6t6q00M77z9tcf23d7B6m69mP0Mxd707',
            34		=> 'i0m8q0JVjIXV5cL1Ctl8vqpjpiQ8IlQT',
            35		=> 'aV8q4LTicCc0DbwD98ttQ4tYBA00Lwli',
            36		=> 's1VA7ESw3sSg0S1EpSeMw3mp7C1E70s9',
            37		=> 'S9dZOa4D9SUTOUAoD556DDS6Ud44dz4g',
            38		=> 'rq41JlPYzR71Exe377w0WWo3E1wEo1DY',
            39		=> '7OxBo7O14gH674foggAbFX7B6abd7h1a',
            40		=> 'SSBssSssX9V9VSXQVrbmjmY9JSTsSbTv',
            41		=> '55ZzOxdvOa5Bp2prYO52zoEWwNXR5aWO',
            42		=> 'ZCCwHDwRCzEXxgelh4ixcaX10R6dIdHa',
            43		=> 'TPvT0TIpzgLLhhIMPLWgwHgLg6WhGveG',
            44		=> 'QnkOf88O59H53oAg8XAfFuU53aFdFVlq',
            45		=> 'pLSbZ1LW6rJYj7lj41R7jI5517itleTJ',
            46		=> 'hRy9077Rxtt8YHMyf20R8tb7Ke78KET0',
            47		=> 'r5f6cxfTtJjf6jzc55661Ta5QvIC61f6',
            48		=> 'vvx5r47688x8551a87RzQ76A4VxX8TKr',
            49		=> 'VAJ71Wbv47W11G440T11014taS7aS01A',
            50		=> '31in1w31233Op4PPOkih3N12KH2111jN',
            51		=> 'l1xss1DiDXDDzD1lwkjAIi2K5zEIed1l',
            52		=> 'aIL2VV73JVy7Y0y35ib5dRD2t02A7320',
            53		=> 'tUv4223nGm4JEmVCete3GutfmjcpCjnn',
            54		=> 'c99qnBPf0B44400P4pS0f3aNOBOsNSOP',
            55		=> 'dd1BFBb44v7VNbf199A1ybZaz594CaZg',
            56		=> '8npNEyMS70e849ni88zqv09z8slzLLVq',
            57		=> 'GG0MeNEPz0gp0Ej6PEPJnNUE61e16Pf1',
            58		=> 'v7Zib3B0ly7V0bVv7wuUE7zElLEYUb2b',
            59		=> 'gCEeGyJCJZAmj4neN3YZJUYMjogycvcN',
            60		=> '5245vc4bvc482v25544Z5Zx0bB30l283',
            61		=> 'RO8YcEp6RJLb8ZryKzk0JszeB6bTtwsC',
            62		=> '2V6TV6Z32Xo3GZhvXC3B33BEX36EsH00',
            63		=> '6jTtggDI81mtm7t8TT9TIJz8b6I8bbq7',
            64		=> 'QNXvjZWXJeJ0J21x00T88wv1YY2311zf',
            65		=> '55m4lR5kurigRreuniMr2ig5l4u4N9U5',
            66		=> '2ikz4t8QtJK1ZLG4Gjk1t4q576qEp165',
            67		=> '1OJDyn11g7Xg7ojSCJD5ND7g17CQfXjc',
            68		=> 'wxX7Fj5XkKXW65jn0J9hDK2wNx7kxJNH',
            69		=> 'KUYG55uRAv5ROukA5zEM5O7ot1vKOkGx',
            70		=> 'gXtZ22TZ3tpZXxTQN3XP9PNnTxt9twTN',
            71		=> 'JD5eeJ7qu0CJitOZzhEjQQQq0hKdtNoT',
            72		=> 'HRnrOHmnsOyxtUy71XA8X4HY8Y8qms1o',
            73		=> 'sDKPsS0PnKoVvPh69St0DR7o6ZvcCdoO',
            74		=> 'NyUA1UAUG9wZ9azbEkA16YU6Zz29D2ay',
            75		=> 'YfdyGZ1ME1NNFLFGZLxeLkLzXr1xTxNY',
            76		=> '6W3792Q5z36WS2AuAUDXp9Psdqx7hs99',
            77		=> 'KOu42YVY8HOxOwohXV8y4rVQkhHZ1Qbo',
            78		=> '4Dc45EEqK24vcjE4PQgDZcd4stZGcG46',
            79		=> 'i73RNN61o7i6xGtNzGdI1L7il3G6XitN',
            80		=> '6bQB8yyyVo5NB6cNAJ5M8yPDo5ym5Yf3',
            81		=> 't9S55L4Z9ldSooGT89Xod4Xw31MMYxZ9',
            82		=> 'NHoKKQ5Ne44oC9Oc5NkOJv5SCcOEkOuY',
            83		=> 'JD9P6S6SfA67xsR6R17LpCxEj38Nn61N',
            84		=> 'bRBw6bgUW0lBZW01W0w0WurlFXHuIiwB',
            85		=> '3cdWxIvWwVXCVkcwXkWkgXcxxcCGXgK0',
            86		=> '0Jy02aYY0VEXeexE2x0rZWEJYd77gJr0',
            87		=> 'W3xpL8ffeQzlIw3C83fFeFwf3lWa66Wi',
            88		=> 'iCYPR7H8paAwAYcrEC8CcGC57C5I7hC5',
            89		=> 'SCIOXKYOfO6sQO8sO1hSckK06oSy886h',
            90		=> 'hZcQ08faFR9hh8htRVY7hRGB0c0xCza9',
            91		=> 'fTh0D1wQfaNOF3ic2qnRrR43r1QFN003',
            92		=> '3OO176OAE7o37E3neEUu4U4nra4eLUz7',
            93		=> '722KBS5X2krKRMY5sR7b70zS5sy0T2Ks',
            94		=> '3TjjDciT6p6dc2pcDZT5mIJd5CpClpla',
            95		=> 'E83dE3EBQqum8e8S3SQ4mMU2S4ZmdZgg',
            96		=> '8ZRH890rRRUrzP2TNjrrhnR6jpA2Ut5H',
            97		=> '3vq3sBbbBFBvFVvzQ5pVB9cB7bvSYbBk',
            98		=> '63jN7O3VXrNoEJjs8j73olr7r3884ev8',
            99		=> 'cnb9ccCz78cxVix6vxR4b8i6b04c6C68',
        );
        if (!is_array($ary) || !$keys[$keyIndex]){
            return false;
        }
        $sign = md5(http_build_query($ary).$keys[$keyIndex]);
        return $sign;
    }
}



