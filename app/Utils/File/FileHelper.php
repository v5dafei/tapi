<?php
/**
 * 文件图片以及资源类型处理
 */

namespace App\Utils\File;


use App\Exceptions\ErrMsg;
use App\Utils\Helper;
use App\Utils\Date\DateHelper;

class FileHelper
{


    /**
     * 基础的检查图片中是否包含木马
     * @param string $path 文件路劲
     * @return bool true是木马，false不是
     */
    public static function checkUploadPicVirus ( $path, $isBase64 = false ) {
        if ( empty($path) ) {
            return false;
        }

        if ( !$isBase64 ) {
            $resource  = fopen($path, 'rb');
            $file_size = filesize($path);
            //图片前512个字节为图片类型，不能一起转换
            if ( $file_size > 512 ) { // 若文件大于521B文件取头和尾
                $hexCode = bin2hex(fread($resource, 512));
                fseek($resource, $file_size - 512);
                $hexCode .= bin2hex(fread($resource, 512));
            } else { // 取全部
                $hexCode = bin2hex(fread($resource, $file_size));
            }
            fclose($resource);
        } else {
            $hexCode = $path;
        }


        /**
         * 对应的 hex值
         * <?           => 3c3f
         * <?php        => 3c3f706870
         * <%           => 3c25
         * ?>           => 3f3e
         * %>           => 253e
         * <script      =>3c736372697074
         * <script>     => 3c7363726970743e
         * </script>    => 3c2f7363726970743e
         * 网上是这样的匹配规则：不太懂,但是可以检查出来
         * preg_match("/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is", $hexCode)
         * */
        if ( preg_match("/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is", $hexCode) ) {
            return true;
        }
        return false;
    }

    /**
     * @title  二维码生成
     * @param string $data
     * @author benjamin
     */
    public static function genQrCode ( $data, $isShow = true) {
        $filePath = FRAMEWORK_PATH . "Utils/File/phpqrcode.php";
        include_once $filePath;
        $errorCorrectionLevel = "6";
        $matrixPointSize      = "6";

        \QRcode::png($data, false, $errorCorrectionLevel, $matrixPointSize, 1, $isShow);
    }

    /**
     * @title  二维码生成 - 并转换base64
     * @param string $data
     * @author benjamin
     */
    public static function genQrcode64($data){
        $filePath = FRAMEWORK_PATH . "Utils/File/phpqrcode.php";
        include_once $filePath;
        $errorCorrectionLevel = "6";
        $matrixPointSize      = "6";


//        $QRcode = new \QRcode();
        ob_start(); // 在服务器打开一个缓冲区来保存所有的输出
//        $QRcode->png($frame,false,$level,$size,$margin);
        \QRcode::png2($data, false, $errorCorrectionLevel, $matrixPointSize, 1, false);
        $imageString = ob_get_contents();
        ob_end_clean(); //清除缓冲区的内容，并将缓冲区关闭，但不会输出内容

        return self::base64EncodeImage($imageString);
    }

    /**
     * @title  获取图片资源数据
     * @param resource $resource
     * @return false|string
     * @author benjamin
     */
    public static function imageToBuffer ( $resource ) {
        ob_start();
        imagepng($resource);
        $output_buf = ob_get_contents();
        ob_end_clean();
        return $output_buf;
    }

    /**
     * @title  将图片资源转base64
     * @link   https://www.jianshu.com/p/ea49397fcd13  Data URI scheme 规范
     * @param        $imageData
     * @param string $mime
     * @return string
     * @author benjamin
     */
    public static function base64EncodeImage ( $imageData, $mime = 'image/png' ) {
        # $imageInfo   = getimagesize($strTmpName);
        # $imageData   = fread(fopen($strTmpName, 'r'), filesize($strTmpName));

        # data:image/png;base64
        $base64Image = 'data:' . $mime . ';base64,' . chunk_split(base64_encode($imageData));
        return $base64Image;
    }


    public static function base64ImgInfo($base64Img) {
        $res = preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64Img, $result);

        $mimeType = null;
        if(!empty($result[2])) {
            $mimeType = $result[2];
            if(!in_array($mimeType,array('pjpeg','jpeg','jpg','gif','bmp','png','svg'))){
                $res = false;
            }
        }

        return ['ok' => (bool)$res, 'mime' => $mimeType];
    }

    public static function uploadBase64Img($base64Img, $dir, $fileName) {
        if(!preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64Img, $result)) {
            return ['ok' => false, 'msg' => '上传内容非base64数据'];
        }

        $mimeType = $result[2];
        if(!in_array($mimeType,array('pjpeg','jpeg','jpg','gif','bmp','png','svg'))){
            return ['ok' => false, 'msg' => '上传内容格式不允许'];
        }

        $newFile = $dir . $fileName;
        if(file_put_contents($newFile, base64_decode(str_replace($result[1], '', $base64Img)))){
            return ['ok' => true, 'msg' => '图片上传成功' , 'file' => $newFile];
        }else{
            return ['ok' => false, 'msg' => '图片上传失败'];
        }
    }

    /**
     * 获取CSV上传文件内容
     * @param $fileParams
     * @return array
     * @throws \Exception
     */
    public static function getUploadCsvData ($fileParams) {

        $fileArr = $fileParams['files'];
        $file = $fileArr[0];
        $tmpFile	= $file->path();
//        var_dump($tmpFile);die;
        $fileSize	= filesize($tmpFile);
        $msg = [];
        if ($fileSize <= 0){
            @unlink($tmpFile);
            throw new \Exception('文件尺寸不正确');
//            $msg[] = '文件尺寸不正确';
        }

        if (count($msg)>0){
            echo '<style>*{font-size:12px;}</style>';
            echo implode('；',$msg).'。 &nbsp; &nbsp; &nbsp; <a href="/fhptbet.php/member/importUploadForm">点击返回</a>';
        }else{
            $content = nl2br(file_get_contents($tmpFile));
            $rows = explode('<br />', $content);
            $insert = [];
//            var_dump($rows);die;
            if (count($rows) > 0){
                $i = 0;
                foreach ($rows as $k => $row){
                    $i = $i + 1;
                    if ($i>1){
                        $rowAry = explode(',', $row);
                        if(!isset($rowAry[1])) {
                            continue;
                        }

//                        var_dump($rowAry, $rowAry[8], htmlspecialchars(Helper::paramsFilter(Helper::strFilter(trim(iconv("GBK","UTF-8",$rowAry[8]))))));die;

                        $sqlAry = array(
                            'username'			=> htmlspecialchars( Helper::paramsFilter(ltrim(trim(str_ireplace(["'","\\"],"",$rowAry[0])),"'"))),
                            'password'			=> strtolower(htmlspecialchars(Helper::paramsFilter(trim($rowAry[1])))),
                            'salt'			=> strtolower(htmlspecialchars(Helper::paramsFilter(trim($rowAry[2])))),
                            'coinPassword'		=> strtolower(htmlspecialchars(Helper::paramsFilter(trim($rowAry[3])))),
                            'cp_salt'			=> strtolower(htmlspecialchars(Helper::paramsFilter(trim($rowAry[4])))),
                            'coin'				=> htmlspecialchars(Helper::paramsFilter(trim($rowAry[5]))),
                            'type'				=> preg_match("/代理/", htmlspecialchars(Helper::paramsFilter(trim(iconv("GBK","UTF-8",$rowAry[6]))))) ? '1' : '0',
                            'parentUsername'	=> htmlspecialchars(Helper::paramsFilter(trim($rowAry[7]))),
//                            'name'				=> htmlspecialchars(Helper::paramsFilter(Helper::strFilter(trim(iconv("GBK","UTF-8",$rowAry[8]))))),
                            'name'				=> htmlspecialchars(trim(iconv("GBK","UTF-8",$rowAry[8]))),
                            'account'			=> (int)htmlspecialchars(Helper::paramsFilter(ltrim(trim($rowAry[9]),"'"))),
                            'bankName'			=> trim(htmlspecialchars(Helper::paramsFilter(trim(iconv("GBK","UTF-8",$rowAry[10]))))),
                            'countname'			=> htmlspecialchars(Helper::paramsFilter(trim(iconv("GBK","UTF-8",$rowAry[11])))),
                            'qq'				=> htmlspecialchars(Helper::paramsFilter(trim($rowAry[12]))),
                            'phone'				=> htmlspecialchars(Helper::paramsFilter(trim($rowAry[13]))),
                            'email'				=> htmlspecialchars(Helper::paramsFilter(trim($rowAry[14]))),
                            'wx'				=> htmlspecialchars(Helper::paramsFilter(trim($rowAry[15]))),
                            'regTime'			=> date('Y-m-d H:i:s',strtotime(htmlspecialchars(Helper::paramsFilter(trim($rowAry[16]))))),
                            'regIP'				=> htmlspecialchars(Helper::paramsFilter(trim($rowAry[17]))),
                            'from_domain'		=> htmlspecialchars(Helper::paramsFilter(trim($rowAry[18]))),
                            'level_id'			=> htmlspecialchars(Helper::paramsFilter(trim($rowAry[19]))),
                            'recharge_count'	=> htmlspecialchars(Helper::paramsFilter(trim(abs($rowAry[20])))),
                            'recharge_amount'	=> htmlspecialchars(Helper::paramsFilter(trim(abs($rowAry[21])))),
                            'cash_count'		=> htmlspecialchars(Helper::paramsFilter(trim(abs($rowAry[22])))),
                            'cash_amount'		=> htmlspecialchars(Helper::paramsFilter(trim(abs($rowAry[23])))),
                            'vip_id'	=> htmlspecialchars(Helper::paramsFilter(trim($rowAry[24]))),
                            'integral'	        => htmlspecialchars(Helper::paramsFilter(trim($rowAry[25]))),
                            'comment'	        => htmlspecialchars(Helper::paramsFilter(trim(iconv("GBK","UTF-8",$rowAry[26])))),
                            'success'			=> '0',
                        );

                        if ($sqlAry['regTime'] && DateHelper::checkDateIsValid($sqlAry['regTime'])){
                            $sqlAry['regTime'] = strtotime($sqlAry['regTime']);
                        }else{
                            $sqlAry['regTime'] = time();
                        }
                        if ($sqlAry['regIP'] && ip2long($sqlAry['regIP'])){
                            $sqlAry['regIP'] = ip2long($sqlAry['regIP']);
                        }else{
                            $sqlAry['regIP'] = ip2long('127.0.0.1');
                        }
                        //为空的清除掉
                        foreach ($sqlAry as $key=>$var){
                            if (!$var){
                                unset($sqlAry[$key]);
                            }
                        }
                        if (!empty($sqlAry['username'])){
                            $insert[] = $sqlAry;
//                            $sqlAry2 = $sqlAry;
//                            unset($sqlAry2['success']);
//                            $sql = "INSERT INTO {$this->prename}member_import ".sql_implode($sqlAry, 'insert')." ON DUPLICATE KEY UPDATE ".sql_implode($sqlAry2, 'update');
//                            $this->insert($sql);
                        }
                    }
                }
            }

            return $insert;
        }
    }


    public static function getUploadCsvData2 ($fileParams) {

        $fileArr = $fileParams['files'];
        $file = $fileArr[0];
        $tmpFile	= $file->path();
//        var_dump($tmpFile);die;
        $fileSize	= filesize($tmpFile);
        $msg = [];
        if ($fileSize <= 0){
            @unlink($tmpFile);
            throw new \Exception('文件尺寸不正确');
//            $msg[] = '文件尺寸不正确';
        }

        if (count($msg)>0){
            echo '<style>*{font-size:12px;}</style>';
            echo implode('；',$msg).'。 &nbsp; &nbsp; &nbsp; <a href="/fhptbet.php/member/importUploadForm">点击返回</a>';
        }else{
            $content = nl2br(file_get_contents($tmpFile));
            $rows = explode('<br />', $content);
            $insert = [];
//            var_dump($rows);die;
            if (count($rows) > 0){
                $i = 0;
                foreach ($rows as $k => $row){
                    $i = $i + 1;
                    if ($i>1){
                        $rowAry = explode(',', $row);
                        if(!isset($rowAry[1])) {
                            continue;
                        }



                        $usr = htmlspecialchars( Helper::paramsFilter(ltrim(trim(str_ireplace(["'","\\"],"",$rowAry[0])),"'")));
                        $level = (int)htmlspecialchars($rowAry[1]);

//                        if($k > 5) {
//                            var_dump($rowAry, $usr, $level);die;
//                        }


                        if (!empty($usr)){
                            $insert[$usr] = $level;
//
                        }
                    }
                }
            }

            return $insert;
        }
    }

}