<?php
namespace App\Models\Def;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;

class Banks extends Model
{
    public $table = 'def_bank';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'bank_name',
        'bank_code',
        'bank_background_url',
        'currency'
    ];

    protected $casts = [
        'id' => 'integer',
        'bank_name' => 'string',
        'bank_code' => 'string'
    ];

    public $rules = [
        'bank_name'             => 'required|string|min:4|max:16',
        'bank_code'             => 'required|string|min:3|max:6',
    ];

    public $messages = [
        'bank_name.required'                   => '银行名称必须填写',
        'bank_name.min'                        => '银行名称长席必须大于3个字符',
        'bank_name.max'                        => '银行名称长度必须小于17个字符',
        'bank_code.required'                   => '银行编码必须填写',
        'bank_code.min'                        => '银行编码长度必须大于2个字符',
        'bank_code.max'                        => '标识长度必须小于7个字符',
    ];

    static function getList()
    {
        $input          = request()->all();
        $query          = self::orderBy('id','desc');
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['currency']) && !empty($input['currency'])){
            $query->where('currency',$input['currency']);
        }
        
        $total          = $query->count();
        $item           = $query->skip($offset)->take($pageSize)->get();

        return ['item' => $item, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function bankAdd()
    {
        $input     = request()->all();
        $validator = Validator::make($input, $this->rules, $this->messages);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        if(!isset($input['currency']) || empty($input['currency']) || !is_numeric($input['currency'])){
            return '对不起，币种不能为空';
        }

        $exist = self::where('bank_code',$input['bank_code'])->first();

        if($this->id) {
            if($exist && $this->id != $exist->id) {
                return '对不起，此银行已存在';
            }
        } else {
            if($exist) {
                return '对不起，此银行已存在';
            }
        }

        $this->bank_background_url = isset($input['bank_background_url'])?$input['bank_background_url']:'';
        $this->bank_name           = $input['bank_name'];
        $this->currency            = $input['currency'];
        $this->bank_code           = $input['bank_code'];
        $this->save();

        return true;
    }
}
