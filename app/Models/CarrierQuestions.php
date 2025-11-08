<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class CarrierQuestions extends Model
{
    public $table    = 'inf_carrier_questions';

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

    static function questionLists($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;


        $query  = self::where('carrier_id',$carrier->id)->orderBy('id','desc');

        if(isset($input['type']) && !empty($input['type'])) {
            $query->where('type',$input['type']);
        }

        if(isset($input['title'])) {
            $query->where('title','like','%'.$input['title'].'%');
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function questionSave($carrierUser,$carrier)
    {
        $input          = request()->all();

        $this->carrier_id = $carrier->id;
        $this->title      = $input['title'];
        $this->type       = $input['type'];
        $this->content    = $input['content'];
        $this->sort       = $input['sort'];
        $this->admin_id   = $carrierUser->id;
        $this->save();

        return true;
    }
}
