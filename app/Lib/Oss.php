<?php namespace App\Lib;

use Illuminate\Support\Facades\Validator;
use OSS\Core\OssException;
use OSS\OssClient;

class Oss{

    //$arr的值 是array('filename',carrier_id,directory)
    static function uploadImage($file, $arr)
    {
        $_data['file'] = $file;
        $validator     = Validator::make($_data, ['file' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp']);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $depositPath = storage_path().'/app/public/upload/';

        if (!is_writable($depositPath)) {
            mkdir($depositPath, 0777, true);
            chmod($depositPath, 0777);
        }

        // 上传文件的后缀.
        $entension  = $file->getClientOriginalExtension();
        $newName    = isset($arr['filename']) ?$arr['filename'] : md5(date('Y-m-d H:i:s') . $entension);
        $newName   .= '.' . $entension;

        $file->move($depositPath, $newName);
        $localpath = $depositPath.$newName;

        $filename  = strtolower($arr['carrier_id']).'/'.$arr['directory'].'/'.$newName;

        //开始上传云端
        $bucket = config("main")['osssystem_bucket'];

        self::fileDelete($bucket, $filename);

        $flag = self::fileUpdate($bucket, $filename ,$localpath);

        if($flag !== true) {
            unlink($localpath);

            return $flag;
        }
        return  ['name'=>$newName,'path'=>$filename];
    }

    static function createBucket($bucket)
    {
        $accessKeyId     = config("main")['ossaccessKeyId'];
        $accessKeySecret = config("main")['accessKeySecret'];
        $endpoint        = config("main")['endpoint'];
        $flag            = true;

        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

            $ossClient->createBucket($bucket);
        } catch (OssException $e) {
            $flag=$e->getMessage();
        }
        return $flag;
    }

    static function fileUpdate($bucket ,$filename ,$filepath)
    {
        $accessKeyId     = config("main")['ossaccessKeyId'];
        $accessKeySecret = config("main")['accessKeySecret'];
        $endpoint        = config("main")['endpoint'];
        $flag            = true;

        try{
           $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

           $ossClient->uploadFile($bucket, $filename, $filepath);
        } catch(OssException $e) {
            $flag=$e->getMessage();
        }
        
        return $flag;
    }

    static function fileDelete($bucket ,$filename)
    {
        $accessKeyId     = config("main")['ossaccessKeyId'];
        $accessKeySecret = config("main")['accessKeySecret'];
        $endpoint        = config("main")['endpoint'];
        $flag            = true;

        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

            $ossClient->deleteObject($bucket, $filename);
        } catch(OssException $e) {
            $flag=$e->getMessage();
        }
        return $flag;
    }

    static function fileExists($bucket ,$filename)
    {
        $accessKeyId     = config("main")['ossaccessKeyId'];
        $accessKeySecret = config("main")['accessKeySecret'];
        $endpoint        = config("main")['endpoint'];
        $flag            = false;
        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

            $flag = $ossClient->doesObjectExist($bucket, $filename);
        } catch(OssException $e) {
            $flag=$e->getMessage();
        }
        return $flag;
    }
}
