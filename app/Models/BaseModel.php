<?php

namespace App\Models;

use App\Exceptions\ErrMsg;
use App\Services\Context;
use App\Utils\Arr\ArrHelper;
use DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;
use App\Utils\Str\StrHelper;

class BaseModel extends Model
{


//    use Context;

    const FRAMEWORK = 'laravel';

    /**
     * 表核心字段声明
     */
    const TABLE_PREFIX = '';
    const TABLE_NAME   = '';
    const TABLE_PK     = '';
    const TABLE_TITLE  = '资源';
    static $store = "redis";

    protected $table = '';
//    public static $instance = [];


    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DATE_FORMAT = 'time';

    /**
     * @title 搜索常用字段归类
     * @var array
     */
    const COLUMNS = [
        'adminPageList' => '*'
    ];

    static $PAGER = [
        'list' => [], 'total' => 0, 'currentPage' => 1, 'totalPage' => 0
    ];

    /**
     * @title  单例通用插件
     * @return mixed
     * @author benjamin
     */
    public static function getInstance () {
        # 延迟获取当前调用的类
        $class = static::class;
        $k     = md5($class);
        if ( empty(self::$instance[$k]) ) {
            self::$instance[$k] = new $class();
        }
        return self::$instance[$k];
    }

    /**
     * 获取商户信息
     * @return mixed
     */
    public static function getMerInfo ( $columns = '' ) {

        $merchant = (array)request()->get('merchant');
        if ( empty($columns) ) {
            return $merchant;
        }
        return !empty($merchant[$columns]) ? $merchant[$columns] : '';
    }

    /**
     * 获取商户号
     * @return mixed
     */
    public static function getMerId () {

        $merchant = (array)request()->get('merchant');
        return !empty($merchant['id']) ? $merchant['id'] : 0;
    }


    /**
     * @title 字段搜索规则
     * @var array
     */
    static $SEARCH_RULES = [
//        'regTimeStart' => [ 'func' => '>=', 'column' => 'regTime' ],                  // 注册起始时间
//        'regTimeEnd'   => [ 'func' => '<=', 'column' => 'regTime' ],                  // 注册结束时间
//        'minBalance'   => [ 'func' => '>=', 'column' => 'balance' ],                  // 最低余额
//        'maxBalance'   => [ 'func' => '<=', 'column' => 'balance' ],                  // 最高余额
//        'usr'          => [ 'func' => 'like', 'type' => 'all' ],                      // 模糊用户名查询：默认
//        'usrExact'     => [ 'func' => '=', 'column' => 'usr' ],                       // 精确用户名查询
//        'usrMulti'     => [ 'func' => 'in', 'column' => 'usr' ],                      // 批量用户名查询
//        'hasParent'    => [ 'func' => '>=', 'column' => 'parentId', 'val' => 0 ],     // 有上级
//        'noParent'     => [ 'func' => '=', 'column' => 'parentId', 'val' => 0 ],      // 无上级
//        'hasBankCard'  => [ 'func' => 'other' ],                                      // 有银行卡：other - 跳过自行单独处理
    ];

    /**
     * 模型初始化处理
     * BaseModel constructor.
     * @param array $attributes
     */
    public function __construct ( array $attributes = [] ) {

        if ( empty($this->table) && !empty(static::TABLE_NAME) ) {
            $this->table = static::TABLE_PREFIX . static::TABLE_NAME;

        }

        parent::__construct($attributes);
    }

    public static function redis () {
        $redisCfg = config('database')['redis'];
        $merchant = static::getMerId();
        # 适配REDIS旧文件配置
        $redisClusterList = [];
        $redisServerList  = [];

        foreach ( $redisCfg as $k => $redis ) {
            if ( !in_array($k, [ 'default' ]) ) continue;

            $key                   = $redis['host'] . ':' . $redis['port'];
            $redis['pwd']          = $redis['password'];
            $redisServerList[$key] = $redis;
        }

//        consoleLog('REDIS缓存前缀：' . 'api2021_' . $merchant);

        $redisConf = [
            # 集群配置（优先使用）
            'clusterList' => $redisClusterList,
            # 普通节点配置
            'serverList'  => $redisServerList,
            'timeout'     => 3,
            'pwd'         => '',
            'persistent'  => false,  // 长连接: 待测试
//            'prefix'      => 'api2021_' . $merchant,
            'prefix'      => '',
            'db_id'       => '0',
        ];

        return \App\Lib\Redis::getInstance($redisConf);
    }


    /**
     * @title  获取请求数据
     * @return mixed
     * @author benjamin
     */
    final static function getRequestData () {
        return request()->all();
    }

    /**
     * @title  获取请求数据-额外查询规则
     * @return mixed
     * @author benjamin
     */
    final static function getSearchRule () {
        return (array)request()->get('searchRule');
    }

    /**
     * @title  外部搜索条件构造器
     * @param $params
     * @return array
     * @author benjamin
     */
    public static function getCriteria ( $params, $condition = [] ) {

        $params      = self::searchFilter($params);
        $criteria    = $data = $where = [];
        $searchRules = array_merge(self::getSearchRule(), static::$SEARCH_RULES);

        foreach ( $params as $k => $v ) {

            if ( isset($searchRules[$k]) ) {

                if ( empty($searchRules[$k]['func']) || $searchRules[$k]['func'] == 'other' ) continue;

                $searchRule = $searchRules[$k];

                # 搜索字段处理
                $field = !empty($searchRule['column']) ? "{$searchRule['column']}" : "{$k}";

                # 搜索值处理
                if ( isset($searchRule['val']) ) {
                    $v = $searchRule['val'];
                }
                if ( !empty($searchRule['toTime']) ) {
                    $v = strtotime($v);
                }
                if ( !empty($searchRule['toLong']) ) {
                    $v = ip2long($v);
                }
                $value = is_numeric($v) ? $v : "'{$v}'";

                # 搜索方法处理
                switch ( $searchRule['func'] ) {
                    case 'like':
                        $type = !empty($searchRule['type']) && in_array($searchRule['type'], [ 'all', 'left', 'right' ]) ? $searchRule['type'] : 'all';
                        if ( $type == 'all' ) $where[] = "{$field} LIKE '%{$v}%'";
                        if ( $type == 'left' ) $where[] = "{$field} LIKE '%{$v}'";
                        if ( $type == 'right' ) $where[] = "{$field} LIKE '{$v}%'";
                        break;
                    case 'in':
                        $v          = !empty($searchRule['val']) ? $searchRule['val'] : $v;
                        $v          = is_array($v) ? $v : explode(',', $v);
                        $valueIdStr = implode("','", $v);
                        $where[]    = "{$field} IN ('{$valueIdStr}')";
                        break;
                    default:
                        $where[] = $field . " {$searchRule['func']} " . $value;
                        break;
                }

            } else {
                # 普通搜索处理

                $data[$k] = $v;
            }
        }

        $criteria['data']  = !empty($condition['data']) ? array_merge($data, $condition['data']) : $data;
        $criteria['where'] = !empty($condition['where']) ? array_merge($where, $condition['where']) : $where;

//        return $criteria;
        return array_merge($condition, $criteria);
    }

    /**
     * @title  搜索统一过滤以及常用参数组装
     * @param array $params
     * @return array
     * @author benjamin
     */
    final static function searchFilter ( array $params ) {
        $fieldFilter = [ 'token', 'page', 'rows', 'page_index', 'page_size' ];
        $transFilter = [ 'betId' => 'id', 'gameId' => 'type', 'usr' => 'usr' ];

        $new = [];

        foreach ( $params as $k => $v ) {
            # 过滤多余字段
            if ( in_array($k, $fieldFilter) ) continue;

            # 字段转换
            if ( isset($transFilter[$k]) ) {
                $new[$transFilter[$k]] = $v;
            } else {
                $new[$k] = $v;
            }
        }

        return $new;
    }

    /**
     * @title  获取当前数据表名(全称)
     * @return mixed
     * @author benjamin
     */
    final static function getTableName ( $needPrefix = true ) {
        return !empty(static::TABLE_NAME) ? ($needPrefix ? self::getTablePrefix() : '') . static::TABLE_NAME : self::getModel()->getTable();
    }

    /**
     * @title  获取当前数据表前缀
     * @return mixed
     * @author benjamin
     */
    final static function getTablePrefix () {
        return static::TABLE_PREFIX;
    }

    /**
     * @title 返回当前PDO连接
     */
    final static function pdo () {
        return DB::getPdo();
    }

    final static function begin () {
        return \DB::beginTransaction();
    }

    final static function commit () {
        return \DB::commit();
    }

    final static function rollback () {
        return \DB::rollback();
    }

    /**
     * @title 使用从库操作
     */
    final static function useReadDb () {
        return true;
        self::db()->useReadDb();
    }

    /**
     * @title 使用主库操作（每次请求后：底层自动选择）
     */
    final static function useWriteDb () {
        return true;
        self::db()->useWriteDb();
    }

    /**
     * @title 当前插入主键
     */
    final static function lastInsertId () {
        return self::pdo()->lastInsertId();
    }

    /**
     * @title  当前语句影响行数(弃用)
     * @return int
     * @author benjamin
     */
    final static function rowCount () {
        return (int)self::db()->rowCount();
    }

    final static function findByPk ( $id, $columns = "*" ) {
        $one = self::find($id, explode(',', $columns));
        return !empty($one) ? $one->toArray() : [];
        return self::find($id, explode(',', $columns))->toArray();
//        return self::db()->table(static::TABLE_NAME)->field($columns)->where([ static::TABLE_PK => $id ])->limit('0,1')->find();
    }

    final static function findOne ( $data = [], $columns = "*" ) {
        $columnArr = explode(',', $columns);
        foreach ( $columnArr as $k => $column ) {
            if ( strpos($column, 'AS') !== false ) {
                $columnArr[$k] = self::raw($column);
            }
        }
        $one = DB::table(self::getTableName())->where($data)->select($columnArr)->first();
        return (array)$one;
//        return self::db()->table(static::TABLE_NAME)->field($columns)->where($data)->limit('0,1')->find();
    }


    final static function raw ( $sql ) {
        return DB::raw($sql);
    }

    final static function findOneBySql ( $sql, $params = [] ) {
        if ( strpos($sql, 'LIMIT') === false && strpos($sql, 'FOR UPDATE') === false ) {
            $sql .= ' LIMIT 0,1';
        }
        return (array)DB::selectOne($sql, $params);
//        return self::db()->table(static::TABLE_NAME)->findBySql($sql, $params);
    }

    final static function findOneBySearch ( $search ) {
        $search['limit'] = '0,1';
        $conditions      = self::getConditions($search);
        return (array)DB::selectOne($conditions['sql'], $conditions['bind']);
//        return self::db()->table(static::TABLE_NAME)->findBySql($conditions['sql'], $conditions['bind']);
    }

    final static function findAll ( $conditions = [] ) {
        $conditions = self::getConditions($conditions);
        $res        = DB::select($conditions['sql'], $conditions['bind']);
        return !empty($res) ? ArrHelper::objToArr($res) : [];
//        return self::db()->table(static::TABLE_NAME)->findAll($conditions['sql'], $conditions['bind']);
    }

    final static function findAllBySql ( $sql, $params ) {
        $conditions = self::getConditions([ 'data' => $params ]);
        $res        = DB::select($sql, $conditions['bind']);
        return !empty($res) ? ArrHelper::objToArr($res) : [];
//        return self::db()->table(static::TABLE_NAME)->findAll($sql, $conditions['bind']);
    }

    public static function findAllBySqlWithCache ( $sql = '', $params = [], $expire = 1) {
        $cacheKey = self::genRedisKeyByModel(self::genCacheUnique(['sql' => $sql, 'params' => $params]));
//        var_dump($cacheKey);die;
        # 缓存前缀：用于批量删除
        $tag   = 'ModelData';
        $cache = cache()->store(self::$store);

        if ( $expire > 0 && $cache->tags($tag)->has($cacheKey) ) {
            return $cache->tags($tag)->get($cacheKey);
        }

        $conditions = self::getConditions([ 'data' => $params ]);
        $res        = DB::select($sql, $conditions['bind']);
        $data       = !empty($res) ? ArrHelper::objToArr($res) : [];

        if ( $expire > 0 ) {
            $expire = !empty($data) ? $expire : 30; // 为空的数据只缓存10秒
            $cache->tags($tag)->put($cacheKey, $data, $expire);
        }

        return $data;
    }

    /**
     * 新增资源(返回插入主键ID)
     * @param array $data
     * @return int
     * @author benjamin
     */
    final static function insert2 ( array $data ) {
        return DB::table(static::getTableName())->insert($data);
//        return self::create($data);;
//        return self::db()->table(static::TABLE_NAME)->insert($data);
    }

    /**
     * 批量新增资源
     * @param array $data
     * @return int
     * @author benjamin
     */
    final static function insertAll ( array $data ) {
        return DB::table(static::getTableName())->insert($data);
//        return self::create($data);;
//        return self::db()->table(static::TABLE_NAME)->insert($data);
    }

    /**
     * @title  资源更新
     * @demo   更新条件："`username` = '{$usr}' AND `type` = 0"  |  [ 'username' => $usr, 'type' => 0]
     * @param string|array $condition 更新条件
     * @param array        $data      更新数据
     * @return bool
     * @author benjamin
     */
    public static function update2 ( $condition = [], $data = [] ) {
        if ( static::TABLE_PK && isset($data[static::TABLE_PK]) ) {
            unset($data[static::TABLE_PK]);
        }
        return DB::table(static::getTableName())->where($condition)->update($data);
//        return self::db()->table(static::TABLE_NAME)->where($condition)->update($data);
    }

    /**
     * 原生SQL更新
     * @param       $sql
     * @param array $bind
     * @return mixed
     */
    public static function updateBySql ( $sql, $bind = [] ) {
        return DB::update($sql, $bind);
    }

    /**
     * @title  资源多条件更新完善
     * @param array $condition
     * @param array $data
     * @return mixed
     * @author benjamin
     */
    final static function updateByCondition ( $condition = [], $data = [] ) {
        if ( empty($data) ) return true;

        $table      = self::getTableName();
        $conditions = self::getConditions($condition);

        # 多字段：直接更新
        $set = '';
        foreach ( $data as $k => $v ) {
            $key   = "`{$k}`";
            $value = is_numeric($v) ? $v : "'{$v}'";
            $set   .= "{$key} = {$value},";
        }
        $set = rtrim($set, ",");

        $sql = "UPDATE `{$table}` SET {$set} {$conditions['where']}";

        return self::updateBySql($sql, $conditions['bind']);
    }

    /**
     * @title  普通删除条件使用此删除
     * @demo   删除条件："`username` = '{$usr}' AND `type` = 0"  | [ 'username' => $usr, 'type' => 0] 数据只能处理相等数据
     * @param array|string $condition 删除条件
     * @return bool
     * @author benjamin
     */
    final static function delete2 ( $condition ) {
        if ( is_string($condition) ) {
            return DB::table(self::getTableName())->where($condition)->delete();
        }

        return DB::table(self::getTableName())->where($condition)->delete();
//        return self::db()->table(static::TABLE_NAME)->where($condition)->delete();
    }

    /**
     * @title  复杂删除条件使用条件构造器此删除
     * @param $condition
     * @return mixed
     * @author benjamin
     */
    final static function deleteByCondition ( $condition ) {
        $conditions = self::getConditions($condition);
        return DB::delete($conditions['delSql'], $conditions['bind']);
//        return self::db()->table(static::TABLE_NAME)->deleteBySql($conditions['delSql'], $conditions['bind']);
    }

    /**
     * @title  单字段增量更新
     * @param     $data
     * @param     $field
     * @param int $num
     * @return mixed
     * @author benjamin
     */
    final static function incr ( $data, $field, $num = 1, $where = [] ) {
        return self::incrOrDecr($data, $field, $num, $where);
    }

    /**
     * @title  单字段递减
     * @param     $data
     * @param     $field
     * @param int $num
     * @return mixed
     * @author benjamin
     */
    final static function decr ( $data, $field, $num = 1, $where = [] ) {
        return self::incrOrDecr($data, $field, $num, $where);
    }

    /**
     * @title  通用多字段递增递减
     * @author benjamin
     */
    final static function incrOrDecr ( $data, $field, $num = 1, $where = [] ) {
        $table      = self::getTablePrefix() . static::TABLE_NAME;
        $conditions = self::getConditions([ 'data' => $data, 'where' => $where ]);

        $set = '';
        # 多字段更新
        if ( is_array($field) ) {
            foreach ( $field as $k => $v ) {
                $incrOrDecr = $v > 0 ? '+' : '-';
                $value      = abs($v);
                $set        .= "{$k} = {$k} {$incrOrDecr} {$value},";
            }
            $set = rtrim($set, ",");
        } else {
            # 单字段更新
            $incrOrDecr = $num > 0 ? '+' : '-';
            $value      = abs($num);
            $set        .= "{$field} = {$field} {$incrOrDecr} {$value}";
        }

        $sql = "UPDATE `{$table}` SET {$set} {$conditions['where']}";

//        return self::execute($sql, $data);
        return self::updateBySql($sql, $conditions['bind']);

    }

    /**
     * @title  通用多字段增减量更新、直接更新
     * @param array $condition  更新条件
     * @param array $incrOrDecr 增减量更新字段与数据
     * @param array $update     直接更新字段与数据
     * @return mixed
     * @author benjamin
     */
    final static function incrOrDecrPlus ( array $condition, array $incrOrDecr, array $update = [] ) {
        $table      = self::getTablePrefix() . static::TABLE_NAME;
        $conditions = self::getConditions($condition);

        $set = '';

        # 多字段：直接更新
        foreach ( $update as $k => $v ) {
            $key   = "`{$k}`";
            $value = is_numeric($v) ? $v : "'{$v}'";
            $set   .= "{$key} = {$value},";
        }

        # 多字段：增量减量处理
        foreach ( $incrOrDecr as $k => $v ) {
            $incrOrDecr = $v > 0 ? '+' : '-';
            $value      = abs($v);
            $set        .= "{$k} = {$k} {$incrOrDecr} {$value},";
        }

        $set = rtrim($set, ",");

        $sql = "UPDATE `{$table}` SET {$set} {$conditions['where']}";

//        return self::execute($sql, $conditions['data']);
        return self::updateBySql($sql, $conditions['bind']);
    }

    /**
     * @title 执行一条SQL语句，并返回受影响的行数
     */
    final static function exec ( $sql ) {

        $resule = DB::update($sql);
        if ( $resule || $resule === 0 ) {
            //成功的提示语
            return true;
        } else {

            //失败的提示语
            return false;
        }
    }

    final static function count ( $condition = [] ) {
        $conditions = self::getConditions($condition);
        return self::countBySql($conditions['countSql'], $conditions['bind']);
    }

    final static function countBySql ( $sql, $params ) {
        $countRes = \DB::select($sql, $params);
        return !empty($countRes[0]) ? $countRes[0]->total : 0;

//        $res = self::db()->findBySql($sql, $params);
//        return !empty($res['total']) ? (int)$res['total'] : 0;
    }

    final static function sumBySql ( $sql, $params ) {
        $countRes = \DB::select($sql, $params);
        return !empty($countRes[0]) ? $countRes[0]->sum : 0;

//        $res = self::db()->findBySql($sql, $params);
//        return !empty($res) ? $res['sum'] : 0;
    }

    final static function sumBySearch ( $condition ) {
        $conditions = self::getConditions($condition);
        return self::sumBySql($conditions['sumSql'], $conditions['bind']);
    }

    final static function getCacheDriver ($condition) {
        return !empty($condition['cacheDriver']) && $condition['cacheDriver'] === 'file' ? 'file' : 'redis';
    }

    /**
     * @title 模型缓存文件KEY生成
     * @param string $ext
     * @param bool   $isHot 是否热点KEY
     * @return string
     */
    final static function genFileKeyByModel ( $unique = '', $isHot = false, $ext = 'php' ) {
        return ROOT_PATH . '/customise/cache/cache_' . static::TABLE_NAME .'_' . $unique .'.' . $ext;
//        return RUNTIME_PATH . 'Cache/' . ($isHot ? 'Hot' : '') . 'Model' . ucfirst(static::TABLE_NAME) . $unique . '.' . $ext;
    }

    /**
     * @title  获取列表数据
     * @param array $condition
     * @param null  $arrKey
     * @return array
     */
    final static function getDataList ( array $condition = [], $arrKey = null ) {
        $conditions = self::getConditions($condition);

        # 读取从库
        if ( !empty($condition['useReadDb']) ) {
//            \Core\Mvc\self::useReadDb();
        }

        # 获取所有数据
//        $data = self::db()->findAll($conditions['sql'], $conditions['bind']);
//        $data = Db->findAll($conditions['sql'], $conditions['bind']);
//        var_dump($conditions['sql'], $conditions['bind']);die;
        $data = \DB::select($conditions['sql'], $conditions['bind']);
        $data = !empty($data) ? ArrHelper::objToArr($data) : [];

        # 生成键为 $arrKey 的数组列表
        $arrKey = !empty($condition['arrKey']) ? $condition['arrKey'] : $arrKey;
        $list   = self::genListUseIndex($data, $arrKey);

        # 格式化列表数据
        if ( !empty($condition['formatFunc']) ) {
            $formatFunc = $condition['formatFunc'];
            $list       = static::$formatFunc($list);
        }

        # 是否需要count
        if ( !empty($condition['needCount']) ) {
            $countRes = self::db()->findBySql($conditions['countSql'], $conditions['bind']);
            self::response()->setExtra('total', intval($countRes['total']));
        }

        return !empty($list) ? $list : $data;
    }

    /**
     * @title  获取分页数据
     * @param array $condition
     * @param null  $arrKey
     * @return array
     */
    final static function getPageList ( array $condition = [], $arrKey = null ) {
        $conditions = self::getConditions($condition);

        # 读取从库
        if ( !empty($condition['useReadDb']) ) {
//            self::useReadDb();
        }

        # 获取所有数据
        $data = \DB::select($conditions['limitSql'], $conditions['bind']);
        $data = !empty($data) ? ArrHelper::objToArr($data) : [];

        # 生成主键为 $keyName 的数组列表
        $list = self::genListUseIndex($data, $arrKey);

        # 格式化列表数据
        if ( !empty($condition['formatFunc']) ) {
            $formatFunc = $condition['formatFunc'];
            $list       = static::$formatFunc($list);
        }

        # 获取分页列表
        $countRes = \DB::select($conditions['countSql'], $conditions['bind']);
//        var_dump($countRes, !empty($countRes[0]), $countRes[0]->total);die;

        # 全局响应参数
        $input       = !empty($condition['pager']) ? $condition['pager'] : self::getRequestData();
        $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize    = isset($input['page_size']) ? (intval($input['page_size']) >= 100 ? 100 : intval($input['page_size'])) : config('main')['page_size'];
        $total       = !empty($countRes[0]) ? $countRes[0]->total : 0;
//        $items          = $query->skip($offset)->take($pageSize)->get();

//        self::$PAGER['total']       = $total;
//        self::$PAGER['currentPage'] = $currentPage;
//        self::$PAGER['totalPage']   = intval(ceil($total / $pageSize));
//        return !empty($list) ? $list : [];

        return [ 'list' => $list, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ];

    }

    /**
     * @title   单条数据缓存
     * @param array $condition
     * @param int   $expire
     * @return  mixed
     * @author  benjamin
     */
    final static function getOneByCache ( array $condition = [], $expire = 1 ) {
        $cacheKey = self::genRedisKeyByModel(self::genCacheUnique($condition), !empty($condition['isHot']));

        # 缓存前缀：用于批量删除
        $tag   = 'ModelData';
        $cache = cache()->store(self::$store);

        if ( $expire > 0 && $cache->tags($tag)->has($cacheKey) ) {
            return $cache->tags($tag)->get($cacheKey);
        }

        # 读取从库
        if ( !empty($condition['useReadDb']) ) {
            self::useReadDb();
        }

        $condition['limit'] = '0,1';
        $conditions         = self::getConditions($condition);
        $data               = self::findOneBySql($conditions['sql'], $conditions['bind']);

        if ( $expire > 0 ) {
            $expire = !empty($data) ? $expire : 30; // 为空的数据只缓存10秒
            $cache->tags($tag)->put($cacheKey, $data, $expire);
        }

        return $data;
    }

    /**
     * @title  获取列表数据缓存
     * @param array $condition
     * @param int   $expire
     * @return array
     * @author benjamin
     */
    final static function getListByCache2 ( array $condition = [], $expire = 0 ) {

        # 当前数据缓存key
        $cacheKey = self::genRedisKeyByModel(self::genCacheUnique($condition), !empty($condition['isHot']));

        # 缓存前缀：用于批量删除
        $tag   = 'ModelData';
        $cache = cache()->store(self::$store);

        if ( $expire > 0 && $cache->tags($tag)->has($cacheKey) ) {
            return $cache->tags($tag)->get($cacheKey);
        }

        # 读取从库
        if ( !empty($condition['useReadDb']) ) {
            self::useReadDb();
        }

        # 当前商户
        if ( !empty($condition['addMer']) ) {
            if ( !isset($condition['data']) ) $condition['data'] = [];
            if ( !isset($condition['data']['carrier_id']) ) {
                $condition['data'] = array_merge([ 'carrier_id' => self::getMerId() ], $condition['data']);
            }
        }

        $list = self::getDataList($condition);

        if ( $expire > 0 && !empty($list) ) {
            $redisStatus = $cache->tags($tag)->put($cacheKey, $list, $expire);
        }

        return $list;
    }

    /**
     * @title  获取列表数据缓存
     * @param array $condition
     * @param int   $expire
     * @return array
     * @author benjamin
     */
    final static function getListByCache ( array $condition = [], $expire = 0 ) {

        # 获取缓存
        $cacheDriver = self::getCacheDriver($condition);
        if ( $cacheDriver === 'file' ) {
            $cacheKey   = self::genFileKeyByModel(self::genCacheUnique($condition));
            $cache_file = $cacheKey;
            if ( file_exists($cache_file) && (time() - @filemtime($cache_file)) < $expire && (!empty($cacheList = json_decode(file_get_contents($cache_file), true))) ) {
                return $cacheList;
            }
        } else {
            $cacheKey = self::genRedisKeyByModel(self::genCacheUnique($condition), !empty($condition['isHot']));
            if ( $expire > 0 && !empty($cacheData = self::redis()->get($cacheKey)) ) {
                return $cacheData;
            }
        }

        # 读取从库
        if ( !empty($condition['useReadDb']) ) {
            self::useReadDb();
        }

        $list = $data = self::getDataList($condition);

        # 设置缓存
        if ( $cacheDriver === 'file' ) {
            if ( $expire > 0 && (!empty($data) || !empty($condition['forceCache']) ) ) {
                $fp = fopen($cache_file, 'w');
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        } else {
            if ( $expire > 0 && (!empty($data) || !empty($condition['forceCache']) ) ) {
                self::redis()->set($cacheKey, $data, $expire);
            }
        }

        return $list;
    }

    /**
     * @title  获取分页列表数据缓存
     * @param array $condition
     * @param int   $expire
     * @return array
     * @author benjamin
     */
    final static function getPageListByCache ( array $condition = [], $expire = 0 ) {

        # 当前数据缓存key
        $cacheKey = self::genRedisKeyByModel(self::genCacheUnique(array_merge($condition, [ 'isPageList' => 1 ])), !empty($condition['isHot']));

        # 缓存前缀：用于批量删除
        $tag   = 'ModelData';
        $cache = cache()->store(self::$store);

        if ( $expire > 0 && !empty($cacheList = $cache->tags($tag)->has($cacheKey)) ) {
            # 全局响应参数
            self::response()->setExtra('total', intval($cacheList['total']));
            return $cacheList['list'];
        }

        # 读取从库
        if ( !empty($condition['useReadDb']) ) {
            self::useReadDb();
        }

        $list  = self::getPageList($condition);
        $total = self::response()->extra['total'];

        if ( $expire > 0 && !empty($list) ) {
            $redisStatus = self::redis()->set($cacheKey, [ 'list' => $list, 'total' => $total ], $expire);
        }

        return $list;
    }

    /**
     * @title  生成缓存唯一KEY
     * @tips   避免不同条件获取缓存的数据却相同
     * @param array $data
     * @return string
     * @author benjamin
     */
    final static function genCacheUnique ( $data = [] ) {
        if ( empty($data) ) return '';
        $str = md5(json_encode($data));
        return substr($str, 0, 15);
    }

    /**
     * @title  生成索带索引的数组列表
     * @param      $data
     * @param null $index
     * @return array
     * @author benjamin
     */
    final static function genListUseIndex ( $data, $index = null ) {
        if ( $index !== null ) {
            $list = [];
            foreach ( $data as $k => $v ) {
                if ( !isset($v[$index]) ) break;
                $list[$v[$index]] = $v;
            }
            return $list;
        }
        return $data;
    }

    /**
     * @title 模型缓存REDIS-KEY生成
     * @param string $unique
     * @param bool   $isHot 是否热点KEY
     * @return string
     */
    final static function genRedisKeyByModel ( $unique = '', $isHot = false ) {
        return 'Api_' . ($isHot ? 'Hot' : '') . 'Model' . ucfirst(self::getTableName()) . ($unique ? '_' . $unique : '');
    }

    /**
     * 通用格式化列表数据，用于外部展示
     * @param        $list
     * @param string $scene
     * @return mixed
     */
    public static function formatList ( $list ) {

        $timeColumns = [ 'createTime', 'updateTime', 'regTime', 'lastLogin', 'loginTime', 'accessTime', 'bet_time', 'requestTime', 'create_time', 'update_time', 'time', 'ctime', 'readTime', 'last_update' ];
        $ipColumns   = [ 'regIP', 'lastIP', 'loginIP', 'ip' ];

        foreach ( $list as $k => &$row ) {
            # 时间字段格式化
            foreach ( $timeColumns as $column ) {
                if ( isset($row[$column]) ) {
                    $row[$column] = !empty($row[$column]) ? date('Y-m-d H:i:s', $row[$column]) : '--';
                }
            }
            # IP字段格式化
            foreach ( $ipColumns as $column ) {
                if ( isset($row[$column]) ) {
                    $row[$column] = !empty($row[$column]) && strlen($row[$column]) > 8 ? long2ip($row[$column]) : '--';
//                    $row[$column.'_location'] = (!empty($row[$column]) || $row[$column] !== '--') ? IP::ipLocation($row[$column], []) : '';
                }
            }
        }

        return $list;
    }

    /**
     * 资源新增
     * @param $params
     * @return int
     * @throws \Exception
     */
    public static function addData ( $params) {

        $params[static::CREATED_AT] = static::DATE_FORMAT === 'datetime' ? date('Y-m-d H:i:s') : time();
        
        $status                     = self::insert2($params);
        if ( !$status ) {
            throw new \Exception(static::TABLE_TITLE . '添加失败,' . ERR_MSG);
        }
        return $status;
    }

    /**
     * 资源修改
     * @param $params
     * @return int
     * @throws \Exception
     */
    public static function editData ( $params) {

//        var_dump($params[static::TABLE_PK]);die;
        $record = self::findByPk($params[static::TABLE_PK]);
//        var_dump($record);die;
        if ( empty($record) ) {
            throw new \Exception(static::TABLE_TITLE . '并不存在，操作已被取消!');
        }

        if(static::DATE_FORMAT === 'datetime') {
            $params[static::UPDATED_AT] = date('Y-m-d H:i:s');
        } else {
            $params[static::UPDATED_AT] = time();
        }

        $status                     = self::update2([ static::TABLE_PK => $params[static::TABLE_PK] ], $params);
        if ( !$status ) {
            throw new \Exception(static::TABLE_TITLE . '更新失败,' . ERR_MSG);
        }
        return $status;
    }

    /**
     * 资源删除
     * @param array  $params
     * @param string $delType logic|disk
     * @return bool
     * @throws \Exception
     */
    public static function delData ( array $params, $delType = 'logic' ) {
        $record = self::findByPk($params[static::TABLE_PK]);
        if ( empty($record) ) {
            throw new ErrMsg(static::TABLE_TITLE . '并不存在，操作已被取消!');
        }
        
        if ( static::DATE_FORMAT === 'datetime' ) {
            $params[static::UPDATED_AT] = date('Y-m-d H:i:s');
        } else {
            $params[static::UPDATED_AT] = time();
        }

        if ( $delType === 'logic' ) {
            $status = self::update2([ static::TABLE_PK => $params[static::TABLE_PK] ], [ 'is_delete' => 1, static::UPDATED_AT => $params[static::UPDATED_AT] ]);
        } else {
            $status = self::delete2([ static::TABLE_PK => $params[static::TABLE_PK] ]);
        }

        if ( !$status ) {
            throw new ErrMsg(static::TABLE_TITLE . '更新失败,' . ERR_MSG);
        }
        return $status;
    }


    /**
     * @title  条件构造器（替代链式调用）
     * @param array $options
     * @return array
     * @author benjamin
     */
    final static function getConditions ( array $options = [] ) {
        # 对空数据进行过滤
        $data = !empty($options['data']) ? array_filter($options['data'], function ( $var ) {
            if ( $var !== '' ) return true;
        }) : [];

        # 全局参数
        $params = self::getRequestData();
        $page   = !empty($params['page_index']) ? $params['page_index'] : 1;
        $rows   = !empty($params['page_size']) ? $params['page_size'] : config('main')['page_size'];

        # 所有条件
        $conditions = [
            'table'   => !empty($options['table']) ? $options['table'] : self::getTableName(),
            'alias'   => !empty($options['alias']) ? $options['alias'] : '',
            'columns' => !empty($options['columns']) ? $options['columns'] : '*',
            # data: 普通条件，适用于相等数据 ['usr' => 'xxx', 'type' => 1],  IN查询 ['uid' => [1,2,3,4]]
            'data'    => $data,
            'bind'    => [],
            'leftJoin'=> !empty($options['leftJoin']) ? ' LEFT JOIN ' . $options['leftJoin'] : '',
            'on'      => !empty($options['on']) ? ' ON ' . $options['on'] : '',
            'force'   => !empty($options['force']) ? 'FORCE ' . $options['force'] : '',
            # where: 额外查询条件，直接拆分拼接, ['coin > 10', 'deposit > 100']
            'where'   => !empty($options['where']) && is_array($options['where']) ? implode(' AND ', $options['where']) : '',
            'order'   => !empty($options['order']) ? ' ORDER BY ' . $options['order'] : '',
            'group'   => !empty($options['group']) ? ' GROUP BY ' . $options['group'] : '',
            'limit'   => !empty($options['limit']) ? ' LIMIT ' . $options['limit'] : '',
            'count'   => !empty($options['count']) ? $options['count'] : ' count(1) as total',
            'sum'     => !empty($options['sum']) ? " SUM({$options['sum']}) as sum" : '',
            'page'    => !empty($options['page']) ? $options['page'] : $page,
            'rows'    => !empty($options['rows']) ? $options['rows'] : $rows,
            'rwLock'  => !empty($options['rwLock']) ? ' FOR UPDATE' : '',
        ];

        # 根据参数构建 查询语句
        $i     = 0;
        $where = '';
        $alias = $conditions['alias'];
        $leftJoin = $conditions['leftJoin'];
        $on = $conditions['on'];

        foreach ( $data as $column => $val ) {
            $addAlias = !empty($alias) ? $alias .'.' : '';
            $addAlias = '';

            # 构建预处理绑定语句
            if ( !is_array($val) ) {
                $conditions['bind'][':' . $addAlias . str_replace('.','_', $column)] = StrHelper::strNumToNum($val);
            }

            # 值为数组 使用IN
            if ( is_array($val) ) {
                sort($val);
                $idStr = implode("','", $val);
                $where .= " {$addAlias}{$column} IN ('{$idStr}') ";
            } else {
//                $column = str_replace('.','_', $column);
                $where .= " {$addAlias}{$column} = :" . str_replace('.','_', $column) ;
            }

            $where .= ($i < count($data) - 1 ? ' AND' : '');
            $i++;
        }

        # 构建查询语句
        $conditions['where'] = $conditions['where'] || $where ? 'WHERE ' . $where . ($where && $conditions['where'] ? ' AND ' : '') . $conditions['where'] : '';

        # 组装常用SQL
        $conditions['sql'] = "SELECT {$conditions['columns']} FROM `{$conditions['table']}` {$alias} {$leftJoin} {$on} {$conditions['force']} {$conditions['where']}{$conditions['group']}{$conditions['order']}{$conditions['limit']}{$conditions['rwLock']}";

        # 总条数统计SQL
        $conditions['countSql'] = "SELECT {$conditions['count']} FROM `{$conditions['table']}` {$alias} {$leftJoin} {$on} {$conditions['force']} {$conditions['where']}{$conditions['group']}";
        if ( !empty($conditions['group']) ) {
            $conditions['countSql'] = "SELECT  count(*) as total FROM ({$conditions['countSql']}) count";
        }

        # 相加总额统计SQL
        if ( !empty($conditions['sum']) ) {
            $conditions['sumSql'] = "SELECT {$conditions['sum']} FROM `{$conditions['table']}` {$alias} {$leftJoin} {$on} {$conditions['force']} {$conditions['where']}";
        }

        # 获取分页SQL
        $limitStart             = $conditions['page'] > 0 ? ($conditions['page'] - 1) * $conditions['rows'] : 0;
        $limitEnd               = $conditions['rows'];
        $conditions['limitSql'] = $conditions['sql'] . " limit {$limitStart},{$limitEnd}";

        # 更新SQL
        $conditions['updateSql'] = "UPDATE `{$conditions['table']}` {$conditions['where']}";

        # 删除SQL
        $conditions['delSql'] = "DELETE FROM `{$conditions['table']}` {$conditions['where']}";

//        var_dump($conditions);die;

        return $conditions;
    }
}
