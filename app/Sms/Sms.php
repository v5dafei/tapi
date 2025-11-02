<?php

namespace App\Sms;

use App\Models\Conf\CarrierThirdPartPay;
use App\Models\Conf\CarrierPayChannel;
use App\Models\PlayerTransfer;
use App\Models\Carrier;

class Sms
{
    public $smschannel;
    public $smspassage;

    public function __construct($smspassage)
    {
        $platNamespace             = '\\App\\Sms\\'.$smspassage->filename;

        if (class_exists($platNamespace)) {
            if ($this->smschannel === null) {
                $platClass        = new \ReflectionClass($platNamespace);
                $this->smschannel = $platClass->newInstanceArgs();
                $this->smspassage = $smspassage;
            }

        } else {
            \Log::info('对不起，此SMS通道不存在');
            exit;
        }
    }

    public function sendData($mobile,$carrier,$prefixLanguage,$siteTitle)
    {   $verificationCode = rand(1000,9999);

        $areaCode = '';
        switch ($carrier->currency) {
            case 'CNY':
                $areaCode = '86';
                break;
            case 'PHP':
                $areaCode = '63';
                break;
            case 'VND':
                $areaCode = '84';
                break;
            case 'INR':
                $areaCode = '91';
                break;
            case 'IDR':
                $areaCode = '62';
                break;
            case 'THB':
                $areaCode = '66';
                break;
            case 'USD':
                $areaCode = '';
                break;
            
            default:
                $areaCode = '86';
                break;
        }

        $shortmobile = ltrim($mobile,$areaCode);

        cache()->put('short_mobile_'.$shortmobile,$verificationCode,now()->addMinutes(1));

        return $this->smschannel->sendData($mobile, $carrier,$prefixLanguage,$siteTitle,$this->smspassage,$verificationCode);
    }

    //回调
    public function callback($input)
    {
        $this->smschannel->callback($input,$this->smspassage);
        return $this->smschannel->successNotice();
    }
}