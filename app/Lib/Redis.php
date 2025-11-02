<?php

namespace App\Lib;

use App\Exceptions\ErrRedis;
use App\Utils\File\Logger;
use Redis as redisServer;

class Redis
{

    private static $redisop;
    private        $cluster;
    private        $redis;
    public         $curConf = [];
    private        $connKey = '';
    public         $code = 'defConn';

    private        $pingResult = 'default';

    /**
     * 防止被实例化（单例模式）
     *
     * @param $conf
     * @throws \Exception
     */
    /**
     * 防止被实例化（单例模式）
     *
     * @param $conf
     * @throws \Exception
     */
    private function __construct ( $conf ) {

        $this->curConf = $conf;
        $this->connect();
    }

    /**
     * @title  单例模式
     * @param $conf
     * @return mixed
     * @throws \Exception
     */
    public static function getInstance ( $conf ) {
        if ( !isset(static::$redisop) ) {
            static::$redisop = new self($conf);
        }
        return static::$redisop;
    }


    /**
     * todo 暂未使用
     * 在CALL魔法函数中查找要调用的方法是否存在，如果存在可以直接
     * 调用对应类中的方法，这里用到call_user_func_array()
     */
    public function __call ( $name, $params ) {

        if ( method_exists($this, $name) ) {
            # 检查连接
            $this->beforeEvents();

            # 调度具体方法
            call_user_func_array(array( $this, $name ), $params);
        } else {
            throw new \Exception("缓存服务 - 操作方法：{$name} 并不存在...");
        }

    }

    /**
     * @title  根据集群情况报错的特殊处理
     * @error  Uncaught exception 'RedisClusterException' with message 'Error processing response from Redis node!'
     * @author benjamin
     */
    public function handlerRedisAction($action, $args, $config = []) {
        try {
//            consoleLog('当前连接code：' . $this->code);

            handler:

            # 带前缀KEY生成
            if ( !isset($config['getKeyName']) || $config['getKeyName'] ) {
                $tableName = $args[0];
                $args[0]   = $this->getKeyName($tableName);
            }

//            # 随机抛出异常
//            if ( defined('CLI_MODE') && CLI_MODE && rand(1,6) == 2 && $this->code == 'defConn') {
//                consoleLog('这是一个手动抛出的Redis-Cluster异常');
//                throw new \RedisClusterException('这是一个手动抛出的Redis-Cluster异常');
//            }

            # 执行具体操作
            return call_user_func_array([ $this->redis, $action ], $args);

        } catch (\RedisClusterException $e) {
            # 记录当前集群错误信息
            Logger::write(
                "集群节点异常：{$e->getMessage()}，重连处理中...",
                '/chat/errNode/' . $action,
                Logger::LEVEL_ERR,
                false
            );

            # 尝试检查和重连，无法重连抛出异常
            $this->beforeEvents();

            # 重连成功则继续处理
            consoleLog('重新继续上次操作:', [
                $action,
                $args
            ]);

            goto handler;
        } catch (\RedisException $e) {
            # 记录当前集群错误信息
            Logger::write(
                "单机节点异常：{$e->getMessage()}，重连处理中...",
                '/chat/errNode/' . $action,
                Logger::LEVEL_ERR,
                false
            );

            # 尝试检查和重连，无法重连抛出异常
            $this->beforeEvents();

            # 重连成功则继续处理
            consoleLog('重新继续上次操作:', [
                $action,
                $args
            ]);

            goto handler;
        } catch (\Exception $e) {
            # 处理：PHP Notice: RedisCluster::get(): send of 55 bytes failed with errno=32 Broken pipe
            Logger::write(
                "节点特殊异常：{$e->getMessage()}，重连处理中...",
                '/chat/errNode/' . $action,
                Logger::LEVEL_ERR,
                false
            );

            # 尝试检查和重连，无法重连抛出异常
            $this->beforeEvents();

            # 重连成功则继续处理
            consoleLog('重新继续上次操作:', [
                $action,
                $args
            ]);

            goto handler;
        }
    }

    /**
     * 检查连接并重连操作
     */
    private function checkConnect () {
        # 只在Swoole场景下进行重连
        if ( defined('CLI_MODE') && CLI_MODE ) {
            consoleLog('开始重连检查');
            try {
                # ping用于检查当前连接的状态,成功时返回+PONG,失败时抛出一个RedisException对象.
                # ping失败时警告:
                # Warning: Redis::ping(): connect() failed: Connection refused

                if ( !$this->checkPingResult($this->ping()) ) {
                    throw new \RedisException('缓存服务连接失败!');
                }
            } catch (\RedisClusterException $e ) { // 集群异常

                # 断线重连
                $this->connect();

                if ( !$this->checkPingResult($this->ping()) ) {
                    # 如果连接之后仍然异常则抛出最终异常
                    throw new \Exception('缓存服务重连失败，内容：'. $e->getMessage() .'，类型：' . ($this->cluster ? '集群' : '单机') . '，PING：' . $this->pingResult);
                } else {
                    $this->code = 'newConn';
                }

            } catch (\RedisException $e ) { // 单节点异常
                # 信息如 Connection lost 或 Redis server went away

                # 断线重连
                $this->connect();

                if ( !$this->checkPingResult($this->ping()) ) {
                    # 如果连接之后仍然异常则抛出最终异常
                    throw new \Exception('缓存服务重连失败，内容：'. $e->getMessage() .'，类型：' . ($this->cluster ? '集群' : '单机') . '，PING：' . $this->pingResult);
                } else {
                    $this->code = 'newConn';
                }

            }

        }
    }

    /**
     * 连接操作
     */
    private function connect () {
//        consoleLog('开启重连！...');
        $conf = $this->curConf;

        try {
            # 集群节点列表
            if ( count($conf['clusterList']) > 0 ) {
                $clusterList = $conf['clusterList'];

                # 集群某个节点参数：['127.0.0.1:9002']
                $connKey       = array_rand($clusterList);
                $connAry       = [ $connKey ];
                $this->connKey = $connKey;

                # 全部集群节点参数：['127.0.0.1:9001','127.0.0.1:9002','127.0.0.1:9003']
//                $connAry = array_keys($clusterList);

                # 超时时间：命令行模式下不过期
                $timeout = defined('CLI_MODE') && CLI_MODE ? NULL : 6;

                # 连接集群
                $this->redis   = new \RedisCluster(NULL, $connAry, $timeout, 6);
                $this->cluster = true;

                # 读取策略调整，解决集群负载不均衡的问题。暂不使用
                //$this->redis->setOption(RedisCluster::OPT_SLAVE_FAILOVER, RedisCluster::FAILOVER_DISTRIBUTE_SLAVES);

            } elseif ( count($conf['serverList']) > 0 ) {
                # 普通节点列表

                # 获取某个连接配置
                $nodeList = $conf['serverList'];
                $connKey  = array_rand($nodeList);
                $curConf  = $nodeList[$connKey];
                $this->connKey = $connKey; // 127.0.0.1:9001

                # 单机模式
                $this->redis = new redisServer();
                $this->redis->connect($curConf['host'], $curConf['port']);

                # 暂时不验证
                //$this->redis->auth($conf['redisServer'][0]['pw']);
                $this->redis->select($conf['db_id']);
            }
        } catch (\Exception $e) {
            throw new ErrRedis($e->getMessage());
        }
    }

    /**
     * 析构关闭redis连接
     */
    public function __destruct () {
        $this->redis->close();
    }

    /**
     * 前置处理事件
     */
    public function beforeEvents () {
        $this->checkConnect();
    }

    /**
     * 选择一个数据库
     *
     * @param int $num 数据库编号
     */
    public function select ( $num ) {
        if ( !$this->cluster ) {

            # 断线重连版本
            return $this->handlerRedisAction(__FUNCTION__, func_get_args(), ['getKeyName' => false]);

            # 普通版本
//            $this->redis->select($num);
        }
    }

    /**
     * 数据库前缀统一
     */
    public function getKeyName ( $key ) {
        if ( !empty($this->eventParams['noPrefix']) ) {
            return $key;
        }

        # KEY只能是字幕数字
        $key = preg_replace("/[^a-zA-Z0-9]+/", "", $key);

        # KEY前缀获取
        $prefix = !empty($this->eventParams['prefix']) ? !empty($this->eventParams['prefix']) : $this->curConf['prefix'];
        if ( !empty($this->eventParams['prefix']) ) {
            return $this->eventParams['prefix'] . "_" . $key;
        }

        return !empty($prefix) ? $prefix . "_" . $key : $key;

//        return $this->curConf['prefix'] . "_" . preg_replace("/[^a-zA-Z0-9]+/", "", $key);
    }

    /**
     * @title 判断是否热点key，将热点key分割成50份，以分散流量到多服务器上
     * @param $key
     * @return bool
     */
    public function isHotKey ( $key ) {
        # 集群条件下，判断是否热点key
        if ( $this->cluster && preg_match("/(Hot)+/i", $key) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置值  构建一个字符串
     *
     * @param string $key     KEY名称
     * @param string $value   设置值
     * @param int    $timeOut 时间  -1表示无过期时间
     */
    public function set ( $key, $value, $timeOut = 0 ) {
        $value = serialize($value);
        $key   = $this->getKeyName($key);
        //将热点key分离成50个，get时取其中一个，以分散key到多个redis服务器，避免热点key倾斜
        $isHotKey = $this->isHotKey($key);
        if ( $isHotKey ) {
            for ( $i = 0; $i < 50; $i++ ) {
                if ( $timeOut > 0 ) {
                    $retRes = $this->setex($key . '_' . $i, $timeOut, $value);
                } elseif ( $timeOut == -1 ) {
                    $retRes = $this->redis->set($key . '_' . $i, $value);
                } else {
                    $retRes = '';
                }
            }
        } else {
            if ( $timeOut > 0 ) {
                $retRes = $this->setex($key, $timeOut, $value);
            } elseif ( $timeOut == -1 ) {
                $retRes = $this->redis->set($key, $value);
            } else {
                $retRes = '';
            }
        }
        return $retRes;
    }

    /**
     * 设置带过期时间的缓存，暂时只供内部调用
     */
    private function setex($key, $timeOut, $value) {

        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args(), ['getKeyName' => false]);

        # 普通处理版本
//        return $this->redis->setex($key, $timeOut, $value);
    }

    /**
     * 获取过期时间
     *
     * @param $key
     * @return int -2～过期时间
     */
    public function ttl ( $key ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());
        
        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->ttl($key);
    }

    /**
     * @title 设置某个KEY过期时间(秒)
     */
    public function expire ( $key, $expire = 0 ) {
        if ( $expire > 0 ) {
            # 断线重连版本
            return $this->handlerRedisAction(__FUNCTION__, func_get_args());

            # 普通版本
//            return $this->redis->expire($key, $expire);
        }
    }

    /**
     * @title  设置某个KEY过期时间(毫秒)
     */
    public function pExpire ( $key, $expire = 0 ) {
        if ( $expire > 0 ) {
            # 断线重连版本
            return $this->handlerRedisAction(__FUNCTION__, func_get_args());

            # 普通版本
//            return $this->redis->pExpire($key, $expire);
        }
    }

    /*
     * 构建一个集合(无序集合)
     * @param string $key 集合Y名称
     * @param string|array $value  值
     */
    public function sadd ( $key, $value ) {

        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->sadd($key, $value);
    }

    /*
     * 构建一个集合(有序集合)
     * @param string $key 集合名称
     * @param string|array $value  值
     */
    public function zadd ( $key, $value ) {

        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->zadd($key, $value);
    }

    public function zAddWitchScore($key, $score, $value)
    {
        # 断线重连版本
        $this->handlerRedisAction('zAdd', func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->zAdd($key, $score, $value);
    }

    /**
     * 取集合对应元素
     *
     * @param string $setName 集合名字
     */
    public function smembers ( $setName ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $setName = $this->getKeyName($setName);
//        return $this->redis->smembers($setName);
    }

    /**
     * 构建一个列表(先进后去，类似栈)
     *
     * @param sting  $key   KEY名称
     * @param string $value 值
     */
    public function lpush ( $key, $value ) {
        # 断线重连版本
        return $this->handlerRedisAction('LPUSH', func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->LPUSH($key, $value);
    }

    /**
     * 移除一个元素
     *
     * @param sting  $key   KEY名称
     * @param string $value 值
     */
    public function lpop ( $key ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->lpop($key);
    }

    /**
     * 移除一个元素
     *
     * @param sting  $key   KEY名称
     * @param string $value 值
     */
    public function rpop ( $key ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->rpop($key);
    }

    /**
     * 获取列表长度
     *
     * @param sting $key KEY名称
     */
    public function llen ( $key ) {

        # 断线重连版本
        return $this->handlerRedisAction('Llen', func_get_args());

        # 单节点处理版本
//        $key = $this->getKeyName($key);
//        return $this->redis->Llen($key);
    }

    /**
     * 修剪列表长度
     *
     * @param sting $key KEY名称
     */
    public function ltrim ( $key, $start, $end ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->ltrim($key, $start, $end);
    }

    public function lrem ( $key, $value, $count ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->lrem($key, $value, $count);
    }

    /**
     * 构建一个列表(先进先去，类似队列)
     *
     * @param sting  $key   KEY名称
     * @param string $value 值
     */
    public function rpush ( $key, $value ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->rpush($key, $value);
    }

    /**
     * 获取所有列表数据（从头到尾取）
     *
     * @param sting $key  KEY名称
     * @param int   $head 开始
     * @param int   $tail 结束
     */
    public function lranges ( $key, $head, $tail ) {
        # 断线重连版本
        return $this->handlerRedisAction('lrange', func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->lrange($key, $head, $tail);
    }

    /**
     * HASH类型
     *
     * @param string $tableName 表名字key
     * @param string $key       字段名字
     * @param sting  $value     值
     */
    public function hset ( $tableName, $field, $value ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $this->beforeEvents();
//        $tableName = $this->getKeyName($tableName);
//        return $this->redis->hset($tableName,$field,$value);
    }

    public function hget ( $tableName, $field ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $this->beforeEvents();
//        $tableName = $this->getKeyName($tableName);
//        return $this->redis->hget($tableName, $field);
    }

    public function hmget ( $tableName, $fieldArray ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $tableName = $this->getKeyName($tableName);
//        return $this->redis->hmget($tableName, $fieldArray);
    }

    public function hgetAll ( $tableName ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $tableName = $this->getKeyName($tableName);
//        return $this->redis->hgetAll($tableName);
    }

    public function hLen ( $tableName ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $tableName = $this->getKeyName($tableName);
//        return $this->redis->hLen($tableName);
    }

    public function hKeys ( $tableName ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $tableName = $this->getKeyName($tableName);
//        return $this->redis->hKeys($tableName);
    }

    public function hDel ( $tableName, $field ) {
        # 断线重连版本
        return $this->handlerRedisAction(__FUNCTION__, func_get_args());

        # 普通版本
//        $tableName = $this->getKeyName($tableName);
//        return $this->redis->hDel($tableName, $field);
    }

    public function hincrby ( $tableName, $field, $value ) {
        # 断线重连版本
        return $this->handlerRedisAction('hIncrBy', func_get_args());

        # 普通版本
//        $tableName = $this->getKeyName($tableName);
//        return $this->redis->hIncrBy($tableName, $field, $value);
    }

    public function hIncrByFloat ( $tableName, $field, $value ) {
        # 断线重连版本
        return $this->handlerRedisAction('hIncrByFloat', func_get_args());

        # 普通版本
//        $tableName = $this->getKeyName($tableName);
//        return $this->redis->hIncrBy($tableName, $field, $value);
    }

    /**
     * 设置多个值
     *
     * @param array        $keyArray KEY名称
     * @param string|array $value    获取得到的数据
     * @param int          $timeOut  时间
     */
    public function sets ( $keyArray, $timeout ) {
        if ( is_array($keyArray) ) {
            $retRes = $this->redis->mset($keyArray);
            if ( $timeout > 0 ) {
                foreach ( $keyArray as $key => $value ) {
                    $key = $this->getKeyName($key);
                    $this->redis->expire($key, $timeout);
                }
            }
            return $retRes;
        } else {
            return "Call  " . __FUNCTION__ . " method  parameter  Error !";
        }
    }

    /**
     * 通过key获取数据
     *
     * @param string $key KEY名称
     */
    public function get ( $key ) {
        # 集群代理版
        $key      = $this->getKeyName($key);
        $isHotKey = $this->isHotKey($key);
        // 分离热点key为50个，get取其中一个，以分散热点key到多个redis服务器，避免热点key倾斜
        if ( $isHotKey ) {
            $unique = defined('CLI_MODE') && CLI_MODE ? getmypid() : $this->request->getClientRealIP();
            $result = $this->handlerRedisAction(__FUNCTION__, [$key . '_' . crc32($unique) % 50], ['getKeyName' => false]);
        } else {
            $result = $this->handlerRedisAction(__FUNCTION__, [$key], ['getKeyName' => false]);
        }
        $result = unserialize($result);
        return $result;


        # 旧版
//        $key      = $this->getKeyName($key);
//        $isHotKey = $this->isHotKey($key);
//        // 分离热点key为50个，get取其中一个，以分散热点key到多个redis服务器，避免热点key倾斜
//        // todo $isHotKey
//        if ( $isHotKey ) {
//            $unique = $this->IP();
//            $result = $this->redis->get($key . '_' . crc32($unique) % 50);
//        } else {
//            $result = $this->redis->get($key);
//        }
//        $result = unserialize($result);
//        return $result;
    }

    /**
     * 拷贝自get方法，只是不进行反序列化。
     *
     * @param $key
     * @return bool|string
     */
    public function unsGet ( $key ) {
        $key      = $this->getKeyName($key);
        $isHotKey = $this->isHotKey($key);
        //分离热点key为10个，get取其中一个，以分散热点key到多个redis服务器，避免热点key倾斜
        if ( $isHotKey ) {
            $result = $this->redis->get($key . '_' . crc32($_COOKIE['loginsessid']) % 10);
        } else {
            $result = $this->redis->get($key);
        }
        return $result;
    }

    /**
     * 同时获取多个值
     *
     * @param array $keyArray 获key数值
     */
    public function gets ( $keyArray ) {
        if ( is_array($keyArray) ) {
            $newKeyArray = array();
            foreach ( $keyArray as $key => $value ) {
                $value = $this->getKeyName($value);
                array_push($newKeyArray, $value);
            }
            return $this->redis->mget($newKeyArray);
        } else {
            return "Call  " . __FUNCTION__ . " method  parameter  Error !";
        }
    }

    /**
     * 获取所有key名，不是值
     */
    public function keyAll ( $key = null ) {
        if ( $key ) {
//            $key = $GLOBALS['conf']['db']['user'] . "_" . preg_replace("/[^a-zA-Z0-9\*]+/", "", $key);
            $key = $this->getKeyName($key);
            return $this->redis->keys($key);
        } else {
            return $this->redis->keys('*');
        }
    }

    /**
     * 删除一条数据key
     *
     * @param string $key 删除KEY的名称
     */
    public function del ( $key ) {
        $key = $this->getKeyName($key);
        //热点key有50个
        $isHotKey = $this->isHotKey($key);
        if ( $isHotKey ) {
            for ( $i = 0; $i < 50; $i++ ) {
                $this->redis->del($key . '_' . $i);
            }
            return true;
        } else {
            return $this->redis->del($key);
        }
    }

    /**
     * 同时删除多个key数据
     *
     * @param array $keyArray KEY集合
     */
    public function dels ( $keyArray ) {
        if ( is_array($keyArray) ) {
            $newKeyArray = array();
            foreach ( $keyArray as $key => $value ) {
                $value = $this->getKeyName($value);
                array_push($newKeyArray, $value);
            }
            return $this->redis->del($newKeyArray);
        } else {
            return "Call  " . __FUNCTION__ . " method  parameter  Error !";
        }
    }

    /**
     * 数据自增
     *
     * @param string $key KEY名称
     */
    public function increment ( $key ) {
        # 断线重连版本
        return $this->handlerRedisAction('incr', func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->incr($key);
    }

    /**
     * 数据自减
     *
     * @param string $key KEY名称
     */
    public function decrement ( $key ) {
        # 断线重连版本
        return $this->handlerRedisAction('decr', func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->decr($key);
    }


    /**
     * 判断key是否存在
     *
     * @param string $key KEY名称
     */
    public function isExists ( $key ) {
        # 断线重连版本
        return $this->handlerRedisAction('exists', func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->exists($key);
    }

    public function hExists ( $key, $field ) {
        # 断线重连版本
        return $this->handlerRedisAction('hExists', func_get_args());

        # 普通版本
//        $key = $this->getKeyName($key);
//        return $this->redis->hExists($key, $field);
    }

    /**
     * 重命名- 当且仅当newkey不存在时，将key改为newkey ，当newkey存在时候会报错哦RENAME
     *  和 rename不一样，它是直接更新（存在的值也会直接更新）
     *
     * @param string $Key    KEY名称
     * @param string $newKey 新key名称
     */
    public function updateName ( $key, $newKey ) {
        $key    = $this->getKeyName($key);
        $newKey = $this->getKeyName($newKey);
        return $this->redis->RENAMENX($key, $newKey);
    }

    /**
     * 获取KEY存储的值类型
     * none(key不存在) int(0)  string(字符串) int(1)   list(列表) int(3)  set(集合) int(2)   zset(有序集) int(4)    hash(哈希表) int(5)
     *
     * @param string $key KEY名称
     */
    public function dataType ( $key ) {
        $key = $this->getKeyName($key);
        return $this->redis->type($key);
    }

    /**
     * Redis Lindex 命令用于通过索引获取列表中的元素。你也可以使用负数下标，以 -1 表示列表的最后一个元素， -2 表示列表的倒数第二个元素，以此类推。
     */
    public function lIndex ( $key, $index ) {
        $key = $this->getKeyName($key);
        return $this->redis->lIndex($key, $index);
    }

    /**
     * 清空数据
     */
    public function flushAll () {
        return $this->redis->flushAll();
    }

    /**
     * 返回redis对象
     * redis有非常多的操作方法，我们只封装了一部分
     * 拿着这个对象就可以直接调用redis自身方法
     * eg:$redis->redisOtherMethods()->keys('*a*')   keys方法没封
     */
    public function conn () {
        return $this->redis;
    }

    /**
     * Redis Ping 命令使用客户端向 Redis 服务器发送一个 PING ，如果服务器运作正常的话，会返回一个 PONG
     * @return mixed
     */
    public function ping() {
        if($this->cluster) {
            $res = $this->redis->ping($this->connKey);
        } else {
            $res = $this->redis->ping();
        }
        return $res;
    }

    /**
     * 集群与单节点返回的结果不一致处理
     * @param $res
     * @return bool
     */
    private function checkPingResult ( $res ) {
        $this->pingResult = $res;

        if ( $this->cluster ) {
            $return = (bool)$res;
        } else {
            $return = false;
            if ( $res == 1 || $res === '+pong' || $res === '+PONG' ) {
                $return = true;
            }
        }

//        # 随机断开连接
//        if ( defined('CLI_MODE') && CLI_MODE && rand(1,6) == 2 && $this->code == 'defConn') {
//            $return = false;
//        }

        consoleLog('检查连接：' . ($return ? '正常' : '断开'));
        return $return;
    }

}