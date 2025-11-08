<?php

namespace App\Models;

use App\Models\Bet\Lottery;
use App\Models\Bet\SourceLottery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use App\Models\RolesModel\PermissionGroup;
use App\Models\RolesModel\Permission;
use App\Models\Conf\CarrierThirdPartPay;
use App\Models\Log\RemainQuota;
use App\Models\Map\CarrierGamePlat;
use App\Models\Map\CarrierGame;
use App\Models\Map\CarrierPreFixGamePlat;
use App\Models\Def\MainGamePlat;
use App\Models\Def\Game;
use App\Models\Def\PayFactory;
use App\Models\Def\SmsPassage;
use App\Models\CarrierIps;
use App\Models\CarrierUser;
use App\Models\CarrierPreFixDomain;
use App\Models\Language;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\CarrierCache;
use Aws\S3\S3Client;
use App\Lib\Clog;

class Carrier extends BaseModel
{
    use Notifiable;

    public $table = 'inf_carrier';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'name',
        'site_url',
        'is_forbidden',
        'remain_quota',
        'sign',
        'cleargameaccount',
    ];

    protected $casts = [
        'id'                 => 'integer',
        'name'               => 'string',
        'sign'               => 'string',
        'remain_quota'       => 'numeric',
        'is_forbidden'       => 'integer',
    ];

    public $rules = [
        'name'                => 'required|min:3|max:32',
        'sign'                => 'required|string|min:4|max:4',
    ]; 

    public $messages = [
        'name.required'               => '名称必须填写',
        'name.min'                    => '名称长席必须3个字符',
        'name.max'                    => '名称长度必须33个字符',
        'sign.required'               => '标识必须填写',
        'sign.sting'                  => '标识必须是字符串',
        'sign.min'                    => '标识长度必须是4个字符',
        'sign.max'                    => '标识长度必须是4个字符',
        'remain_quota.required'       => '额度必须填写',
        'remain_quota.min'            => '额度必须大于等于0',
    ];

    public function request($url)
    {

        $ch = curl_init($url); //请求的URL地址
        \Log::info('请求的URL是'.$url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        $output    = curl_exec($ch);
        $error     = curl_error($ch);
        curl_close($ch);
        
        if (!empty($error)) {
            \Log::info('错误信息是'.$error);
            return false;
        } else {
           $output = json_decode($output,true);
           return $output;
        }
    }

    static function fileUpdate($filename ,$body)
    {
        $flag   = true;
        $init   = [
            'version'     => 'latest',
            'region'      => config("main")['region'], #改为美国西部
            'credentials' => [
                'key'         => config("main")['ossaccessKeyId'], #访问秘钥
                'secret'      => config("main")['accessKeySecret'] #私有访问秘钥
            ]
        ];

        $s3               = new S3Client($init);
        try {
            $result = $s3->putObject([
                'Bucket' => config("main")['s3extra_bucket'],
                'Key'    => $filename,
                'Body'   => $body,
                'ACL'    => 'public-read',
            ]);
        } catch (Aws\S3\Exception\S3Exception $e) {
            $flag =$e->getMessage();
        }
        
        return $flag;
    }

    public function saveItem () {
        $input           = request()->all();
        $validator = Validator::make($input, $this->rules, $this->messages);

        if ( $validator->fails() ) {
            return $validator->errors()->first();
        }

        $sameNameCarrier = self::where('name', request()->name)->first();
        $sameSignCarrier = self::where('sign', strtoupper(request()->sign))->first();

        //判断是否重名
        if ( $this->id ) {
            if ( $sameNameCarrier && $sameNameCarrier->id != $this->id ) {
                return '对不起, 此商户名已存在!';
            }
            if ( $sameSignCarrier && $sameSignCarrier->id != $this->id ) {
                return '对不起, 此商户名标识已被使用!';
            }
        } else {
            if ( $sameNameCarrier ) {
                return '对不起, 此商户名已存在!';
            }

            if ( $sameSignCarrier ) {
                return '对不起, 此商户名标识已被使用!';
            }
        }

        $carrier = self::orderBy('id', 'desc')->first();

        if ( !$this->id ) {
            $isCreateCarrier = true;
            //获取carrier_id 
            $output = $this->request(config('game')['pub']['gameurl'].'/api/carreridadd',[]);
            if(isset($output['success']) && $output['success']===true){
                $this->id = $output['data']['carrier_id'];
            } else {
                return '对不起, 远程CarrierId获取失败!';
            }
        }

        $this->name                     = $input['name'];
        $this->is_forbidden             = $input['is_forbidden'];
        $this->sign                     = strtoupper($input['sign']);
        $this->remain_quota             = $input['remain_quota'];
        $this->save();

        CarrierCache::flushCarrierInit($this->id);

        return true;
    }

    static function getList ( $input ) {
        $query       = self::orderBy('id', 'DESC');
        $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
        $offset      = ($currentPage - 1) * $pageSize;
        $total       = $query->count();
        $items       = $query->skip($offset)->take($pageSize)->get();

        return ['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ];
    }

    public function carrierChangeStatus () {
        $this->is_forbidden = !$this->is_forbidden;
        $this->save();
    }

    public function carrierPermissions () {
        $permissionGroups = PermissionGroup::orderBySort('asc')->with([ 'groups.permissions' ])->topGroup()->get();
        $serviceAdminTeam = CarrierServiceTeam::byCarrierId($this->id)->administrator()->with('teamRoles')->first();

        if ( !$serviceAdminTeam ) {
            return '缺少管理部门,请联系管理员添加';
        }

        $teamPermissions = $serviceAdminTeam->teamRoles->map(function ( $element ) {
            return $element->permission_id;
        })->toArray();

        return [ 'permissionGroups' => $permissionGroups, 'teamPermissions' => $teamPermissions ];
    }

    public function carrierPermissionsSave () {
        $permissions = request()->get('permission', []);

        if ( $permissions ) {
            $count = Permission::whereIn('id', $permissions)->count('id');
            if ( $count != count($permissions) ) {
                return '有不存在的权限数据';
            }
        }

        $serviceAdminTeam = CarrierServiceTeam::byCarrierId($this->id)->administrator()->with('teamRoles')->first();

        if ( !$serviceAdminTeam ) {
            return '缺少管理部门,请联系管理员添加';
        }

        $teamPermissions = $serviceAdminTeam->teamRoles->map(function ( $element ) {
            return $element->permission_id;
        })->toArray();

        $deletePermissions = array_diff($teamPermissions, $permissions);
        $insertPermissions = array_diff($permissions, $teamPermissions);
        $carrier           = $this;
        try {
            \DB::transaction(function () use ( $insertPermissions, $deletePermissions, $serviceAdminTeam, $carrier ) {
                foreach ( $insertPermissions as $permission ) {
                    $serviceTeamRole                = new CarrierServiceTeamRole();
                    $serviceTeamRole->permission_id = $permission;
                    $serviceTeamRole->carrier_id    = $carrier->id;
                    $serviceTeamRole->team_id       = $serviceAdminTeam->id;
                    $serviceTeamRole->save();
                }

                if ( $deletePermissions ) {
                    CarrierServiceTeamRole::permissionIds($deletePermissions)->byCarrierId($carrier->id)->delete();
                }
            });
            return true;
        } catch (\Exception $e) {
            Clog::recordabnormal('部分分配权限操作异常：:'.$e->getMessage()); 
            return $e->getMessage();
        }
    }

    public static function carrierUserList () {
        return CarrierUser::select('inf_carrier_user.*', 'inf_carrier_service_team.team_name')
            ->leftJoin('inf_carrier_service_team', 'inf_carrier_service_team.id', '=', 'inf_carrier_user.team_id')
            ->where('inf_carrier_user.username', '!=', 'super_admin')
            ->where('is_super_admin',1)
            ->get();
    }

    public function carrierGameplats () {
        $allgamePlats    = MainGamePlat::where('status',1)->get();
        $selectgameplats = CarrierGamePlat::where('carrier_id', $this->id)->get();

        return [ 'carrier' => $this, 'allgameplats' => $allgamePlats, 'selectgameplats' => $selectgameplats ];
    }

    public function carrierGameplatsSave () {
        $platIds = request()->get('plat_ids', []);

        if ( !is_array($platIds) ) {
            return '游戏平台不正确';
        }

        $currGamePlatIds   = CarrierGamePlat::where('carrier_id', $this->id)->pluck('game_plat_id')->toArray();
        $addGamePlatIds    = array_diff($platIds, $currGamePlatIds);
        $deleteGamePlatIds = array_diff($currGamePlatIds, $platIds);
        $deleteGameIds     = Game::whereIn('main_game_plat_id', $deleteGamePlatIds)->pluck('game_id')->toArray();

        //删除游戏与平台
        CarrierGame::where('carrier_id', $this->id)->whereIn('game_id', $deleteGameIds)->delete();
        CarrierGamePlat::where('carrier_id', $this->id)->whereIn('game_plat_id', $deleteGamePlatIds)->delete();

        $addMainGamePlats = MainGamePlat::whereIn('main_game_plat_id', $addGamePlatIds)->get();

        //新增游戏平台
        $data = array();


        foreach ( $addMainGamePlats as $key => $value ) {
            $row                   = new  CarrierGamePlat();
            $row->carrier_id       = $this->id;
            $row->game_plat_id     = $value->main_game_plat_id;
            $row->sort             = $value->sort;
            $row->status           = $value->status;
            $row->created_at       = date('Y-m-d H:d:s');
            $row->updated_at       = date('Y-m-d H:d:s');
            $row->point            = 10;
            $row->save();

            $insert['carrier_id']   = $this->id;
            $insert['game_plat_id'] = $value->main_game_plat_id;
            $insert['sort']         = $value->sort;
            $insert['status']       = $value->status;
            $insert['created_at']   = date('Y-m-d H:d:s');
            $insert['updated_at']   = date('Y-m-d H:d:s');
            $insert['point']        = 10;
            $data[]                 = $insert;
        }
        
        $query = Game::whereIn('main_game_plat_id', $addGamePlatIds);

        //新增游戏
        $addGames = $query->get();
        $data     = array();
        foreach ( $addGames as $key => $value ) {
            $insert                     = array();
            $insert['game_plat_id']     = $value->main_game_plat_id;
            $insert['carrier_id']       = $this->id;
            $insert['game_id']          = $value->game_id;
            $insert['sort']             = $value->sort;
            $insert['display_name']     = $value->game_name;
            $insert['en_display_name']  = $value->en_game_name;
            $insert['status']           = $value->status;
            $insert['zh_status']        = $value->zh_status;
            $insert['en_status']        = $value->en_status;
            $insert['is_recommend']     = $value->is_recommend;
            $insert['is_hot']           = $value->is_hot;
            $insert['game_category']    = $value->game_category;
            $insert['created_at']       = date('Y-m-d H:d:s');
            $insert['updated_at']       = date('Y-m-d H:d:s');
            $data[]                     = $insert;

            if(count($data)>500){
                \DB::Table('map_carrier_games')->insert($data);
                 $data  = [];
            }
        }

        if(count($data)>0){
            \DB::Table('map_carrier_games')->insert($data);
        }

        //刷新缓存
        $tag    = 'map_carrier_games_'.$this->id;

        GameCache::flushPlatList($this->id);
        cache()->store('redis')->tags($tag)->flush();

        //更新map_carrier_prefix_game_plats 表
        $carrierGamePlats     =  CarrierGamePlat::all();
        $carrierPreFixDomains = CarrierPreFixDomain::all();

        $insertData           = [];
        foreach ($carrierGamePlats as $key => $value) {
            foreach ($carrierPreFixDomains as $k1 => $v1) {
                $existCarrierPreFixGamePlat = CarrierPreFixGamePlat::where('carrier_id',$value->carrier_id)->where('prefix',$v1->prefix)->where('game_plat_id',$value->game_plat_id)->first();
                if(!$existCarrierPreFixGamePlat){
                    $rows                 = [];
                    $rows['carrier_id']   = $value->carrier_id;
                    $rows['game_plat_id'] = $value->game_plat_id;
                    $rows['point']        = $value->point;
                    $rows['prefix']       = $v1->prefix;
                    $rows['created_at']   = date('Y-m-d H:i:s');
                    $rows['updated_at']   = date('Y-m-d H:i:s');
                    $insertData[]         = $rows;
                }
            }
        }

        \DB::Table('map_carrier_prefix_game_plats')->insert($insertData);


        //删除干掉的平台
        $carrierGamePlatIds     =  CarrierGamePlat::pluck('game_plat_id')->toArray();
        CarrierPreFixGamePlat::whereNotIn('game_plat_id',$carrierGamePlatIds)->delete();

        return true;
    }

    public function carrierPayFactorys () 
    {
        $allgamePayfactorys    = PayFactory::all();
        $selectPayfactorys     = CarrierPayFactory::where('carrier_id', $this->id)->get();

        return [ 'carrier' => $this, 'allgamepayfactorys' => $allgamePayfactorys, 'selectpayfactorys' => $selectPayfactorys ];
    }

    public function carrierPayFactorysSave () 
    {
        $platIds = request()->get('factorys_ids', []);

        if ( !is_array($platIds) ) {
            return '游戏平台不正确';
        }

        $currPayfactoryIds   = CarrierPayFactory::where('carrier_id', $this->id)->pluck('factory_id')->toArray();
        $addPayfactoryIds    = array_diff($platIds, $currPayfactoryIds);
        $deletePayfactoryIds = array_diff($currPayfactoryIds, $platIds);

        //删除厂商
        CarrierPayFactory::where('carrier_id', $this->id)->whereIn('factory_id', $deletePayfactoryIds)->delete();

        //新增游戏平台
        $data = array();

        foreach ( $addPayfactoryIds as $key => $value ) {
            $insert['carrier_id']   = $this->id;
            $insert['factory_id']   = $value;
            $insert['created_at']   = date('Y-m-d H:d:s');
            $insert['updated_at']   = date('Y-m-d H:d:s');
            $data[]                 = $insert;
        }
        
        \DB::Table('inf_carrier_pay_factory')->insert($data);

        return true;
    }

    public function carrierRemainQuotaAdd () {
        $amount = request()->get('amount', 0);

        if ( !is_numeric($amount) ) {
            return '对不起！参数错误';
        }

        if ($this->remain_quota + $amount < 0 ) {
            return '对不起！调整额度后，商户的额度不能少于0';
        }

        try {
            \DB::beginTransaction();
            $remainQuota                      = new RemainQuota();
            $remainQuota->carrier_id          = $this->id;
            $remainQuota->amount              = $amount;
            $remainQuota->direction           = $amount > 0 ? 3 : 4;
            $remainQuota->mark                = '管理员调整额度';
            $remainQuota->before_remainquota  = $this->remain_quota;
            $remainQuota->remainquota         = bcadd($this->remain_quota, $amount, 2);
            $remainQuota->save();

            $this->remain_quota = $remainQuota->remainquota;
            $this->save();

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollback();
            Clog::recordabnormal('管理员调整额度异常：:'.$e->getMessage()); 
            return '操作异常：' . $e->getMessage();
        }

    }

    public function carrierCasinoAdd () {
        $input = request()->all();
        if ( !isset($input['apiusername']) ) {
            return '对不起！缺少apiusername参数';
        }

        if ( !isset($input['apipassword']) ) {
            return '对不起！缺少apipassword参数';
        }

        if ( !isset($input['apikey']) ) {
            return '对不起！缺少apikey参数';
        }

        $this->apiusername = $input['apiusername'];
        $this->apipassword = $input['apipassword'];
        $this->apikey      = $input['apikey'];
        $this->save();

        CarrierCache::forgetCarrier($this->sign);

        return true;
    }

    /**
     * 商户彩票分配逻辑
     * @param $id
     * @param $lottIdAry
     * @return bool
     * @throws \Exception
     */
    public static function carrierLotterySave ( $id, $lottIdAry ) {

        $lotteryList = SourceLottery::getDataList([ 'data' => [ 'id' => $lottIdAry ] ]);

        try {

            Lottery::begin();

            foreach ( $lotteryList as $lott ) {

                # 已分配的彩种则跳过
                if ( Lottery::findOne([ 'carrier_id' => $id, 'lott_id' => $lott['lott_id'] ]) ) continue;

                # 复制彩票数据
                $lott['carrier_id'] = $id;
                $lott['lott_id']    = $lott['lott_id'];

                $lott['created_at'] = time();
                $lott['updated_at'] = time();
                unset($lott['lott_id']);

                $status = Lottery::insert2($lott);
                if ( !$status ) throw new \Exception('彩种分配异常，请稍后重试!');

                # 复制开奖数据 todo
                # 复制玩法组 todo
                # 复制子玩法 todo

            }

            Lottery::commit();

        } catch (\Exception $e) {

            Lottery::rollback();
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    public function carrierUsers () {
        return $this->hasMany(CarrierUser::class, 'carrier_id', 'id');
    }

    public function mapGamePlats () {
        return $this->hasMany(CarrierGamePlat::class, 'carrier_id', 'id');
    }

    public function carrierPayChannels () {
        return $this->hasMany(CarrierPayChannel::class, 'carrier_id', 'id');
    }

    public function carrierIps () {
        return $this->hasMany(CarrierIps::class, 'carrier_id', 'id');
    }

    public function thirdPartPayConf () {
        return $this->hasMany(CarrierThirdPartPay::class, 'carrier_id', 'id');
    }

    public function serviceTeams () {
        return $this->hasMany(CarrierServiceTeam::class, 'carrier_id', 'id');
    }


    public static function getSiteBalance ($id, $rwLock = false ) {

        $info = self::findOneBySearch([
            'data'    => [ 'id' => $id ],
            'columns' => 'remain_quota as amount',
            'rwLock'  => $rwLock,
        ]);

        return !empty($info['amount']) ? $info['amount'] : 0;
    }
}
