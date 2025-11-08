<?php
namespace App\Models\Def;

use Illuminate\Database\Eloquent\Model;

class PayFactory extends Model
{

    public $table = 'def_pay_factory_list';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'factory_name',
        'code'
    ];

    protected $casts = [
        'factory_name' => 'string',
        'code'         => 'string'
    ];

    public static $rules = [];

    public function saveItem()
    {
        $input      = request()->all();
        $payFactory = self::where('factory_name',$input['factory_name'])->first();

        if($this->id) {
            if($payFactory && $payFactory->id != $this->id) {
                 return '对不起, 此渠道已经存在!';
            }
        } else {
            if($payFactory) {
                return '对不起, 此渠道已经存在!';
            }
        }

        if(!isset($input['factory_name']) || empty($input['factory_name'])){
            return '对不起, 支付厂商名称不能为空!';
        }

        if(!isset($input['code']) || empty($input['code'])){
            return '对不起, 支付编码不能为空!';
        }

        if(!isset($input['currency']) || empty($input['currency'])){
            return '对不起, 币种不能为空!';
        }

        if(!isset($input['type']) || !in_array($input['type'], [1,2])){
            return '对不起, 类型取值不正确!';
        }

        $payFactory = self::where('code',$input['code'])->first();
        if($this->id) {
            if($payFactory && $payFactory->id != $this->id) {
                 return '对不起, 此编码已经存在!';
            }
        } else {
            if($payFactory) {
                return '对不起, 此编码已经存在!';
            }
            $this->status          = 0;
        }

        $this->factory_name        = $input['factory_name'];
        $this->type                = $input['type'];
        $this->code                = $input['code'];
        $this->currency            = $input['currency'];

        if(isset($input['ip']) && $input['ip']!='undefined'){
            $this->ip              = $input['ip'];
        } else {
            $this->ip              = '';
        }

        $this->save();

        return true;
    }

    static function getList() 
    {
        $input          = request()->all();
        $query          = self::orderBy('id','asc');
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $total          = $query->count();
        $data           = $query->skip($offset)->take($pageSize)->get();

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
