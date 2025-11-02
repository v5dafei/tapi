<?php
namespace App\Models\Def;

use Illuminate\Database\Eloquent\Model;

class SmsPassage extends Model
{

    public $table = 'def_sms_passage_list';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [];

    protected $casts = [];

    public static $rules = [];

    public function saveItem()
    {
        $input      = request()->all();

        if(!isset($input['filename']) || empty($input['filename'])){
            return '对不起，文件名不能为空值';
        }

        $existSmsPassage = self::where('filename',$input['filename'])->first();

        if($this->id){
            if($existSmsPassage && $existSmsPassage->id != $this->id){
                return '对不起，此文件名已经存在';
            }

        } else {
            if($existSmsPassage ){
                return '对不起，此文件名已经存在';
            }
        }

        $this->name                = $input['name'];
        $this->appkey              = $input['appkey'];
        $this->appcode             = $input['appcode'];
        $this->appsecret           = $input['appsecret'];
        $this->status              = $input['status'];
        $this->filename            = $input['filename'];
        $this->sendurl             = $input['sendurl'];

        $this->save();

        return true;
    }
}
