<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Lib\Cache\PlayerCache;
use App\Models\Player;

class PlayerMessage extends Model
{    
    public $table = 'inf_player_message';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
        'player_id',
        'carrier_id',
        'title',
        'content',
        'is_read',
        'admin_id'
    ];

    protected $casts = [
    
    ];

    public  static  $rules = [
        'title'        => 'required',
        'content'      => 'required',
        'player_ids'   => 'required',
    ];

    public static $messages = [
        'title.required'                  => '标题必须填写',
        'content.required'                => '内容必须填写',
        'player_ids.required'             => '玩家必须填写'
    ];

    static function messageSave($carrierUser,$carrier)
    {
        $input          = request()->all();
        $validator      = Validator::make($input, self::$rules, self::$messages);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        if(!is_array($input['player_ids'])) {
            return '对不起, 参数不正确';
        }

        if($input['player_ids'][0]!=0){
            $playerCounts = Player::whereIn('player_id',$input['player_ids'])->where('carrier_id',$carrier->id)->count();

            if(count($input['player_ids']) != $playerCounts) {
                return '对不起, 您无权操作';
            }
        } 

        if(!isset($input['type']) || !in_array($input['type'],[1,2])){
            return '对不起, 参数取值不正确';
        }

        if(is_null($input['player_ids'][0])){
            if($input['type']==1){
                $playerIds = Player::where('carrier_id',$carrier->id)->where('win_lose_agent',0)->where('is_tester',0)->pluck('player_id')->toArray();
            } else{
                $playerIds = Player::where('carrier_id',$carrier->id)->where('win_lose_agent',1)->where('is_tester',0)->pluck('player_id')->toArray();
            }
            $input['player_ids'] = $playerIds;
        }

        $data = [];

        foreach ($input['player_ids'] as  $value) {
            $row               = [];
            $row['carrier_id'] = $carrier->id;
            $row['player_id']  = $value;
            $row['title']      = $input['title'];
            $row['content']    = $input['content'];
            $row['admin_id']   = $carrierUser->id;

            if($input['type']==1){
                $row['type']   = 1;
            } else{
                $row['type']   = 2;
            }

            $row['is_read']    = 0;
            $row['created_at'] = date('Y-m-d H:i:s');
            $row['updated_at'] = date('Y-m-d H:i:s');
            $data[]            = $row;

            if(count($data)==1000){
                \DB::table('inf_player_message')->insert($data);
                $data = [];
            }
        }
        if(count($data)){
            \DB::table('inf_player_message')->insert($data);
        }

        return true;
    }

    static function messageList($carrierUser,$carrierId)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::select('inf_player.user_name','inf_carrier_user.username','inf_player_message.*')
            ->leftJoin('inf_player','inf_player.player_id','=','inf_player_message.player_id')
            ->leftJoin('inf_carrier_user','inf_carrier_user.id','=','inf_player_message.admin_id')
            ->where('inf_player.carrier_id',$carrierId)
            ->orderBy('inf_player_message.id','desc');

        if(isset($input['user_name']) && trim($input['user_name']) == '') {
            $query->where('inf_player.user_name',$input['user_name']);
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        foreach ($data as $key => &$value) {
            if(is_null($value->user_name)){
                $value->user_name = '全体';
            }
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
