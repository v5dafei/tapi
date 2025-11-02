<?php
/**
 * Created by PhpStorm.
 * Author: benjamin
 * Date: 2020/11/14/0014
 * Time: 12:12
 */

namespace App\Utils\Captcha;

use App\Utils\Validator;

class DingXiangCaptcha
{
    public $appId     = '';
    public $appSecret = '';

    public $client = '';
    public $resMsg = '';

    public $config = [
        'device'     => 'pc',
        'timeOut'    => 60,
        'captchaUrl' => ''
    ];

    /**
     * 构造入参为appId和appSecret
     * appId和前端验证码的appId保持一致，appId可公开
     * appSecret为秘钥，请勿公开
     * token在前端完成验证后可以获取到，随业务请求发送到后台，token有效期为两分钟
     **/
    function __construct ( $config = [] ) {

        $this->appId     = '8bb5053b378d291b45d167aaea0d4e49';
        $this->appSecret = 'bda5a1b4621e5dcf8c77e8ca6f244559';

        $this->config = array_merge($this->config, $config);

        include_once(API_PATH . "/Lib/dingxianginc/sdk/captcha/CaptchaClient.php");

        $client = new \CaptchaClient($this->appId, $this->appSecret);

        # 设置超时时间
        $client->setTimeOut($this->config['timeOut']);

        # 特殊情况需要额外指定服务器,可以在这个指定，默认情况下不需要设置
        if ( !empty($this->config['captchaUrl']) ) {
            $client->setCaptchaUrl($this->config['captchaUrl']);
        }

        $this->client = $client;
    }

    /**
     * 验证码正确性检查
     * @param $token
     * @return bool
     */
    public function check ( $token = '' ) {

        if ( empty(trim($token)) ) {
            if ( $this->config['device'] === 'pc' ) {
                $this->resMsg = 'needRandomCaptcha';
            } else {
                $this->resMsg = '请拖动滑动进行验证';
            }
            return false;
        }

        if ( strlen($token) <= 10 ) {
            if ( $this->config['device'] === 'pc' ) {
                $this->resMsg = 'needRandomCaptcha';
            } else {
                $this->resMsg = '请拖动滑动进行验证';
            }
            return false;
        }

        # 响应处理
        $response     = $this->client->verifyToken($token);
        $this->resMsg = $response->serverStatus;
        if ( $this->resMsg === '无效请求' ) {
            if ( $this->config['device'] === 'pc' ) {
                $this->resMsg = 'needRandomCaptcha';
            } else {
                $this->resMsg = '表单已过期，请刷新页面重试';
            }
        }

        # 确保验证状态是SERVER_SUCCESS，SDK中有容错机制，在网络出现异常的情况会返回通过
        if ( $response->result ) {
            /**token验证通过，继续其他流程**/
            return true;
        } else {
            /**token验证失败**/
            return false;
        }
    }


    /**
     * 获取校验信息
     */
    public function getErrMsg () {
        return !empty($this->resMsg) ? $this->resMsg : '滑动验证码校验失败，请重新滑动!';
    }

}