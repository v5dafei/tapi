<?php

/**
 * @title  数据验证器
 * @link   http://www.runoob.com/php/php-ref-filter.html    PHP Filter 系列函数
 * @link   https://www.php.net/manual/zh/ref.ctype.php      PHP ctype 系列函数
 * @link   http://www.runoob.com/regexp/regexp-syntax.html  正则基础语法
 * @author benjamin
 */

namespace App\Utils;

use App\Exceptions\ErrMsg;
use Exception;
use App\Utils\Str\StrHelper;
use Core\Plugin;


/**
 * 数据验证器使用说明 - 提供：单数据验证，多数据验证（数组）
 * @tips  string  check             验证方法：支持多验证,竖线隔开（必填）
 * @tips  bool    canEmpty          是否为空：为空时有值就验证,无值不验证（非必填, 默认不能为空）
 * @tips  string  msgPrefix         消息前缀：提示对用户的友好度，在具体错误描述前加上消息前缀（必填）
 * @tips  int     min\max\len       数据长度：在长度区间内或者指定长度（非必填）（需确定方法内部是否支持）
 * @tips  int     than\lessThan     数据大小：是否大于小于指定数值（非必填）（需确定方法内部是否支持）
 * @tips  array   in\notIn          集合验证：是否属于某个集合 （非必填）（需确定方法内部是否支持）
 * @tips  mixin   default           默认数据：参数未填或已填未传值时，填充默认数据 （非必填）（只支持多参数验证）
 * @tips  展开或隐藏内容：phpstorm => ctrl + shift + (+|-) 只显示方法名和注释
 */
class Validator
{
    private static $instance      = null;
    private static $checkedParams = [];
    private static $searchRules   = [];

    /**
     * @title 验证结果描述
     * @var string
     */
    private static $msg       = '';
    public static  $msgPrefix = '';

    /**
     * @title 当前验证状态
     * @var string
     */
    private static $curCheckType   = '';
    private static $curCheckStatus = true;

    /**
     * @title 参数白名单
     * @var array
     */
    private static $whiteList = [
//        'page',
//        'rows',
    ];

    public static function init () {
        self::$msg            = '';
        self::$msgPrefix      = '';
        self::$curCheckType   = '';
        self::$curCheckStatus = true;
        self::$checkedParams  = [];
    }

    /**
     * @title 单例调用
     * @return Validator
     */
    private static function getInstance () {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @title 使用示例
     * @throws \Exception
     */
    private static function demo () {
        $data = [
            'usr'    => 'fhuser',
            'pwd'    => md5('147258'),
            'gen'    => '男',
            'amount' => '-100',
        ];

        $rules = [
            'usr'    => [ 'check' => 'isUsr', 'min' => '6', 'max' => '20', 'msgPrefix' => '用户名' ],
            'pwd'    => [ 'check' => 'isPwd', 'len' => '32', 'msgPrefix' => '密码' ],
            'gen'    => [ 'check' => 'inArray', 'in' => [ '男', '女' ], 'msgPrefix' => '性别' ],
            'age'    => [ 'check' => 'inRange', 'min' => 18, 'max' => 100, 'canEmpty' => true, 'msgPrefix' => '性别' ],
            'amount' => [ 'check' => 'isAmount', 'than' => 0, 'msgPrefix' => '交易金额' ],
        ];

        if ( self::validate($data, $rules) ) {
            self::error(self::getErrMsg());
        }

        # 已验证数据
        $checedParams = self::getCheckedParams();
    }

    /**
     * @title 获取校验结果描述
     * @return string
     */
    public static function getErrMsg () {
        $msgPrefix = (self::$msgPrefix ? self::$msgPrefix . ': ' : (self::$curCheckType ? '请求参数 ' . self::$curCheckType : ''));
        return $msgPrefix . self::$msg;
    }

    /**
     * @title 获取通过校验的参数
     * @tips  只会留下 $data 内声明的参数，额外参数被略过
     */
    public static function getCheckedParams () {
        return self::$checkedParams;
    }

    /**
     * @title 获取特殊搜索规则字段
     * @tips  只会留下带 search 规则的参数配置
     */
    public static function getSearchRules () {
        return self::$searchRules;
    }

    /**
     * @title 多数据校验
     * @param array $data
     * @param array $rules
     * @return bool
     * @throws ErrMsg
     */
    public static function validate ( array $data, array $rules ) {
        // todo: 校验公共参数

        self::$curCheckType = '';

        // 只校验配置的属性和数据
        foreach ( $rules as $attr => $options ) {

            if ( in_array($attr, self::$whiteList) ) {
                continue;
            }
            if ( !is_array($options) || empty($options['check']) ) {
                self::error('数据校验器参数异常：attr：' . $attr . ' 配置异常!');
            }

            # 多校验方式，以 | 隔开
            $methods = strpos($options['check'], '|') !== false ? explode('|', $options['check']) : array( $options['check'] );

            # 除了数组校验方式，其它一律只能传递字符串
            if ( isset($data[$attr]) && is_array($data[$attr]) && !in_array('isArray', $methods) ) {
                self::error('数据校验器参数异常：' . $attr . '：只能是字符串或者数字!');
            }

            // 额外的请求参数 例: action = 'login123' 已取消此验证
            // if ($attr === 'action' && (empty($data[$attr]) || $data[$attr] !== $options['check'])) {
            //     self::error('不被支持的操作：' . $options['check']);
            // }

            # 校验方法是否存在
            foreach ( $methods as $method ) {
                if ( $attr !== 'action' && !method_exists(self::getInstance(), $method) ) {
                    self::error('数据校验器参数异常：校验方法：' . $method . ' 不存在!');
                }
            }

            self::$msgPrefix    = $options['msgPrefix'] ? $options['msgPrefix'] : '';
            self::$curCheckType = $attr . ':';

//            var_dump(self::$curCheckType);

            # 未传递对应参数，且参数可以为空 > 跳过
            if ( !isset($data[$attr]) && (isset($options['canEmpty']) && $options['canEmpty']) ) {
                # 填充默认数据，继续走验证
                if ( isset($options['default']) ) {
                    $data[$attr] = $options['default'];
                } else {
                    continue;
                }
            } else if ( !isset($data[$attr]) && empty($options['canEmpty']) ) { // 未传递参数，且参数不能为空 结束循环并报错
                # 填充默认数据，继续走验证
                if ( isset($options['default']) ) {
                    $data[$attr] = $options['default'];
                } else {
                    self::$msg            = ' 参数未传递!';
                    self::$curCheckStatus = false;
                    break;
                }
            }

            # 参数值为空，且允许为空 > 跳过
            $data[$attr] = !is_array($data[$attr]) ? trim($data[$attr]) : $data[$attr];
            if ( self::isEmpty($data[$attr]) && (isset($options['canEmpty']) && $options['canEmpty']) ) {
                if ( isset($options['default']) ) self::$checkedParams[$attr] = $options['default'];
                continue;
            } else if ( self::isEmpty($data[$attr]) ) { // 参数值为空，且不能为空 结束循环并报错
                self::$msg            = ' 不能为空!';
                self::$curCheckStatus = false;
                break;
            }

            # 删除不需校验的方法
            if ( array_search('canEmpty', $methods) ) {
                unset($methods['canEmpty']);
            }
            if ( array_search('action', $methods) ) {
                unset($methods['action']);
            }

            # 数据校验
            foreach ( $methods as $check ) {
                if ( !self::$check($data[$attr], $options) ) {
                    self::$curCheckStatus = false;
                    self::$msg            = !empty($options['msg']) ? $options['msg'] : self::$msg;
                    break;
                } else {
                    self::$curCheckStatus = true;
                }
            }

            # 工具类出现问题？ 请开此调试开关
//            var_dump([
//                'methods' => join(',', $methods),
//                'value'   => $data[$attr],
//                'result'  => self::$curCheckStatus ? '成功' : '失败',
//                'msg'     => self::getErrMsg()
//            ]);

            if ( !self::$curCheckStatus ) {
                break;
            }

            # 已校验参数及规则
            self::$checkedParams[$attr] = $data[$attr];

            # 特殊搜索字段规则
            if ( !empty($options['search']) ) {
                self::$searchRules[$attr] = $options['search'];
            }

        }

        if ( self::$curCheckStatus ) {
            self::$curCheckType = '';
        }

        return self::$curCheckStatus;
    }

    /**
     * @title 必须有值
     * @param string $val
     * @param array  $options
     * @return bool
     */
    public static function required ( $val = '', array $options = [] ) {
        if ( self::isEmpty($val, $options) ) {
            return false;
        }
        if ( !self::length($val, $options) ) {
            return false;
        }
        return true;
    }

    /**
     * @title 是否为空
     * @tips  除了NULL、空字符串、空数组，其他都算有值
     */
    public static function isEmpty ( $val = '', array $options = [] ) {
        if ( is_array($val) ) {
            if ( empty($val) ) {
                self::$msg = '不能为空!';
                return true;
            }
        } else if ( is_string($val) ) {
            $val = trim($val);
            if ( is_null($val) || $val === '' ) {
                self::$msg = '不能为空!';
                return true;
            }
        }

        return false;
    }

    /**
     * @title 检验字符长度
     * @param string $val
     * @param array  $options
     * @return bool
     */
    public static function length ( $val = '', array $options = [] ) {


        if ( !isset($options['min']) && !isset($options['max']) ) {
            return true;
        }

        // $match = '[\x{4e00}-\x{9fa5}A-Za-z0-9]';
        $match = '[\w\W]';
        if ( isset($options['min']) && !isset($options['max']) ) {
            if ( !preg_match("/^{$match}{" . $options['min'] . ',' . "}$/u", $val) ) {
                self::$msg = "长度最少为：{$options['min']}位数! ";
                return false;
            }
        }
        if ( !isset($options['min']) && isset($options['max']) ) {
            if ( !preg_match("/^{$match}{0," . $options['max'] . "}$/u", $val) ) {
                self::$msg = "长度最大为：{$options['min']}位数! ";
                return false;
            }
        }
        if ( isset($options['min']) && isset($options['max']) ) {
            if ( !preg_match("/^{$match}{" . $options['min'] . ',' . $options['max'] . "}$/u", $val) ) {
                self::$msg = "长度必须在 {$options['min']}位 - {$options['max']}位数 之间!";
                return false;
            }
        }
        return true;
    }

    /**
     * @title  数据大小比较
     * @param string $val
     * @param array  $options
     * @return bool
     * @author benjamin
     */
    public static function compare ( $val, array $options = [] ) {
        $options = array_merge([
            'than'     => null,      // 大于
            'lessThan' => null,      // 小于
            'egt'      => null,      // 大于等于
            'elt'      => null,      // 小于等于
        ], $options);

        # 大于等于 VS 小于等于 处理
        if ( !is_null($options['egt']) && !is_null($options['elt']) ) {
            if ( !($val >= $options['egt']) || !($val <= $options['elt']) ) {
                self::$msg = '必须在区间：' . $options['egt'] . '-' . $options['elt'] . ' 以内!';
                return false;
            }
        }
        if ( !is_null($options['egt']) && is_null($options['elt']) && $val < $options['egt'] ) {
            self::$msg = '必须大于等于' . $options['egt'];
            return false;
        }
        if ( !is_null($options['elt']) && is_null($options['egt']) && $val > $options['elt'] ) {
            self::$msg = '必须小于等于' . $options['elt'];
            return false;
        }

        # 大于 VS 小于 处理
        if ( !is_null($options['than']) && !is_null($options['lessThan']) ) {
            if ( !($val > $options['than']) || !($val < $options['lessThan']) ) {
                self::$msg = '必须在区间：' . $options['than'] . '-' . $options['lessThan'] . ' 以内!';
                return false;
            }
        }
        if ( !is_null($options['than']) && is_null($options['lessThan']) && $val <= $options['than'] ) {
            self::$msg = !empty($options['range']) ? '区间金额2必须大于区间金额1' : '必须大于' . $options['than'];
            return false;
        }
        if ( !is_null($options['lessThan']) && is_null($options['than']) && $val >= $options['lessThan'] ) {
            self::$msg = '必须小于' . $options['lessThan'];
            return false;
        }

        return true;
    }

    /**
     * @title 验证码校验
     * @param $val
     * @return bool
     */
    public static function captcha ( $val, array $options = [] ) {
        $options = array_merge([ 'len' => 4 ], $options);
        if ( !ctype_alnum($val) || strlen($val) !== $options['len'] ) {
            self::$msgPrefix = self::$msgPrefix ? self::$msgPrefix : '验证码：';
            self::$msg       = '为' . $options['len'] . '位字母与数字组合!';
            return false;
        }
        return true;
    }

    public static function slideCaptcha ( $val, array $options = [] ) {
        $options = array_merge([ 'len' => 4 ], $options);
        if ( !ctype_alnum($val) || strlen($val) !== $options['len'] ) {
            self::$msgPrefix = self::$msgPrefix ? self::$msgPrefix : '验证码：';
            self::$msg       = '为' . $options['len'] . '位字母与数字组合!';
            return false;
        }
        return true;
    }

    /**
     * @title 用户名校验
     * @param $val
     * @return bool
     */
    public static function isUsr ( $val, array $options = [] ) {
        $options = array_merge([ 'min' => 2, 'max' => 20, 'checkUpper' => true ], $options);
        if ( $options['checkUpper'] && self::isUpperCase($val, $options) ) {
            self::$msg = '必须为小写';
            return false;
        }
        if ( !preg_match('/^[a-zA-Z0-9_]{' . $options['min'] . ',' . $options['max'] . '}$/', $val) ) {
            self::$msgPrefix = self::$msgPrefix ? self::$msgPrefix : '用户名：';
            self::$msg       = '为 ' . $options['min'] . '-' . $options['max'] . ' 位字母与数字组成';
            return false;
        }
        return true;
    }

    /**
     * @title 混合用户名校验：手机、邮箱、用户名
     * @param $val
     * @return bool
     */
    public static function isMixinUsr ( $val, array $options = [] ) {
        $options = array_merge(
            [ 'min' => 3, 'max' => 20, 'isMobile' => false, 'isEmail' => false ],
            $options);

        if ( !empty($options['isMobile']) && self::isMobile($val) ) {
            return true;
        }

        if ( !empty($options['isEmail']) && self::isEmail($val) ) {
            return true;
        }

        if ( !self::isUsr($val, $options) ) {
            return false;
        }

        return true;
    }

    /**
     * @title 校验昵称
     */
    public static function isNickName ( $val, array $options = [] ) {
        $options = array_merge(['min' => 2, 'max' => 20], $options);

        # 正则校验
        $strlen = StrHelper::utf8_strlen($val);
        if ( !preg_match("/^[A-Za-z0-9_\x{4e00}-\x{9fa5}]*$/u", $val) || ($strlen<$options['min'] || $strlen>$options['max']) ) {
            self::$msg = '为' . $options['min'] . '到' . $options['max'] . '位数字或字母、汉字、下划线组成!';;
            return false;
        }

        return true;
    }

    /**
     * @title 密码校验
     * @param $val
     * @return bool
     */
    public static function isPwd ( $val, array $options = [] ) {
        $options = array_merge([ 'len' => 32 ], $options);
        if ( strlen($val) != $options['len'] ) {
            self::$msgPrefix = self::$msgPrefix ? self::$msgPrefix : '密码：';
            self::$msg       = "为{$options['len']}位加密字符串!";
            return false;
        }
        return true;
    }

    /**
     * @title  是否大写
     * @author benjamin
     */
    public static function isUpperCase ( $val, array $options = [] ) {
        if ( !preg_match('/[A-Z]+/', $val) ) {
            self::$msg = '必须为大写!';
            return false;
        }
        return true;
    }

    /**
     * @title  是否小写
     * @author benjamin
     */
    public static function isLowCase ( $val, array $options = [] ) {
        if ( !preg_match('/[a-z]+/', $val) ) {
            self::$msg = '必须为小写!';
            return false;
        }
        return true;
    }

    /**
     * @title 是否加密字符串：目前只验证长度
     * @param       $val
     * @param array $options
     * @return bool
     */
    public static function isHash ( $val, array $options = [] ) {
        $options = array_merge([], $options);

        # 检查长度区间
        if ( !self::length($val, $options) ) {
            return false;
        }

        # 指定长度
        if ( !empty($options['len']) && (strlen($val) != $options['len']) ) {
            self::$msgPrefix = self::$msgPrefix ? self::$msgPrefix : '密码：';
            self::$msg       = "为{$options['len']}位加密字符串!";
            return false;
        }
        return true;
    }

    /**
     * @title 邮箱校验
     * @param $val
     * @return bool
     */
    public static function isEmail ( $val, array $options = [] ) {
        $options = array_merge([], $options);
        if ( !filter_var($val, FILTER_VALIDATE_EMAIL) ) {
            self::$msgPrefix = self::$msgPrefix ? self::$msgPrefix : '邮箱地址：';
            self::$msg       = '邮箱地址不正确!';
            return false;
        }
        return true;
    }

    /**
     * @title 网址校验
     * @param $val
     * @return bool
     */
    public static function isUrl ( $val, array $options = [] ) {
        $options = array_merge([], $options);
        if ( !filter_var($val, FILTER_VALIDATE_URL) ) {
            self::$msgPrefix = self::$msgPrefix ? self::$msgPrefix : '网址链接：';
            self::$msg       = '网址链接不正确!';
            return false;
        }
        return true;
    }

    /**
     * @title 网址校验
     * @param $val
     * @return bool
     */
    public static function isIP ( $val, array $options = [] ) {
        $options = array_merge([], $options);
        if ( !filter_var($val, FILTER_VALIDATE_IP) ) {
            self::$msgPrefix = self::$msgPrefix ? self::$msgPrefix : 'IP地址：';
            self::$msg       = 'IP地址不正确!';
            return false;
        }
        return true;
    }

    /**
     * @title 手机号校验
     * @param $val
     * @return bool
     */
    public static function isMobile ( $val, array $options = [] ) {
        $options = array_merge([], $options);

        # 当前站点货币
        $currency = Plugin::getConfig('system', 'currency', 'CNY');
        if ( $currency == 'CNY' ) {
            $patten = '/^1[3456789]{1}[0-9]{9}$/';
        } else {
            $patten = '/^[0-9]{9,11}$/';
        }

        if ( !preg_match($patten, $val) ) {
            self::$msgPrefix = self::$msgPrefix ? self::$msgPrefix : '手机号：';
            self::$msg       = '格式不正确!';
            return false;
        }
        return true;
    }

    /**
     * @title qq号校验
     * @param $val
     * @return bool
     */
    public static function isQQ ( $val, array $options = [] ) {
        $options = array_merge([], $options);
        if ( !preg_match('/^[1-9][0-9]{4,19}$/', $val) ) {
            self::$msgPrefix = self::$msgPrefix ? self::$msgPrefix : 'QQ号：';
            self::$msg       = 'qq由5-20位数字组成!';
            return false;
        }
        return true;
    }

    /**
     * @title 是否为字母与数字的组合   (abc123)
     * @param $val
     * @return bool
     */
    public static function isAlnum ( $val, array $options = [] ) {
        if ( !ctype_alnum($val) ) {
            self::$msg = '必须为字母与数字组合!';
            return false;
        }
        return true;
    }

    /**
     * @title 是否为纯字母  (abc)
     * @param $val
     * @return bool
     */
    public static function isAlpha ( $val, array $options = [] ) {
        if ( !ctype_alpha($val) ) {
            self::$msg = '必须为字母!';
            return false;
        }
        return true;
    }

    /**
     * @title 校验整形数字（可以是字符串数字）
     * @param $val
     * @return bool
     */
    public static function isInt ( $val, array $options = [] ) {
        $options = array_merge([
            'than'     => null,      // 大于
            'lessThan' => null       // 小于
        ], $options);

        # 检查数字格式
        if ( !self::isNum($val) ) {
            self::$msg = '必须是一个数字!';
            return false;
        }

        # 正则检查是否整数
        if ( !preg_match('/^([-]?[1-9]+[0-9]*|0)$/', $val) ) {
            self::$msg = '必须是一个整数!';
            return false;
        }

        # 检查数字区间
        if ( !self::compare($val, $options) ) {
            return false;
        }
        return true;
    }

    /**
     * @title 校验浮点型数字 (1.00)
     * @param       $val
     * @param array $options
     * @return bool
     */
    public static function isFloat ( $val, array $options = [] ) {
        $options = array_merge([ 'decimal' => null ], $options);

        if ( !self::isNum($val) ) {
            self::$msg = '必须为数字!';
            return false;
        }

        if ( strpos($val, '.') === false ) {
            self::$msg = '必须是带小数点的数字!';
            return false;
        }

        if ( !empty($options['decimal']) ) {
            $decimal = explode('.', $val)[1];
            if ( strlen($decimal) !== $options['decimal'] ) {
                self::$msg = "必须是带{$options['decimal']}位小数点的数字!";
                return false;
            }
        }

        return true;
    }

    /**
     * @title 是否中文
     */
    public static function isChinese ( $val, array $options = [] ) {
        $options = array_merge([ 'min' => 2, 'max' => 10 ], $options);
        if ( !preg_match("/^[\x{4e00}-\x{9fa5}]{" . $options['min'] . ',' . $options['max'] . "}$/u", $val) ) {
            self::$msg = '为' . $options['min'] . '到' . $options['max'] . '位中文组成!';;
            return false;
        }
        return true;
    }

    /**
     * @title 是否中文
     * @since 1.0.1 支持中间点 - 周星·驰
     */
    public static function isCn ( $val, array $options = [] ) {
        $options = array_merge([ 'min' => 2, 'max' => 12 ], $options);

        # 检查点位置：只能出现在中间
        $indexL = mb_strpos($val, "·");
        $strrev = StrHelper::strrev($val);

        if ( $indexL !== false ) {
            # 检查中文字符串位置失败：转换其他思路
//            echo ($val . '-' . $index . '-' . (StrHelper::utf8_strlen($val) - 1)) . "\r\n";

            # 将字符串反转获取第一个字符
            $indexR = mb_strpos($strrev, "·");
            if ( $indexL === 0 || $indexR === 0 ) {
                self::$msg = '间隔符号·必须处于文字中间!';
                return false;
            }
        }

        # 正则校验
        if ( !preg_match("/^[\x{4e00}-\x{9fa5}\·]{" . $options['min'] . ',' . $options['max'] . "}$/u", $val) ) {
            self::$msg = '为' . $options['min'] . '到' . $options['max'] . '位中文组成!';;
            return false;
        }

        return true;
    }

    /**
     * @title 是否越南语
     */
    public static function isVn ( $val, array $options = [] ) {
        $options = array_merge([ 'min' => 3, 'max' => 60 ], $options);
        $val     = preg_replace('/ +/', ' ', $val);

        # 正则校验
        if ( !preg_match("/^[a-zÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂ ưăạảấầẩẫậắằẳẵặẹẻẽềềểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ]{" . $options['min'] . ',' . $options['max'] . "}$/i", $val) ) {
            self::$msg = '为' . $options['min'] . '到' . $options['max'] . '位字母组成!';;
            return false;
        }
        return true;
    }

    /**
     * @title 是否印度语
     */
    public static function isIndia ( $val, array $options = [] ) {
        $options = array_merge([ 'min' => 3, 'max' => 60 ], $options);
        $val     = preg_replace('/ +/', ' ', $val);

        # 正则校验
        if ( !preg_match("/^[\x{0900}-\x{097F}a-z]{" . $options['min'] . ',' . $options['max'] . "}$/iu", $val) ) {
            self::$msg = '为' . $options['min'] . '到' . $options['max'] . '位字母组成!';;
            return false;
        }
        return true;
    }

    /**
     * @title 校验真实姓名
     */
    public static function isRealName ( $val, array $options = [] ) {
        # 当前站点货币
        $currency = Plugin::getConfig('system', 'currency', 'CNY');

        if ( $currency == 'CNY' && !Validator::isCn($val, $options) )
            return false;

        if (
            $currency == 'VND' && !Validator::isVn($val, $options)
            || ($currency == 'INR' && !Validator::isIndia($val, $options))
        ) {
            return false;
        }


        return true;
    }

    /**
     * @title 校验日期和时间 (2019-05-20 | 2019-05-20 13:14:00)
     * @param string $date
     * @param array  $options
     * @return bool
     */
    public static function isDate ( $date, array $options = [] ) {
//        $formats = ['Y-m-d', 'Y-m-d H:i:s'];
        $options  = array_merge([ 'formats' => [ 'Y-m-d' ] ], $options);
        $unixTime = strtotime($date);

        // 校验日期的有效性，满足其中一个格式就行
        if ( $unixTime ) {
            foreach ( $options['formats'] as $format ) {
                if ( date($format, $unixTime) === $date ) {
                    return true;
                }
            }
        }

        self::$msg = '日期格式不正确!';
        return false;
    }

    /**
     * @title  是否时间戳 (1561360143)
     * @return bool
     */
    public static function isTimestamp ( $val, array $options = [] ) {
        $options = array_merge([ 'len' => 10 ], $options);
        if ( !self::isInt($val, $options) ) {
            return false;
        }
        if ( strlen($val) !== $options['len'] ) {
            self::$msg = '为10位长度时间戳!';
            return false;
        }
        return true;
    }

    /**
     * @title 检验是否为数字 (1 | '1' | 1.00)
     * 1：纯数字、字符串数字、浮点数 皆可
     * 2：php is_numeric 带+号 也会检验通过
     * @param string $num
     * @param array  $options
     * @return bool
     */
    public static function isNum ( $num, array $options = [] ) {
        $options = array_merge([ 'len' => 0 ], $options);

        if ( !is_numeric($num) || strpos($num, '+') !== false ) {
            self::$msg = '必须为数字!';
            return false;
        }

        if ( !empty($options['len']) && strlen($num) !== $options['len'] ) {
            self::$msg = "长度为{$options['len']}位!";
            return false;
        }

        return true;
    }

    /**
     * @title 检验是否为带区间的数字 (1 | 0-10)
     * @hint  支持整数、以及整数加分隔符
     * @param string $num
     * @param array  $options
     * @return bool
     */
    public static function isRangeNum ( $num, array $options = [] ) {
        $options = array_merge([], $options);
        if ( !preg_match('/^(\d+|[\d]+\-[\d]+)$/', $num) ) {
            self::$msg = '格式不正确，由数字以及中横线构成!';
            return false;
        }
        return true;
    }

    /**
     * @title 是否为间隔符隔开的字符 (,-_|)
     * @param string $str
     * @param array  $options
     * @return bool
     */
    public static function isIdStr ( $str, array $options = [] ) {
        $options             = array_merge([ 'connector' => ',-_|', 'hasConnectorInFirst' => true ], $options);
        $connector           = $options['connector'];
        $hasConnectorInFirst = $options['hasConnectorInFirst'];

        # 文字部分匹配规则
        $word = '[\x{4e00}-\x{9fa5}A-Z0-9a-z]';
        if ( $hasConnectorInFirst ) {
            # 首字母可以带连接符
            $match = "/^([{$connector}]?{$word}+)*$/u";
        } else {
            # 首字母不能带连接符
            $match = "/^{$word}+([{$connector}]?{$word}+)*$/u";
        }

        # demo
//        $str = '1,2,3';
//        $str = '1|2-|3,';
//        $str = '1/1';
//        $str = '/api/user/info/';
//        $str = 'user/dudu/';
//        $str = "user/dudu/hehe";

        if ( !preg_match($match, $str) ) {
            self::$msg = '格式不正确：由中文、数字、字母、加连接符号：[' . $connector . '] 组成';
            return false;
        }
        return true;
    }

    /**
     * @title 是否属于某个集合 in = [0, 1, 2]
     * @param       $val
     * @param array $options
     * @return bool
     */
    public static function inArray ( $val, array $options = [] ) {
        $options = array_merge([ 'in' => [] ], $options);
        if ( !in_array($val, $options['in']) ) {
//            self::$msg = '限定在集合 ' . json_encode($options['in'], JSON_UNESCAPED_UNICODE) . ' 以内';
            self::$msg = '限定在集合 [' . implode(',', $options['in']) . '] 以内';
            return false;
        }
        return true;
    }

    /**
     * @title 是否不属于某个集合 notIn = [0, 1, 2]
     * @param       $val
     * @param array $options
     * @return bool
     */
    public static function notInArray ( $val, array $options = [] ) {
        $options = array_merge([ 'notIn' => [] ], $options);
        if ( in_array($val, $options['notIn']) ) {
            self::$msg = '不能为 ' . json_encode($options['notIn'], JSON_UNESCAPED_UNICODE) . ' !';
            return false;
        }
        return true;
    }

    /**
     * @title 是否在某个区间内（和compare重合）
     * @param       $val
     * @param array $options
     * @return bool
     */
    public static function inRange ( $val, array $options = [] ) {
        $options = array_merge([], $options);
        if ( !empty($options['min']) && empty($options['max']) && $val < $options['min'] ) {
            self::$msg = '必须大于：' . $options['min'];
            return false;
        } elseif ( empty($options['min']) && !empty($options['max']) && $val > $options['max'] ) {
            self::$msg = '必须小于：' . $options['max'];
            return false;
        } elseif ( !empty($options['min']) && !empty($options['max']) ) {
            if ( $val < $options['min'] || $val > $options['max'] ) {
                self::$msg = '限定在区间 ' . $options['min'] . '-' . $options['max'] . ' 以内';
                return false;
            }
        }
        return true;
    }

    /**
     * @title 混合数据验证
     * @param       $val
     * @param array $options
     * @return bool
     */
    public static function isMixin ( $val, array $options = [] ) {
        $options = array_merge([], $options);

        if ( !self::length($val, $options) ) {
            return false;
        }

        if ( !preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9,_?]+$/u", $val) ) {
            self::$msg = "格式必须为：中文、字母、数字或其组合，不能含有特殊字符和空格!";
            return false;
        }

        return true;
    }

    /**
     * @title 交易金额验证 (10 | -10 | 10.00)
     * @param string $amount
     * @param array  $options
     * @return bool
     */
    public static function isAmount ( $amount, array $options = [] ) {
        $options = array_merge([
            'isFloat' => false,  // 是否浮点数
            'isInt'   => false,  // 是否整型
//            'than'     => 0,      // 大于
//            'lessThan' => 0       // 小于
        ], $options);

        if ( !self::isNum($amount) ) {
            return false;
        }
        if ( !empty($options['isFloat']) && !self::isFloat($amount, $options) ) {
            return false;
        }
        if ( !empty($options['isInt']) && !self::isInt($amount, $options) ) {
            return false;
        }
        if ( (isset($options['than']) || isset($options['lessThan'])) && !self::compare($amount, $options) ) {
            return false;
        }

        return true;
    }
    /**
     * @title 交易金额验证 (10 | -10 | 10.00)
     * @param string $amount
     * @param array  $options
     * @return bool
     */
    public static function isAmountRange ( $amount, array $options = [] ) {
        $options = array_merge([
            'isFloat' => false,  // 是否浮点数
            'isInt'   => false,  // 是否整型
//            'than'     => 0,      // 大于
//            'lessThan' => 0       // 小于
        ], $options);

        if ( strpos($amount, '-') !== false ) {
            list($amount1, $amount2) = explode('-', $amount);
            if ( $amount1 === $amount2 ) {
                return self::isAmount($amount1);
            } else {
                return (self::isAmount($amount1) && self::isAmount($amount2, [ 'than' => $amount1, 'range' => true]));
            }

        } else {
            return self::isAmount($amount);
        }
    }


    /**
     * @title 是否为 base64 加密
     * @param string $str
     * @param array  $options
     * @return bool
     */
    public static function isBase64 ( $str, array $options = [] ) {
        return $str == base64_encode(base64_decode($str)) ? true : false;
    }

    /**
     * @title 银行卡号校验
     * @param string $str
     * @param array  $options
     * @return bool
     */
    public static function isBankCard ( $str, array $options = [] ) {

        if ( !preg_match("/^[1-9]{1}(\d{14}|\d{15}|\d{16}|\d{17}|\d{18})$/", $str) ) {
            self::$msg = '格式不正确!';
            return false;
        }

        return true;
    }

    /**
     * @title  是否是一个数组
     * @param       $data
     * @param array $options
     * @return bool
     * @author benjamin
     */
    public static function isArray ( $data, array $options = [] ) {
        $options = array_merge([ 'rules' => [], 'length' => null ], $options);

        if ( !is_array($data) ) {
            self::$msg = '必须是一个数组!';
            return false;
        }
        if ( !empty($options['length']) && count($data) !== (int)$options['length'] ) {
            self::$msg = '非法的数组长度!';
            return false;
        }

        return true;
    }

    /**
     * @title  是否JSON字符串
     * @author benjamin
     */
    public static function isJson ( $str, array $options = [] ) {
        try {
            $jsonArr = json_decode($str, true);
        } catch (\Exception $e) {
            self::$msg = '必须是JSON字符串!';
            return false;
        }

        if ( empty($jsonArr) ) {
            self::$msg = '必须是JSON字符串!';
            return false;
        }

        return true;
    }

    /**
     * @title 文本数据验证
     * @param       $val
     * @param array $options
     * @return bool
     */
    public static function isText ( $val, array $options = [] ) {
        $options = array_merge([], $options);

        if ( !self::length($val, $options) ) {
            return false;
        }

        if ( !preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9,，_?？。]+$/u", $val) ) {
            self::$msg = "格式必须为：中文、字母、数字或其组合，不能含有特殊字符和空格!";
            return false;
        }

        return true;
    }

    /**
     * @title  参数内的数据验证规则配置
     * @param       $data
     * @param array $options
     * @return bool
     * @throws ErrMsg
     * @author benjamin
     */
    public static function rules ( $data, array $options = [] ) {
        $options = array_merge([ 'rules' => [] ], $options);

        if ( empty($options['rules']) ) {
            self::$msg = '额外验证规则不能为空!';
            return false;
        } else {
            if ( !self::validate($data, $options['rules']) ) {
                return false;
            }
        }

        return true;
    }


    /**
     * @title  等于处理
     * @param       $str
     * @param array $options
     * @return mixed
     * @author benjamin
     */
    public static function equal ( $str, array $options = [] ) {
        $options = array_merge([ 'equal' => '' ], $options);
        if ( empty($options['equal']) ) return true;

        if ( $str !== $options['equal'] ) {
            self::$msg = '值不匹配!';
            return false;
        }

        return true;
    }

    /**
     * 统一异常输出
     * @param string $msg
     * @throws ErrMsg
     */
    private static function error ( $msg = '数据校验异常，请检查后重新输入!' ) {
        throw new ErrMsg($msg);
    }
}