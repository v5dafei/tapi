<?php
namespace App\Models\Def;

use Illuminate\Database\Eloquent\Model;
use App\Models\Def\PayFactory;

class PayChannel extends Model
{

    public $table = 'def_pay_channel_list';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'channel_code',
        'name',
        'has_realname'
    ];

    protected $casts = [
        'name' => 'string',
        'channel_code' => 'string'
    ];

    public static $rules = [];

    static function getList() 
    {
        $input = request()->all();
        $query = self::select('def_pay_channel_list.*','def_pay_factory_list.factory_name')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->orderBy('def_pay_channel_list.id','asc');

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $total          = $query->count();
        $data           = $query->skip($offset)->take($pageSize)->get();
        $payFactories   = PayFactory::all();
        $option         = [];

         foreach ($payFactories as $key => $value) {
            $row          = [];
            $row['value'] = $value->id;
            $row['label'] = $value->factory_name;
            $option[]     = $row;
         }

        return ['data' => $data,'option'=>$option, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function saveItem()
    {
        $input      = request()->all();
        $payChannel = self::where('factory_id',$input['factory_id'])->where('channel_code',$input['channel_code'])->where('type',$input['type'])->first();
        if($this->id) {
            if($payChannel && $payChannel->id != $this->id) {
                 return '对不起, 此渠道已经存在!';
            }
        } else {
            if($payChannel) {
                return '对不起, 此渠道已经存在!';
            }
        }

        if(!isset($input['is_smallamountpay']) || !in_array($input['is_smallamountpay'], [0,1])){
            return '对不起, 是否小额取值不正确!';
        }

        if(!isset($input['min']) || !is_numeric($input['min']) || $input['min']<0){
            return '对不起, 最小值取值不正确!';
        }

        if(!isset($input['max']) || !is_numeric($input['max']) || $input['max']<$input['min']){
            return '对不起, 最大值取值不正确!';
        }

        if(!isset($input['name']) || empty($input['name'])){
            return '对不起, 渠道名称取值不正确!';
        }

        if(isset($input['remark']) && !empty($input['remark'])){
            $this->remark          = $input['remark'];
        }

        $this->factory_id          = $input['factory_id'];
        $this->name                = isset($input['name'])?$input['name']:'';
        $this->channel_code        = $input['channel_code'];
        $this->min                 = $input['min'];
        $this->max                 = $input['max'];

        if(is_null($input['enum']) || $input['enum']=='undefined'){
            $this->enum = '';
        } else {
            $this->enum = $input['enum'];
        }

        $this->type                = $input['type'];
        $this->is_smallamountpay   = $input['is_smallamountpay'];
        $this->has_realname        = $input['has_realname'];
        $this->save();
        
        return true;
    }
}
