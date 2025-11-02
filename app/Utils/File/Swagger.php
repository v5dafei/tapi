<?php
/**
 * 接口文档生成及测试工具集成
 *
 * @link    https://www.cnblogs.com/JoiT/p/6378086.html 入门文档
 * @example Docs/swagger/FH-API-DOC.json
 * @author  benjamin
 */

namespace App\Utils\File;

use Core\Plugin;
use App\Utils\Helper;

class Swagger extends Plugin
{

    protected $project = 'app';
    protected $projectName = '';

    protected $_docDir   = '';
    protected $_docFile  = 'fh-api-doc';
    protected $_docExt   = '.json';
    protected $_filename = null;

    protected $_paths       = [];
    protected $_tags        = [];
    protected $_definitions = [];
    protected $_security    = [
//        "API_AUTH" => [
//            "description" => "前台非公共请求必须传递: token , 在对应请求数据里",
//            "type"        => "apiKey",
//            "in"          => "query",
//            "name"        => "token",
//        ],
        "API_AUTH" => [
            "description" => "前台非公共请求必须传递: token , 在对应请求数据里",
            "type"        => "apiKey",
            "in"          => "header",
            "name"        => "auth-token",
        ],
    ];

    /**
     * 请求支持数据类型
     * @var string[]
     */
    protected $_reqDataType = [
//        "*/*",
        "application/x-www-form-urlencoded",
//        "application/json",
    ];

    /**
     * 响应支持数据类型
     * @var string[]
     */
    protected $_resDataType = [
//        "application/x-www-form-urlencoded",
        "application/json",
    ];

    public $conf = [
        'pathInfoType' => 'requestUrl'
    ];


    protected $apiConfig     = [];
    protected $swaggerConfig = [
        'swagger'             => '2.0',
        'info'                => [
            "description"    => "调用登录接口后，将API-SID填入下方Authoriza-value里，所有需要登录的接口会自动填充登录授权token，表单里的对应token字段留空即可",
            "version"        => "1.0.1",
            "title"          => "凤凰app - 接口文档 1.0.1",
            "termsOfService" => "http://swagger.io/terms/",
            "contact"        => [
                "name" => "http://gd.fhptbet.com/my/",
            ],
            "license"        => [
                "name" => "Apache 2.0",
                "url"  => "http://www.apache.org/licenses/LICENSE-2.0.html"
            ]
        ],
        "host"                => "www.fh-api.service",
        "basePath"            => "/",
        "schemes"             => [
            "https",
            "http"
        ],
        "paths"               => [],
        "securityDefinitions" => [
//            "demo" => [
//                "type" => "apiKey",
//                "in"   => "header",  // 在header头里
//                "name" => "API-AUTH-TOKEN",
//            ],
        ],
//        "security"            => [
//            "API_AUTH" => [],
//            "APP_ID" => [],
//        ],
        "definitions" => [
            'RepMessage' => [
                "title"       => "RepMessage",
                "description" => "返回数据接口实体",
                'type'        => 'object',
                'properties' => [
                    'code' => [ 'type' => 'integer', 'description' => '错误编号：0为请求成功，其它为请求异常' ],
                    'msg'  => [ 'type' => 'string', 'description' => '错误信息：请求异常时的异常描述' ],
                    'data' => [ 'type' => 'object', 'description' => '返回数据：请求成功时返回的对应数据' ],
                ]
            ],
            'RepResult' => [
                '200' => [
                    "title"       => "200",
                    "description" => "返回数据接口实体",
                    'type'        => 'object',
                    'properties'  => [
                        'code' => [ 'type' => 'integer', 'description' => '错误编号：0为请求成功，其它为请求异常' ],
                        'msg'  => [ 'type' => 'string', 'description' => '错误信息：请求异常时的异常描述' ],
                        'data' => [ 'type' => 'object', 'description' => '返回数据：请求成功时返回的对应数据' ],
                    ]
                ],
                '401' => [ 'description' => '未登录', ],
                '403' => [ 'description' => '无访问权限', ],
                '404' => [ 'description' => '资源不存在或已被删除', ],
                '500' => [ 'description' => '服务器异常', ],
            ]
        ]
    ];

    public function __construct ( $apiConf, $otherConf = [] ) {
        $this->apiConfig = $apiConf;
        $this->parseConfig($otherConf);
    }

    /**
     * @title swagger.json 文件生成
     */
    public function run () {
        # 设置相关配置
        $this->swaggerConfig['info']['title'] = $this->projectTitle ?? "凤凰" .strtoupper($this->project). " - 接口文档 " . $this->swaggerConfig['info']['version'];
        $this->swaggerConfig['paths'] = $this->_paths;
        if ( !empty($this->_definitions) ) $this->swaggerConfig['definitions'] = $this->_definitions;
        if ( !empty($this->_security) ) $this->swaggerConfig['securityDefinitions'] = $this->_security;
        if ( !empty($this->_tags) ) $this->swaggerConfig['tags'] = $this->_tags;
        $data = $this->swaggerConfig;

        # 生成json文件
        $this->_docDir   = ROOT_PATH . '/wjapp/Docs/swagger';
        $this->_docDir   = ROOT_PATH . '/public/api-doc/static';
        $docName = $this->project === 'app' ? $this->_docFile : 'fh-' . $this->project . '-api-doc';
        $this->_filename = $this->_docDir . DS . $docName . $this->_docExt;

        # 文件权限
//        if (!is_writable($this->_filename)) chmod($this->_filename, 0775);

        # fopen写入
        $fileHandle = fopen($this->_filename, "w+");
        fwrite($fileHandle, json_encode($this->swaggerConfig, JSON_UNESCAPED_UNICODE));
        fclose($fileHandle);

        # 直接写入
//        file_put_contents($filename, json_encode($this->swaggerConfig, JSON_UNESCAPED_UNICODE));

        # 文件权限
        if (!is_writable($this->_filename)) chmod($this->_filename, 0775);

        return $data;
    }

    /**
     * @title 转换项目接口配置
     * 主要转换path相关配置
     */
    private function parseConfig ( $otherConf = [] ) {
        if ( !empty($otherConf['pathInfoType']) ) {
            $this->conf['pathInfoType'] = $otherConf['pathInfoType'];
        }

        if ( !empty($otherConf['project']) ) {
            $this->project = $otherConf['project'];
        }

        if ( !empty($otherConf['projectTitle']) ) {
            $this->projectTitle = $otherConf['projectTitle'];
        }

        $this->parseTags();
        $this->parsePaths();

    }

    /**
     * @title 设置访问的主机或域名
     * @param $host
     * @return $this
     */
    public function setHost ( $host ) {
        $this->swaggerConfig['host'] = $host;
        return $this;
    }

    /**
     * @title 设置请求路径  /v2
     * @param $basePath
     * @return $this
     */
    public function setBasePath ( $basePath ) {
        $this->swaggerConfig['basePath'] = $basePath;
        return $this;
    }

    /**
     * @title 设置接口相关描述
     * @param array $info
     * @return $this
     */
    public function setInfo ( array $info = [] ) {
        foreach ( $info as $k => $v ) {
            if ( isset($this->swaggerConfig[$k]) ) {
                $this->swaggerConfig[$k] = $v;
            }
        }
        return $this;
    }

    /**
     * @title 设置访问协议 http/https
     * @param $schemes
     * @return $this
     */
    public function setSchemes ( $schemes ) {
        $this->swaggerConfig['schemes'] = $schemes;
        return $this;
    }

    /**
     * @title 设置可全局引用的配置
     * @param       $key
     * @param array $config
     * @return $this
     */
    public function setDefinition ( $key, array $config = [] ) {
        $this->_definitions[$key] = $config;
        return $this;
    }

    /**
     * @title 设置授权相关配置
     * @param       $key
     * @param array $config
     * @return $this
     */
    public function setSecurity ( $key, array $config = [] ) {
        $this->_security[$key] = $config;
        return $this;
    }

    /**
     * @title 设置每个请求的具体配置
     * @param array $config
     */
    protected function setPaths ( array $config ) {
        $this->_paths = $config;
    }

    /**
     * todo 待完善和细化：长期
     *
     * @title 将 API 配置转换成 swagger->paths 配置
     * @return bool
     */
    private function parsePaths () {
        if ( empty($this->apiConfig['paths']) ) return false;

        $paths = $this->apiConfig['paths'];
        $data  = [];

        $getTagsName = function (array $tags) {
            $name = [];
           foreach ($tags as $tag){
               if(!empty($this->apiConfig['tags'][$tag])) {
                   $name[] = $this->apiConfig['tags'][$tag]['name'];
               }
           }
           return $name;
        };

        foreach ( $paths as $path => $conf ) {
            $tmp              = [];
            $httpMethod       = strtolower($conf['http_method']);
            $tmp[$httpMethod] = [];

            $v               = [];
            $v['tags']       = !empty($conf['tags']) ? $getTagsName(explode(',', $conf['tags'])) : '其它';
            $v['summary']    = $conf['title'];
            // 请求数据类型
            $v['consumes']   = $this->_reqDataType;
            // 响应数据类型
            $v['produces']   = $this->_resDataType;
            // 接口参数声明
            $v['parameters'] = [];
            $v['responses']  = [];
            $v['security']   = [];

            # 富文本接口说明
            if ( !empty($conf['description']) ) {
                $v['description'] = $conf['description'];
            }

            # 权限模板
            if ( empty($conf['is_free']) ) {
                $auth            = [ 'API_AUTH' => [] ];
                $v['security'][] = $auth;
            }

            # 响应说明
            $v['responses']['200'] = [
                'description' => '成功请求的响应',
                'schema'      => [
                    "originalRef" => "RepMessage",
                    '$ref'        => "#/definitions/RepMessage"
                ]
            ];
            $v['responses']['401'] = [ 'description' => '未登录', ];
            $v['responses']['403'] = [ 'description' => '无访问权限', ];
            $v['responses']['404'] = [ 'description' => '资源不存在或已被删除', ];
            $v['responses']['500'] = [ 'description' => '服务器异常', ];

//            $v['responses'] = [
//                'schema'      => [
//                    "originalRef" => "RepResult",
//                    '$ref'        => "#/definitions/RepResult"
//                ]
//            ];

            $getDataIn = function ($name, $rule, $httpMethod) {
                if($name === 'files') {
//                if($name === 'files' || $httpMethod==='post') {
                    return 'formData';
                }
                return 'query';
            };

            $getIntro = function ($name, $rule){
                $intro = $rule['msgPrefix'];
                if($name === 'token') $intro = $rule['msgPrefix'].'(Authorize配置后会自动填充，留空即可)';
                if($name === 'pwd' || (!empty($rule['check']) && $rule['check'] === 'isPwd')) $intro = $rule['msgPrefix'].'(需要md5加密后在录入)';
                return $intro;
            };

            $getParams = function ($pk, $pv, $ppk='', $ppv=[]) use($getDataIn, $getIntro, $httpMethod) {
                $ptmp                = [];
                $ptmp['in']          = $getDataIn($pk, $pv, $httpMethod);
                $ptmp['name']        = !empty($ppk) ? $ppk.'['. $pk .']' : $pk;
                $ptmp['description'] = $getIntro($pk, $pv);
                $ptmp['type']        = empty($pv['check']) ? 'string' : $this->getDataType($pv['check'], $pk);
                $ptmp['required']    = empty($ppv['canEmpty']) && empty($pv['canEmpty']) && !in_array($pk, ['token']) ? true : false;

                # 自动设置默认值
                if ( !empty($pv['default']) ) {
                    $ptmp['default'] = $pv['default'];
                    $ptmp['x-example"'] = $pv['default'];
                }
                # 自动下拉多选
                if(!empty($pv['check']) && !empty($pv['in'])) {
                    $ptmp['enum'] = array_merge([''], $pv['in']);
                }
                return $ptmp;
            };

            # 生成参数详情
            if ( !empty($conf['data_rules']) ) {
                foreach ( $conf['data_rules'] as $pk => $pv ) {
//                    $ptmp                = [];
//                    $ptmp['in']          = $getDataIn($pk, $pv, $httpMethod);
//                    $ptmp['name']        = $pk;
//                    $ptmp['description'] = $getIntro($pk, $pv);
//                    $ptmp['type']        = empty($pv['check']) ? 'string' : $this->getDataType($pv['check']);
//                    $ptmp['required']    = empty($pv['canEmpty']) && !in_array($pk, ['token']) ? true : false;
//
//                    # 自动设置默认值
//                    if ( !empty($pv['default']) ) {
//                        $ptmp['default'] = $pv['default'];
//                        $ptmp['x-example"'] = $pv['default'];
//                    }
//                    # 自动下拉多选
//                    if(!empty($pv['check']) && !empty($pv['in'])) {
//                        $ptmp['enum'] = $pv['in'];
//                    }

                    // 值为数组的处理
                    if ( !empty($pv['check']) && $pv['check'] === 'isArray|rules' && !empty($pv['rules']) ) {
                        foreach ( $pv['rules'] as $pk1 => $pv1 ) {
                            $v['parameters'][] = $getParams($pk1, $pv1, $pk, $pv);
                        }
                    } else {
                        $v['parameters'][] = $getParams($pk, $pv);
                    }

                }
            }

            # 生成接口路径
            $v['operationId'] = $path.$httpMethod;
            if ( $this->conf['pathInfoType'] === 'paramsCA' ) {
                $paths = explode('_', $path);
                $k     = '/?c=' . $paths[0] . '&a=' . $paths[1]; // $data['/user/login'] 路径转换2
            } else {
                $k = '/' . str_replace('_', '/', $path); // $data['/user/login'] 路径转换1
            }

            $tmp[$httpMethod] = $v;  // $tmp['get'] = $config
            $data[$k]         = $tmp;        // $data['/user/login'] = ['get' => ['xxx']];
        }

        $this->setPaths($data);
    }

    /**
     * @title API接口按标签分组
     */
    private function parseTags () {
        if ( empty($this->apiConfig['tags']) ) return false;
        $arr = [];
        foreach ( $this->apiConfig['tags'] as $tag => $info ) {
            $arr[] = [
                'name'        => $info['name'],
                'description' => $tag,
            ];
        }
        $this->_tags = $arr;
    }

    /**
     * todo 待完善和细化：长期
     *
     * @title 根据校验规则 获取数据类型
     */
    private function getDataType ( $types, $name ='' ) {
        $type  = '';
        $types = strpos('|', $types) !== -1 ? explode('|', $types) : [ $types ];

        $dataType = [
            'number' => [ 'isInt', 'isNum', 'isAmount', 'isMobile' ],
            'string' => [ 'isUsr', 'isPwd', 'isChinese' ],
            'array'  => [ 'isArray' ],
        ];

        foreach ( $types as $t ) {
            if ( in_array($t, $dataType['number']) ) {
                $type = 'integer';
            }
            if ( in_array($t, $dataType['string']) ) {
                $type = 'string';
            }
            if ($name === 'files' && in_array($t, $dataType['array']) ) {
                $type = 'file';
            }
            if ( $type ) break;
        }

        return $type ? $type : 'string';
    }

    /**
     * @title 生成文档响应数据的格式
     * @param $data
     * @return array
     */
    private function getReturnData ( $data ) {
        $return = [];
        foreach ( $data as $k => $v ) {
            $return[$k] = [ 'type' => gettype($v) ];
        }
        return $return;
    }

    public function getFilename () {
        return $this->_filename;
    }
}