<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Cache\CarrierCache;

class CarrierPop extends Model
{
    public $table = 'inf_carrier_pop';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [];

    protected $casts = [];

    public    $rules = [
        'type'        => 'required|in:1,2',
        'status'      => 'required|in:1,0',
        'img_url'     => 'required',
        'title'       => 'required',
        'sort'       => 'required',
        'language'    => 'required|in:zh-cn,en,vi,id,hi,th',
        'prefix'      => 'required',
    ];

    public $messages = [
        'type.required'                   => '终端必须填写',
        'type.in'                         => '终端取值不正确',
        'status.required'                 => '状态必须填写',
        'status.in'                       => '状态取值不正确',
        'img_url.required'                => '图片地址必须填写',
        'title.required'                  => '标题必须填写',
        'language.required'               => '语言必须填写',
        'language.in'                     => '语言取值不正确',
        'sort.required'                   => '排序必须填写',
        'prefix.required'                 => '站点必须填写',
    ];

    public function popSave($carrierUser,$carrier)
    {
        $input          = request()->all();
        $validator      = Validator::make($input, $this->rules, $this->messages);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $this->carrier_id = $carrier->id;
        $this->prefix     = $input['prefix'];
        $this->title      = $input['title'];
        $this->type       = $input['type'];
        $this->status     = $input['status'];
        $this->sort       = $input['sort'];
        $this->img_url    = $input['img_url'];
        $this->url        = isset($input['url']) ? $input['url']:'';
        $this->language   = $input['language'];
        $this->admin_id   = $carrierUser->id;
        $this->save();

        return true;
    }
}
