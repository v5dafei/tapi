<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CarrierPreFixDomain;

class CarrierActivity extends Model
{
    public $table    = 'inf_carrier_activity';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
        'carrier_id',
        'act_type_id',
        'name',
        'bonuses_type',
        'game_category',
        'rebate_financial_bonuses_step_rate_json',
        'apply_times',
        'apply_way',
        'censor_way',
        'image_id',
        'mobile_image_id',
        'apply_rule_string',
        'content',
        'is_static',
        'status',
        'person_account',
        'startTime',
        'endTime',
        'account',
        'gift_amount',
        'sort',
    ];

    protected $casts = [
    ];

    public $firstrules = [
        'name'                                    => 'required',
        'act_type_id'                             => 'required|in:1,2,3',
        'sort'                                    => 'required|integer|min:0',
        'image_id'                                => 'required|exists:inf_carrier_img,id',
        'mobile_image_id'                         => 'required|exists:inf_carrier_img,id'
    ];

    public $tworules = [
        'bonuses_type'                            => 'required|in:1,2',
        'rebate_financial_bonuses_step_rate_json' => 'required',
        'game_category'                           => 'required|in:-1,0,1,2,3,4,5,6,7',
        'apply_way'                               => 'required|in:1,2',
        'apply_times'                             => 'required|in:0,1,2,3,4',
        'censor_way'                              => 'required|in:1,2,3',
        'apply_rule_string'                       => 'required',
    ];


    public $messages = [
        'act_type_id.required'                    => '活动类型必须填写',
        'act_type_id.in'                          => '活动类型取值不正确',
        'name.required'                           => '活动名称必须填写',
        'bonuses_type.required'                   => '红利类型必须填写',
        'rebate_financial_bonuses_step_rate_json' => '红利类型阶梯必须填写',
        'game_category.required'                  => '限流水平台必须填写',
        'apply_way.required'                      => '是否主动申请必须填写',
        'apply_way.in'                            => '是否主动申请取值不正确',
        'apply_times.required'                    => '会员申请次数必须填写',
        'apply_times.in'                          => '会员申请次数取值不正确',
        'censor_way.required'                     => '审核方式必须填写',
        'censor_way.in'                           => '审核方式取值不正确',
        'image_id.required'                       => 'PC端活动图片必须填写',
        'image_id.exists'                         => 'PC端活动图片不存在',
        'mobile_image_id.required'                => '手机端活动图片必须填写',
        'mobile_image_id.exists'                  => '手机端活动图片不存在',
        'apply_rule_string.required'              => '申请规则必须填写',
        'sort.required'                           => '排序必须填写',
        'sort.min'                                => '排序取值不正确',
        'sort.integer'                            => '排序取值不正确',
    ];

    public static function activitiesList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = CarrierActivity::select('id','name','act_type_id','bonuses_type','apply_way','censor_way','status','sort','created_at','startTime','endTime','vi_name','en_name','th_name','id_name','hi_name','prefix')->where('carrier_id',$carrier->id)->orderBy('sort','desc');

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($data as $k => $v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
