<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Models\Conf\CarrierPayChannel;
use App\Models\CarrierBankCard;
use App\Models\Conf\CarrierWebSite;
use App\Models\Map\CarrierPlayerLevelBankCard;
use App\Lib\Cache\CarrierCache;


class CarrierPlayerGrade extends BaseModel
{

    public $table = 'inf_carrier_player_grade';

    const TABLE_PK   = 'id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [

    ];

    protected $casts = [

    ];

    public  $rules = [
        'level_name'                 => 'required|string',
        'prefix'                     => 'required|string',
        'is_default'                 => 'required|in:0,1',
        'withdrawcount'              => 'required|min:1|integer',
        'updategift'                 => 'required|min:0|integer',
        'birthgift'                  => 'required|min:0|integer',
        'sort'                       => 'required|integer',
        'availablebet'               => 'required|min:0|integer',
        'weekly_salary'              => 'required|min:0|integer',
        'monthly_salary'             => 'required|min:0|integer',
        'turnover_multiple'          => 'required|min:0|integer',
    ];

    public $messages = [
        'level_name.required'     => '会员等级名必须填写',
        'prefix.required'         => '站点必须填写',
        'is_default.required'     => '是否默认必须填写',
        'is_default.in'           => '是否默认取值不正确',
        'withdrawcount.required'  => '每日提款次数必须填写',
        'withdrawcount.min'       => '每日提款次数必须大于0',
        'updategift.required'     => '升级礼金必须填写',
        'updategift.min'          => '升级礼金必须大于-1',
        'birthgift.required'      => '生日礼金必须填写',
        'birthgift.min'           => '生日礼金必须大于-1',
        'sort.required'           => '排序必须填写',
        'sort.integer'            => '排序必须为整数',
        'availablebet.required'   => '有效投注必须填写',
        'availablebet.min'        => '有效投注必须大于-1',
        'availablebet.integer'    => '有效投注必须为整数',
        'birthgift.integer'       => '生日礼金必须为整数',
        'updategift.integer'      => '升级礼金必须为整数',
        'withdrawcount.integer'   => '每日提款次数必须为整数',
        'weekly_salary.required'  => '周礼金金额必须填写',
        'weekly_salary.min'       => '周礼金金额必须大于-1',
        'weekly_salary.integer'   => '周礼金金额必须为整数',
        'monthly_salary.required'  => '月礼金金额必须填写',
        'monthly_salary.min'       => '月礼金金额必须大于-1',
        'monthly_salary.integer'   => '月礼金金额必须为整数',
        'turnover_multiple.required'  => '彩金流水倍数必须填写',
        'turnover_multiple.min'       => '彩金流水倍数必须大于-1',
        'turnover_multiple.integer'   => '彩金流水倍数必须为整数',
    ];

    public function playerLevelAdd($carrier)
    {
        $input     = request()->all();
        $validator = Validator::make($input, $this->rules, $this->messages);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $samesort           = self::where('carrier_id',$carrier->id)->where('prefix',$input['prefix'])->where('sort',$input['sort'])->first();
        $upsort             = self::where('carrier_id',$carrier->id)->where('prefix',$input['prefix'])->where('sort','>',$input['sort'])->orderBy('sort','asc')->first();
        $downsort           = self::where('carrier_id',$carrier->id)->where('prefix',$input['prefix'])->where('sort','<',$input['sort'])->orderBy('sort','desc')->first();
        $defalutPlayerLevel = self::where('carrier_id',$carrier->id)->where('prefix',$input['prefix'])->where('is_default',1)->first();

        if($this->id) {
            if($samesort && $samesort->id != $this->id) {
                return '对不起,排序不能重复';
            }

            if($input['is_default'] && $defalutPlayerLevel && $defalutPlayerLevel->id != $this->id) {
                return '对不起,默认会员等级已存在';
            }
        } else {
            if($samesort) {
                return '对不起,会员等级已存在';
            }
            if($input['is_default'] && $defalutPlayerLevel) {
                return '对不起,默认会员等级已存在';
            }
        }

        if($upsort) {
            $upgradeRule = unserialize($upsort->upgrade_rule);

            if($upgradeRule['availablebet'] < $input['availablebet']) {
                return '对不起,有效投注必须小于等于上级会员有效投注';
            }

            if($upsort->weekly_salary<$input['weekly_salary']){
                return '对不起,周礼金必须小于等于上级会员周礼金';
            }

            if($upsort->monthly_salary<$input['monthly_salary']){
                return '对不起,月礼金必须小于等于上级会员月礼金';
            }
        }

        if($downsort) {
            $downgradeRule = unserialize($downsort->upgrade_rule);

            if($downgradeRule['availablebet'] > $input['availablebet']) {
                return '对不起,有效投注必须小于等于下级会员有效投注';
            }

            if($downsort->weekly_salary>$input['weekly_salary']){
                return '对不起,周礼金必须大于等于下级会员周礼金';
            }

            if($downsort->monthly_salary>$input['monthly_salary']){
                return '对不起,月礼金必须大于等于下级会员月礼金';
            }
        }

        $this->carrier_id                   = $carrier->id;
        $this->prefix                       = $input['prefix'];
        $this->level_name                   = $input['level_name'];
        $this->is_default                   = $input['is_default'];
        $this->withdrawcount                = $input['withdrawcount'];
        $this->updategift                   = $input['updategift'];
        $this->birthgift                    = $input['birthgift'];
        $this->sort                         = $input['sort'];
        $this->weekly_salary                = $input['weekly_salary'];
        $this->monthly_salary               = $input['monthly_salary'];
        $this->turnover_multiple            = $input['turnover_multiple'];
        $this->upgrade_rule                 = serialize(['availablebet'=>$input['availablebet']]);
        $this->save();

        return true;
    }

    public function playerLevelDel()
    {
        if($this->is_default) {
            return '对不起,默认会员等级不能删除';
        }

        $this->delete();

        return true;
    }
}
