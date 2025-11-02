<?php
/**
 * 彩票抽象类
 * Date: 2019/7/17
 * Time: 15:45
 */
namespace App\Utils\Lottery;

interface LotteryInterface
{
    const CLIENT_TYPE_PC  = 'pc';
    const CLIENT_TYPE_WAP = 'wap';
    const CLIENT_TYPE_APP = 'app';

    public function getGameResult($nums, $client, $issue='');
}