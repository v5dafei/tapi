<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class ArbitrageAlipay extends Model
{
    public $table    = 'def_arbitrage_alipay';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
       
    ];

    protected $casts = [
    ];

    public $rules = [
        
    ];

    public $messages = [
        
    ];

    public static function arbitrageAlipayList()
    {
        $input       = request()->all();
        $query       = self::orderBy('id', 'desc');

        $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
        $offset      = ($currentPage - 1) * $pageSize;

        if(isset($input['account']) && !empty($input['account'])){
            $query->where('account',$input['account']);
        }

        if(isset($input['real_name']) && !empty($input['real_name'])){
            $query->where('real_name',$input['real_name']);
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
}
