<?php namespace App\Lib;

use App\Utils\File\FileHelper;
use App\Utils\Hash\HashHelper;
use Illuminate\Support\Facades\Validator;
use Aws\S3\S3Client;

class S3{

    //$arr的值 是array('filename',carrier_id,directory)
    static function uploadImage($file, $arr)
    {
        # 获取base64图片信息
        $base64ImgInfo = FileHelper::base64ImgInfo($file);

        if ( !$base64ImgInfo['ok'] ) {
            $_data['file'] = $file;

            $validator     = Validator::make($_data, [ 'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp' ]);
            
            if ( $validator->fails() ) {
                return $validator->errors()->first();
            }
        }

        $depositPath = storage_path().'/app/public/upload/';

        if (!is_writable($depositPath)) {
            mkdir($depositPath, 0777, true);
            chmod($depositPath, 0777);
        }

        # 上传base64图片
        if ( $base64ImgInfo['ok'] ) {
            // 上传文件的后缀.
            $ext     = $base64ImgInfo['mime'];
            $newName = isset($arr['filename']) ? $arr['filename'] : HashHelper::orderId(uniqid());
            $newName .= '.' . $ext;

            $res = FileHelper::uploadBase64Img($file, $depositPath, $newName);
            if(!$res['ok']) return $res['msg'];

        } else {
            // 上传文件的后缀.
            $entension = $file->getClientOriginalExtension();
            $newName   = isset($arr['filename']) ? $arr['filename'] : md5(date('Y-m-d H:i:s').mt_rand(10000,99999) . $entension);
            $newName   .= '.' . $entension;

            $file->move($depositPath, $newName);
        }


        $localpath = $depositPath.$newName;

        if(isset($arr['carrier_id'])){
            $filename  = strtolower($arr['carrier_id']).'/'.$arr['directory'].'/'.$newName;
        } else {
            $filename  = '0/'.$arr['directory'].'/'.$newName;
        }

        //开始上传云端
        $bucket = config("main")['s3system_bucket'];
        $flag   = self::fileUpdate($bucket, $filename ,$localpath);

        unlink($localpath);
        if($flag !== true) {
            return $flag;
        }
        return  ['name'=>$newName,'path'=>$filename];
    }

    static function uploadFile($file, $arr)
    {
        # 获取base64图片信息

        $_data['file'] = $file;

        if(isset($arr['directory']) && $arr['directory']=='appupdate'){
            $validator     = Validator::make($_data, [ 'file' => 'required|mimetypes:application/widget,text/plain,application/zip' ]);
        } else {
            $validator     = Validator::make($_data, [ 'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp' ]);
        }
            
        if ( $validator->fails() ) {
            return $validator->errors()->first();
        }

        $depositPath = storage_path().'/app/public/upload/';

        if (!is_writable($depositPath)) {
            mkdir($depositPath, 0777, true);
            chmod($depositPath, 0777);
        }

            // 上传文件的后缀.
        $entension = $file->getClientOriginalExtension();
        $newName   = isset($arr['filename']) ? $arr['filename'] : md5(date('Y-m-d H:i:s').mt_rand(10000,99999) . $entension);
        $newName   .= '.' . $entension;

        $file->move($depositPath, $newName);
        $localpath = $depositPath.$newName;

        if(isset($arr['carrier_id'])){
            $filename  = strtolower($arr['carrier_id']).'/'.$arr['directory'].'/'.$newName;
        } else {
            $filename  = '0/'.$arr['directory'].'/'.$newName;
        }

        //开始上传云端
        $bucket = config("main")['s3system_bucket'];
        $flag   = self::fileUpdate($bucket, $filename ,$localpath);

        unlink($localpath);
        if($flag !== true) {
            return $flag;
        }
        return  ['name'=>$newName,'path'=>$filename];
    }

    static function createBucket($bucket)
    {
        $client = new S3Client([
            'version'     => 'latest',
            'region'      => config("main")['region'], #要改为美国西部，不然会报错
            'credentials' => [
                'key'     => config("main")['ossaccessKeyId'], #访问秘钥
                'secret'  => config("main")['accessKeySecret'] #私有访问秘钥
            ]
        ]);
        try {
            $result = $client->createBucket([
                'Bucket' => $bucket, // REQUIRED
                'ACL'    => 'public-read',
            ]);
        } catch (Aws\S3\Exception\S3Exception $e) {
            // output error message if fails
            $flag = $e->getMessage();
        }
        return $flag;
    }

    static function fileUpdate($bucket ,$filename ,$filepath)
    {
        $flag   = true;
        $init   = [
            'version'     => 'latest',
            'region'      => config("main")['region'], #改为美国西部
            'credentials' => [
                'key'         => config("main")['ossaccessKeyId'], #访问秘钥
                'secret'      => config("main")['accessKeySecret'] #私有访问秘钥
            ]
        ];

        $s3               = new S3Client($init);
        try {
            $result = $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $filename,
                'Body'   => fopen($filepath, 'r'),
                'ACL'    => 'public-read',
            ]);
        } catch (Aws\S3\Exception\S3Exception $e) {
            $flag =$e->getMessage();
        }
        
        return $flag;
    }
}
