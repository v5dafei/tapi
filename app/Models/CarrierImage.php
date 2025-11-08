<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Cache\CarrierCache;
use App\Models\CarrierPreFixDomain;

class CarrierImage extends Model
{
    public $table = 'inf_carrier_img';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
        'image_category_id',
        'carrier_id',
        'image_path',
        'url',
        'remark',
        'admin_id',
        'sort'
    ];

    protected $casts = [
        'id'                => 'integer',
        'image_category_id' => 'integer',
        'carrier_id'        => 'integer',
        'image_path'        => 'string',
        'url'               => 'string',
        'remark'            => 'string',
        'admin_id'          => 'integer',
        'sort'              => 'integer',
    ];

    public static $rules = [

    ];

    public function imgList($carrier)
    {
        $input          = request()->all();
        $query          = self::select('inf_image_category.category_name','inf_carrier_img.*')->leftJoin('inf_image_category','inf_image_category.id','=','inf_carrier_img.image_category_id')->where('inf_carrier_img.carrier_id',$carrier->id)->orderBy('inf_carrier_img.id','asc');
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['image_category_id']) && trim($input['image_category_id']) != '') {
            $query->where('inf_carrier_img.image_category_id',$input['image_category_id']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('inf_carrier_img.prefix',$input['prefix']);
        }

        $languages     = CarrierCache::getCarrierConfigure($carrier->id,'supportMemberLangMap');
        $languageArrs  = explode(',',$languages);

        if(isset($input['language']) && in_array($input['language'], $languageArrs)){
            $query->where('inf_carrier_img.language',$input['language']);
        } else {
            $query->whereIn('inf_carrier_img.language',$languageArrs);
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }

        foreach ($data as $key => &$value) {
            $value->multiple_name = $carrierPreFixDomainArr[$value->prefix];
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
