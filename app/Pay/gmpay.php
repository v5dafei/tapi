<?php

namespace App\Pay;

use App\Models\PlayerBankCard;
use App\Lib\Clog;

class gmpay
{
    use PayCurl;

    const PAYCODE    = 'gmpay'; 
    const QUERYURL   = 'https://www.ajdaifu.com/Payment_dfpay_query.html';

    public function __get($key)
    {
        return $this->$key;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }


    public function paymentOnBehalf($withdraw, $thirdPartPay,$playerBankCard,$carrierPayChannelId)
    {

        switch ($playerBankCard->bank_name) {
            case '工商银行':
                $playerBankCard->bank_name = '中国工商银行';
                break;
            case '农业银行':
                $playerBankCard->bank_name = '中国农业银行';
                break;
            case '招商银行':
                $playerBankCard->bank_name = '中国农业银行';
                break;
            case '建设银行':
                $playerBankCard->bank_name = '中国建设银行';
                break;
            case '光大银行':
                $playerBankCard->bank_name = '中国光大银行';
                break;
            case '深圳发展银行':
                $playerBankCard->bank_name = '平安银行';
                break;
            case '邮政储蓄银行':
                $playerBankCard->bank_name = '中国邮政储蓄银行';
                break;
            case '浦发银行':
                $playerBankCard->bank_name = '上海浦东发展银行';
                break;
            default:
                break;
        }

        $list =[
            'version'       => 'V2',
            'signType'      => 'MD5',
            'merchantNo'    => $thirdPartPay['merchantNumber'],
            'date'          => date('YmdHis'),
            'channleType'   =>,
            'orderNo'       => $withdraw->pay_order_number,
            'bizAmt'        => bcdiv($withdraw->real_amount,10000,2),
            'accName'       => $playerBankCard->card_owner_name,
            'bankCode'      =>
            'bankBranchName'=> $playerBankCard->bank_name,
            'cardNo'        => $playerBankCard->card_account,
            'openProvince'  =>
            'openCity'      =>
        ];

        $arr = [
            'mchid'         => $thirdPartPay['merchantNumber'],
            'addtime'       => time(),
            'bankcode'      => 'unionpay',
            'list'          => '['.json_encode($list).']',
            'callback_url'  => config('main.behalfUrl').'/'.$carrierPayChannelId,
        ];

        $arr['sign']        = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output             = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr), 1);
        
        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['status'] =='success') {
                return true;
            } else {
                return false;
            }
        } else {
            //返回错误
            return true;
        }
    }



    public function generateSignature($data,$privateKey)
    {
        ksort($data);
        $sign         = urldecode(http_build_query($data)).'&key='.$privateKey;

        return  strtoupper(md5($sign));
    }

    public function behalfCallback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        if(isset($input['out_trade_no']) && !empty(trim($input['out_trade_no']))) {
            $sign = $input['sign'];
            unset($input['sign']);

            $newSign = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($sign == $newSign) {
                if($input['status'] ==2) {
                   // $flag = $this->checked($input['out_trade_no'],$thirdPartPay);
                  //  if($flag==true){
                    return ['status'=>true,'orderNumber'=>$input['out_trade_no'],'thirdOrderNumber'=>$input['orderid']];
                   // } else {
                    //    return false;
                   // }
                }
            } 
        } 
    }

    public function checked($orderid,$thirdPartPay)
    {
        $arr     = [
            'mchid'         => $thirdPartPay['merchantNumber'],
            'out_trade_no'  => $orderid,
            'applytime'     => time()
        ];

        $arr['sign']       = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output            = $this->request('POST', self::QUERYURL, http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
             Clog::payMsg(self::PAYCODE, '', $returnArr);
            if($returnArr['data']['status'] == 2) {
                return true;
            } elseif($returnArr['data']['status'] == 4){
                return false;
            } else {

            }
        } else {

        }
    }

    public function successNotice()
    {
        echo 'OK';
    }
}