<?php

namespace App\Utils\Rpc;

use App\Exceptions\ErrMsg;
use App\Utils\Validator;

class TcpClient
{

    private $client;
    private $ip;
    private $port;
    private $timeOut;
    private $errMsg = null;

    public function __construct ( $ip, $port, $timeOut = 15 ) {
        $this->ip      = $ip;
        $this->port    = $port;
        $this->timeOut = $timeOut;
        $this->client  = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
     /*   $this->client->set(array(
            'open_length_check' => true,
            'package_length_func' => function ($data) {
                if (strlen($data) < 8) {
                    return 0;
                }
                $length = intval(trim(substr($data, 0, 8)));
                if ($length <= 0) {
                    return -1;
                }
                return $length + 8;
            },
        ));
        */
    }

    public function connect () {
        if ( !$this->client->connect($this->ip, $this->port, $this->timeOut) ) {
            $this->errMsg = 'Socket Connect Fail';
        }
    }

    //同步请求客户端
    public function sync ( $data ) {

//        if ( $this->client->connect($this->ip, $this->port, 5) ) {
//
//            consoleLog('isConnected', [ 'status' => $this->isConnected() ]);
//
//            $this->client->send($data); //发送
//            $data = $this->client->recv();//接收数据
//            //$this->client->close();
//            return $this->parseRes($data);
//        }

        $this->client->send($data); //发送
        $data = $this->client->recv();//接收数据
        //$this->client->close();
        return $this->parseRes($data);

    }

    public function sendData () {

    }

    public function isConnected () {
        return $this->client->isConnected();
    }

    //异步websocket请求
    public function async () {


    }

    public function parseRes ( $res ) {
        if ( empty($res) || !Validator::isJson($res) ) {
            throw new ErrMsg('TCP服务请求异常1: ' . $res);
        }

        $resArr = json_decode($res, true);
        if ( !is_array($resArr) || empty($resArr['success']) ) {
            $err = !empty($resArr['message']) ? $resArr['message'] : 'TCP服务请求异常';
            throw new ErrMsg('TCP服务请求异常2: ' . $err);
        }

        return $resArr['data'];
    }

}