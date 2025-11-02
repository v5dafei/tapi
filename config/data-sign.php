<?php
/**
 * Created by PhpStorm.
 * User: thor
 * Date: 2021/5/6
 * Time: 0:58
 */

if ( !function_exists('key_gen') ) {
    function key_gen ( $signType, $signAry ) {
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
                'type'        => (string)$signAry['type'],
                'played_group_id' => (string)$signAry['played_group_id'],
                'played_id'    => (string)$signAry['played_id'],
                'open_issue'    => (string)$signAry['open_issue'],
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
                'played_id'    => (string)$signAry['played_id'],
                'played_group_id' => (string)$signAry['played_group_id'],
                'open_issue'    => (string)$signAry['open_issue'],
                'rebate'      => (string)price_format($signAry['rebate'], 4),
                'money'       => (string)price_format($signAry['money'], 2),
                'bet_data'  => (string)$signAry['bet_data'],
                'bet_info'     => (string)$signAry['bet_info'],
                'bet_day'   => (string)$signAry['bet_day'],
                'bet_ip'    => (string)$signAry['bet_ip'],
                'is_tester'    => (string)$signAry['is_tester'],
                'total_nums'   => (string)$signAry['total_nums'],
                'total_money'  => (string)price_format($signAry['total_money'], 2),
                'bet_time'  => (string)$signAry['bet_time'],
                'uid'         => (string)$signAry['uid'],
                'type'        => (string)$signAry['type'],
                'group_name'   => urlencode((string)$signAry['group_name']),
                'odds'        => (string)price_format($signAry['odds'], 4),
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
