<?php

namespace Utils\Rpc;

class HttpClient{

        public  $client;

        public  function __construct($ip,$port)
        {
            $this->client=new \swoole\http\client($ip,$port);
        }

        //同步请求客户端
        public  function  http(){

        }

        //异步websocket请求
        public  function  async_websocket($callback,$path='/'){
            //监听服务端给我们发送的数据
            $this->client->on('message',function ($cli,$frame){
                    //echo '接收到消息';
            });

            //websocket建立的是一个长连接
            $this->client->upgrade($path,$callback);
        }

        //同步请求客户端

}