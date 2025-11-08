<?php
/**
 * 客户端相关常量声明
 */

namespace App\Utils\Enum;

class ClientEnum
{

    const CLIENT_TYPE_PC  = 'pc';
    const CLIENT_TYPE_WAP = 'wap';
    const CLIENT_TYPE_APP = 'app';

    /**
     * 客户端来源设备声明
     *
     * @tips：0:未知, 1:PC, 2:原生安卓, 3:原生IOS, 4:安卓H5, 5:IOS_H5, 6:豪华版安卓, 7:豪华版IOS，
     * @tips：8:混合版安卓, 9:混合版IOS, 10:聊天版安卓, 11:聊天版IOS
     */
    const DEVICE_UNKNOWN    = 0;    // PC(默认)
    const DEVICE_H5         = 1;    // H5/Mobile
    const DEVICE_AND_NATIVE = 2;    // 原生安卓
    const DEVICE_IOS_NATIVE = 3;    // 原生IOS
    const DEVICE_H5_AND     = 4;    // 安卓H5
    const DEVICE_H5_IOS     = 5;    // 苹果H5

    /**
     * 设备类型与描述
     *
     * @var array
     */
    const DEVICE_MAP = [
        self::DEVICE_UNKNOWN    => 'PC(默认)',
        self::DEVICE_H5         => 'H5/Mobile',
        self::DEVICE_AND_NATIVE => '原生安卓',
        self::DEVICE_IOS_NATIVE => '原生IOS',
        self::DEVICE_H5_AND     => '安卓H5',
        self::DEVICE_H5_IOS     => '苹果H5',
    ];

    /**
     * 设备类型与描述
     *
     * @var array
     */
    static $DEVICE_TYPE2 = [
        0  => 'PC(默认)',
        1  => 'H5/Mobile',
        2  => '原生安卓',
        3  => '原生IOS',
        4  => '安卓H5',
        5  => '苹果H5',
        6  => '豪华版安卓',
        7  => '豪华版IOS',
        8  => '混合版安卓',
        9  => '混合版IOS',
        10 => '聊天版安卓',
        11 => '聊天版IOS',
    ];

}