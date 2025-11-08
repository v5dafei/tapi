<?php

namespace Utils\Rpc;

class  RpcClient
{

    private $serviceName;

    //调用不存在的方法时会触发
    public function __call ( $name, $arg ) {
        if ( $name == 'service' ) { //意味着会调用
            $this->serviceName = $arg[0];
            return $this;
        }

        $client = new TcpClient('118.24.109.254', 9801);
        //请求的服务名称,参数
        $data = json_encode([
            'service' => $this->serviceName, //服务名
            'action'  => $name, //方法
            'params'  => $arg //参数
        ]);

        $res = $client->sync($data);
        return $res;
    }
}


