<?php namespace App\Lib;

use App\Models\Conf\SysTelegramChannel;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;

class Telegram {

    static function sendCode($text, $carrierId = 0) 
    {
        if(!defined('YACONF_PRO_ENV')) define('YACONF_PRO_ENV', 'env');
        $message = '<b>时    间 : '.date('Y-m-d H:i:s',time()).'</b> '.chr(10);
        $channel_id              = \Yaconf::get(YACONF_PRO_ENV.'.channel_id', -887601118);
        $webSendBootToken        = config('main')['web_send_boot_token'];

        $message .= $text;

        return self::sendMessage($channel_id, $message,'HTML',$webSendBootToken);
    }

    static function sendMessage($chat_id, $message ,$model='HTML',$webSendBootToken)
    {
        if(empty($webSendBootToken)){
            $webSendBootToken = config('main')['web_send_boot_token'];
        }
        $url    = 'https://api.telegram.org/bot'.$webSendBootToken.'/sendMessage';
        $params = ['chat_id'=>$chat_id,'text'=>$message,'parse_mode'=>$model];
        $output = self::request('POST',$url,$params);
        $output = json_decode($output,true);

        if(isset($output) && $output['ok']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据名称查找 id
     * @param $token
     * @param $name
     * @return bool
     */
    static function findChannelId($name,$webSendBootToken)
    {
        if(empty($webSendBootToken)){
            $webSendBootToken = config('main')['web_send_boot_token'];
        }

        $url    = 'https://api.telegram.org/bot'.$webSendBootToken.  '/getUpdates';
        $params = [];
        $output = self::request('POST', $url, $params);
        $output = json_decode($output,true);

        if(isset($output['result'])) {
            $channelId = "";

            foreach ($output['result'] as $key => $value) {
                if(isset($value['message']['chat']) && $value['message']['chat']['type'] == 'group') {
                    if($name == $value['message']['chat']['title']) {
                        $channelId = $value['message']['chat']['id'];
                    }
                }
                if(isset($value['channel_post']['chat']) && $value['channel_post']['chat']['type'] == 'channel') {
                    if($name == $value['channel_post']['chat']['title']) {
                        $channelId = $value['channel_post']['chat']['id'];
                    }
                }
            }
            return $channelId;
        }
        return false;
    }

    static function request($method = 'GET', $url, $params = [], $header = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        if (!is_null($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        }

        $output = '';

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

                $output         = curl_exec($ch);
                $request_header = curl_getinfo($ch,CURLINFO_HEADER_OUT);
                if (curl_errno($ch)) {
                    curl_close($ch);

                    return false;
                }
                return $output;
                break;
            case 'GET':
                $output = curl_exec($ch);
                if (curl_errno($ch)) {
                    curl_close($ch);

                    return false;
                }
                return $output;
                break;
        }
    }
}
