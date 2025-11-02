<?php
/**
 * CSV文件常用操作封装
 */

namespace App\Utils\File;


class CsvHelper
{

    /**
     * 字符转换（utf-8 => GBK）
     */
    public static function utfToGbk ( $data ) {
//        return iconv('utf-8', 'GBK', $data);
        return iconv('utf-8', 'GBK//TRANSLIT//IGNORE', $data);
    }

    /**
     * 字符转换（utf-8 => GBK）
     */
    public static function gbkToUtf ( $data ) {
//        return iconv('utf-8', 'GBK', $data);
        return iconv('GBK', 'utf-8//TRANSLIT//IGNORE', $data);
    }

    /**
     * 格式化每行数据
     * @param mixed $data
     * @param false $newLine
     * @return string
     */
    private static function formatCurRow ( $data, $newLine = true, $toGbk = true ) {
        $format = '';
        if ( is_string($data) ) {
            $format = ($toGbk ? self::utfToGbk($data) : $data) . ($newLine ? "\n" : '');
        } elseif ( is_array($data) ) {
            $fieldStr = implode(',', $data);
            $format   = ($toGbk ? self::utfToGbk($fieldStr) : $fieldStr) . ($newLine ? "\n" : '');
        }
        return $format;
    }

    /**
     * 获取CSV文件内容
     * @param     $fileName
     * @param int $startLine
     * @param int $endLine
     * @return array
     */
    public static function importCsv ( $fileName, $startLine = 0, $endLine = 0 ) {
        $data = [];

        if ( empty($startLine) && empty($endLine) ) {
            $file = new \SplFileObject($fileName);
            $file->setFlags(\SplFileObject::READ_CSV);
            foreach ( $file as $key => $row ) {
                if ( !empty($row) ) {
                    $data[] = $row;
                }
            }
            $data = array_filter($data);
        } else {
            $data = self::getFileLines($fileName, $startLine, $endLine);
        }

        return $data;
    }

    /**
     * 生成CSV文件
     * @param array  $data
     * @param string $fileName
     * @param string $columns
     * @return string
     */
    public static function saveToCsv ( array $data, $fileName = '', $columns = '' ) {
//        # 需要导出的列名
//        $columns = [ '姓名', '分数' ];
//
//        # 需要导出的内容
//        $data = [
//            [ 'name' => '张三', 'score' => '80' ],
//            [ 'name' => '李四', 'score' => '90' ],
//            [ 'name' => '王五', 'score' => '60' ],
//            [ 'name' => '小米', 'score' => '88' ],
//        ];

        // 文件名，这里都要将utf-8编码转为gbk，要不可能出现乱码现象
        $fileName = ($fileName ? $fileName : __METHOD__) . '_' . date('Ymd') . '.csv';

        // 拼接文件信息，这里注意两点
        // 1、字段与字段之间用逗号分隔开
        // 2、行与行之间需要换行符
        $fileData = '';

        if ( !empty($columns) ) {
            $fileData = self::formatCurRow($columns, true, true);
        }

        foreach ( $data as $value ) {
            $fileData .= self::formatCurRow($value, true, false);
        }

        $filePath = API_PATH . '/' . $fileName;

        // 将一个字符串写入文件
        file_put_contents($filePath, $fileData);

        return $filePath;
    }

    /**
     * 导出CSV文件内容
     * @param string $fileName
     * @param string $columns
     * @param array  $data
     */
    public static function exportCsv ( array $data, $fileName = '', $columns = '' ) {

//        # 需要导出的列名
//        $columns = [ '姓名', '分数' ];
//
//        # 需要导出的内容
//        $data = [
//            [ 'name' => '张三', 'score' => '80' ],
//            [ 'name' => '李四', 'score' => '90' ],
//            [ 'name' => '王五', 'score' => '60' ],
//            [ 'name' => '小米', 'score' => '88' ],
//        ];

        // 文件名，这里都要将utf-8编码转为gbk，要不可能出现乱码现象
        $fileName = ($fileName ? $fileName : __METHOD__) . '_' . date('Ymd') . '.csv';
        $fileName = self::utfToGbk($fileName);

        // 拼接文件信息，这里注意两点
        // 1、字段与字段之间用逗号分隔开
        // 2、行与行之间需要换行符
        $fileData = '';

        if ( !empty($columns) ) {
            $fileData = self::formatCurRow($columns, true);
        }

        foreach ( $data as $value ) {
            $fileData .= self::formatCurRow($value, true);
        }

        self::sendCsvHeader($fileName, $fileData);
    }

    /**
     * 分页读取文件
     * @param        $filename
     * @param int    $startLine
     * @param int    $endLine
     * @param string $method
     * @return array|string
     */
    public static function getFileLines ( $filename, $startLine = 1, $endLine = 50, $method = 'rb' ) {
        $content = [];
        $endLine = max($endLine, 50);
        $count   = $endLine - $startLine;
        // 判断php版本（因为要用到SplFileObject，PHP>=5.1.0）
        if ( version_compare(PHP_VERSION, '5.1.0', '>=') ) {
            $fp = new \SplFileObject($filename, $method);
            $fp->setFlags(\SplFileObject::READ_CSV);
            $fp->seek($startLine - 1);// 转到第N行, seek方法参数从0开始计数
            for ( $i = 0; $i <= $count; ++$i ) {
                if ( !empty($fp->current()) ) {
                    $content[] = $fp->current();// current()获取当前行内容
                }
                $fp->next();// 下一行
            }
        } else {//PHP<5.1
            $fp = fopen($filename, $method);
            if ( !$fp ) return 'error:can not read file';
            for ( $i = 1; $i < $startLine; ++$i ) {// 跳过前$startLine行
                fgets($fp);
            }
            for ( $i; $i <= $endLine; ++$i ) {
                $content[] = fgets($fp);// 读取文件行内容
            }
            fclose($fp);
        }
        return array_filter($content); // array_filter过滤：false,null,''
    }

    /**
     * 发送CSV文件下载头
     * @param $fileName
     * @param $fileData
     */
    public static function sendCsvHeader ( $fileName, $fileData ) {
        // 头信息设置
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $fileName);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $fileData;
        exit;
    }
}