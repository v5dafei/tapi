<?php
/**
 * @title 数组处理工具类.
 * @link  http://www.runoob.com/php/php-ref-array.html
 */

namespace App\Utils\Arr;

class ArrHelper
{
    static function objToArr($obj) {
        return json_decode(json_encode($obj), true);
    }

    /**
     * @title  生成索带索引的数组列表
     * @param array $data
     * @param null  $index
     * @return array
     * @author benjamin
     */
    public static function genIndexList ( array $data, $index = null ) {
        $list = [];
        if ( $index !== null ) {
            foreach ( $data as $k => $v ) {
                if ( !isset($v[$index]) ) break;
                $list[$v[$index]] = $v;
            }
        }
        return $list;
    }

    /**
     * 菜单权限树->菜单权限一维数组
     * @param array $menuTree
     * @param int   $level
     * @param int   $maxLevel
     * @return array
     */
    public static function parseMenuTreeToArr ( array $menuTree, $level = 1, $maxLevel = 3 ) {

        static $menuList = [];

        foreach ( $menuTree as $k => $menu ) {
            if ( !isset($menuList[$menu['id']]) ) {
                # 当前循环菜单
                $curMenu = $menu;
                if ( !empty($curMenu['subMenu']) ) {
                    $subMenu = $curMenu['subMenu'];
                    unset($curMenu['subMenu']);
                } else {
                    $subMenu = [];
                }

                $curMenu['level']      = $level;
                $menuList[$menu['id']] = $curMenu;

                # 递归获取所有子菜单
                if ( !empty($subMenu) && $level + 1 <= $maxLevel ) {
                    self::parseMenuTreeToArr($subMenu, $level + 1);
                }
            }
        }

        return $menuList;
    }

    /**
     * 菜单一维数组->解析菜单树
     * @param array $menuArr
     * @param int   $level
     * @param int   $maxLevel
     * @return array
     */
    public static function createMenuTreeByArr ( array $menuArr, $pid = 0, $level = 1, $maxLevel = 3 ) {

        $menuTree = [];

        foreach ( $menuArr as $k => $menu ) {
            if ( $menu['pid'] == $pid && $level <= $maxLevel ) {

                # 当前循环菜单
                $curMenu          = $menu;
                $curMenu['level'] = $level;

                # 递归获取所有子菜单
                $subMenu = self::createMenuTreeByArr($menuArr, $menu['id'], $level + 1);
                if ( !empty($subMenu) ) {
                    $curMenu['subMenu'] = $subMenu;
                }

                $menuTree[$menu['id']] = $curMenu;
            }
        }

        return $menuTree;
    }

    /**
     * @title  获取数组维度
     * @param $arr
     * @return int
     * @author benjamin
     */
    public static function getArrLevel ( $arr ) {
        if ( !is_array($arr) ) return 0;
        $maxDepth = 1;
        foreach ( $arr as $value ) {
            if ( is_array($value) ) {
                $curDepth = self::getArrLevel($value) + 1;
                if ( $curDepth > $maxDepth ) {
                    $maxDepth = $curDepth;
                }
            }
        }
        return $maxDepth;
    }

    /**
     * @title  对参数进行连接 (只支持一维数组)
     * @demo  ['usr'=>'test','age'=>1]  =>  usr=test&age=1
     * @param array  $params
     * @param string $join
     * @return string
     * @author benjamin
     */
    public static function joinParams ( array $params, $join = '&' ) {
        $str = '';
        foreach ( $params as $k => $v ) {
            $str .= $k . '=' . $v . $join;
        }
        return rtrim($join, $str);
    }

    /**
     * 获取数组元素
     * @title  dataGet
     * @param array  $data
     * @param        $key
     * @param string $default
     * @return mixed|string
     * @author Michael
     * @time   2019/7/9 22:50
     */
    public static function dataGet ( array $data, $key, $default = '' ) {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    /**
     * @param $array
     * @param $key
     * @param $value
     * @return array
     * @author Michael
     * @time   2019/7/13 21:08
     */
    public static function getKeyValuePair ( $array, $k, $v ) {
        $data = [];
        foreach ( $array as $value ) {
            if ( !isset($value[$k]) ) return [];
            if ( !isset($value[$v]) ) return [];
            $data[$value[$k]] = $value[$v];
        }

        return $data;
    }

    /**
     * 格式化数组（针对于前端的格式化。去除key）
     * @param $array
     * @return array
     * @author Michael
     * @time   2019/7/15 13:07
     */
    public static function formatArrayKey ( $array ) {
        $data = [];
        foreach ( $array as $value ) {
            $data[] = $value;
        }

        return $data;
    }

    /**
     * @title 检查一维数组：连接组合中的字符串
     * @param $key
     * @param $val
     * @param $num
     * @demo  例：$key = [0, 0, 0], $val = [7, 8, 9], $num = 3
     * @return array|string
     */
    public static function combineHelper ( $key, $val, $num ) {

        $rr_tmp = $rr_ary = [];
        # 新代码
        for ( $i = 0; $i < $num; $i++ ) {
            if ( $i === 0 ) {
                $rr_tmp[$i] = $val[$i];
            } else if ( $key[$i] >= $key[$i - 1] ) {
                $rr_tmp[$i] = $val[$i];
            }
        }
        # 旧代码
//            for ( $i = 0; $i < $num; $i++ ) {
//                if ( $key[$i] >= $key[$i - 1] ) {
//                    $rr_tmp[$i] = $val[$i];
//                }
//            }
        $rr_tmp = array_filter($rr_tmp);
        $rr_tmp = array_unique($rr_tmp);
        if ( count($rr_tmp) == $num ) {
            $rr_ary = implode(',', $rr_tmp);
        }

        return $rr_ary;
    }

    /**
     * 第一种组合注数算法
     * @params Array arr        备选数组
     * @params Int num
     * @return array
     * useage:  combine([1,2,3,4,5,6,7,8,9], 3);
     */
    public static function combine ( $arr, $num ) {

        //组合每位数的数组

        //如果投注号码超出长度，则截断超出的部分
        if ( count($arr) > $num ) {
            $i = 0;
            foreach ( $arr as $key => $val ) {
                $i = $i + 1;
                if ( $i > $num ) {
                    //unset($arr[$key]);
                }
            }
        }

        # 生成合理的数组结构
        $r = array();
        for ( $k = 0; $k < $num; $k++ ) {
            for ( $i = 0; $i < count($arr); $i++ ) {
                if ( $i > $k - 1 && $i < count($arr) - $num + $k + 1 ) {
                    $r[$k][] = $arr[$i];
                }
            }
        }

        //组合
        $rr     = array();
        $rr_ary = array();
        $rr_tmp = array();
        $key    = $val = array();
        if ( is_array($r[0]) ) {
            foreach ( $r[0] as $key[0] => $val[0] ) {
                if ( $num <= 1 ) {
                    $rr_ary[] = self::combineHelper($key, $val, $num);
                }
                if ( isset($r[1]) && is_array($r[1]) ) {
                    foreach ( $r[1] as $key[1] => $val[1] ) {
                        if ( $num <= 2 ) {
                            $rr_ary[] = self::combineHelper($key, $val, $num);
                        }
                        if ( isset($r[2]) && is_array($r[2]) ) {
                            foreach ( $r[2] as $key[2] => $val[2] ) {
                                if ( $num <= 3 ) {
                                    $rr_ary[] = self::combineHelper($key, $val, $num);
                                }
                                if ( isset($r[3]) && is_array($r[3]) ) {
                                    foreach ( $r[3] as $key[3] => $val[3] ) {
                                        if ( $num <= 4 ) {
                                            $rr_ary[] = self::combineHelper($key, $val, $num);
                                        }
                                        if ( isset($r[4]) && is_array($r[4]) ) {
                                            foreach ( $r[4] as $key[4] => $val[4] ) {
                                                if ( $num <= 5 ) {
                                                    $rr_ary[] = self::combineHelper($key, $val, $num);
                                                }
                                                if ( isset($r[5]) && is_array($r[5]) ) {
                                                    foreach ( $r[5] as $key[5] => $val[5] ) {
                                                        if ( $num <= 6 ) {
                                                            $rr_ary[] = self::combineHelper($key, $val, $num);
                                                        }
                                                        if ( isset($r[6]) && is_array($r[6]) ) {
                                                            foreach ( $r[6] as $key[6] => $val[6] ) {
                                                                if ( $num <= 7 ) {
                                                                    $rr_ary[] = self::combineHelper($key, $val, $num);
                                                                }
                                                                if ( isset($r[7]) && is_array($r[7]) ) {
                                                                    foreach ( $r[7] as $key[7] => $val[7] ) {
                                                                        if ( $num <= 8 ) {
                                                                            $rr_ary[] = self::combineHelper($key, $val, $num);
                                                                        }
                                                                        if ( isset($r[8]) && is_array($r[8]) ) {
                                                                            foreach ( $r[8] as $key[8] => $val[8] ) {
                                                                                if ( $num <= 9 ) {
                                                                                    $rr_ary[] = self::combineHelper($key, $val, $num);
                                                                                }
                                                                                if ( isset($r[9]) && is_array($r[9]) ) {
                                                                                    foreach ( $r[9] as $key[9] => $val[9] ) {
                                                                                        if ( $num <= 10 ) {
                                                                                            $rr_ary[] = self::combineHelper($key, $val, $num);
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        //重新组合
        $tt     = array();
        $rr_ary = array_filter($rr_ary);
        $rr_ary = array_unique($rr_ary);
        foreach ( $rr_ary as $rr_each ) {
            $tt[] = $rr_each;
        }
        //$tt = implode(',',$tt);
        //$tt = explode(',',$tt);
        return $tt;
    }


    function getRank ( $arr, $len = 0, $str = "" ) {

        global $arr_getrank;
        $arr_len = count($arr);
        if ( $len == 0 ) {
            $arr_getrank[] = $str;
        } else {
            for ( $i = 0; $i < $arr_len; $i++ ) {
                $tmp = array_shift($arr);
                if ( empty($str) ) {
                    getRank($arr, $len - 1, $tmp);
                } else {
                    getRank($arr, $len - 1, $str . "," . $tmp);
                }
// array_push($arr, $tmp);
            }
        }
    }

    /**
     * @title   二位数组排序
     * @param array  $list 原数组
     * @param string $key  排序的字段
     * @param string $sort 排序方式'ASC'正序|DESC倒序
     * @return  array
     * @author  bob
     */
    static public function arraySort ( $list, $key, $sort = 'ASC' ) {
        if ( $list ) {
            $keyArray = [];
            foreach ( $list as $value ) {
                $keyArray[] = $value[$key];
            }

            if ( $sort == 'ASC' ) {
                array_multisort($keyArray, SORT_ASC, $list);
            } else {
                array_multisort($keyArray, SORT_DESC, $list);
            }
        }
        return $list;
    }

    /**
     * @param string $betInfo      投注信息
     * @param int    $yuenanByHand 手动输入
     * @param int    $countMode    下注结算模式
     * @return  bool
     * @author  sphitx
     */
    static public function getYuenanBetNums ( $betInfo, $yuenanByHand = false, $countMode = 0 ) {
        if ( $countMode == 1 ) {
            if ( $yuenanByHand == 2 ) {
                $digits = explode('|', $betInfo);
                return $digits;
            }
            return (array)$betInfo;
        }
        if ( $yuenanByHand == 1 ) {
            $digits = explode(';', $betInfo);
            return $digits;
        }
        // 位数分割
        $digits = explode('|', $betInfo);
        $balls  = [];
        foreach ( $digits as $value ) {
            // 号码分割
            $balls[] = array_unique(explode(',', $value));
        }
        if ( empty($balls) ) {
            return $balls;
        }
        return call_user_func_array('\App\Utils\Arr\ArrHelper::getBetscombination', $balls);
    }

    /**
     * @param array $balls 投注信息
     * @return  array 组合后的数组
     * @author  sphitx
     */
    static public function getBetscombination () {
        $num = func_num_args();
        if ( $num === 0 ) return false;
        $all = func_get_args();
        if ( $num === 1 ) return $all[0];
        while ( count($all) > 1 ) {
            $all_first  = array_shift($all);
            $all_second = array_shift($all);
            $c          = array();
            foreach ( $all_first as $v ) {
                $v = (array)$v;
                foreach ( $all_second as $val ) {
                    $c[] = array_merge($v, (array)$val);
                }
            }
            array_unshift($all, $c);
            unset($c);
        }
        return $all[0];
    }
}