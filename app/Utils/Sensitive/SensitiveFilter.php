<?php

namespace App\Utils\Sensitive;


class SensitiveFilter
{
    public $tree = null;
	/*
    #创建静态对象变量
    static private $instance = null;

    //构造函数私有化，防止外部调用
    private function __construct () {
    }

    //克隆函数私有化，防止外部克隆对象
    private function __clone () {
    }

    //实例化对象变量方法，供外部调用
    public static function getInstance () {
        if ( empty(self::$instance) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
	*/

    public function addWordToTree ( $word ) {
        $len = mb_strlen($word);
        if ( is_null($this->tree) ) {
            $tree        = new TreeNode();
            $tree->isEnd = 0;
        } else {
            $tree = $this->tree;
        }
        $tmp = $tree;
        for ( $i = 0; $i < $len; $i++ ) {
            $nowLetter = mb_substr($word, $i, 1);

            $letterTable = LetterTable::instance();
            $letterTable->set($nowLetter);

            $nowTree = $tree->get($nowLetter);

            if ( !is_null($nowTree) ) {
                $tree = $nowTree;
            } else {
                $newTree        = new TreeNode();
                $newTree->isEnd = 0;
                $tree->set($nowLetter, $newTree);
                $tree = $newTree;
            }

            if ( $i == ($len - 1) ) {
                $tree->isEnd = 1;
            }
        }
        $this->tree = $tmp;
    }

    public function search ( $string ) {
        $len         = mb_strlen($string);
        $result      = [];
        $stack       = [];
        $letterTable = LetterTable::instance();

        $tmpTree = $this->tree;

        for ( $i = 0; $i <= $len; $i++ ) {
            $nowLetterA = mb_substr($string, $i, 1, 'utf-8');
            if ( $letterTable->isExists($nowLetterA) ) {
                if ( !is_null($tmpTree->get($nowLetterA)) ) {
                    array_push($stack, $i);
                }
            } else {
                $end = $i;
                while ( count($stack) > 0 ) {
                    $curIndex = array_pop($stack);
                    $start    = $curIndex;
                    $tmpWord  = '';
                    $tree     = $tmpTree;
                    for ( $j = $curIndex; $j <= $end; $j++ ) {
                        $nowLetter = mb_substr($string, $j, 1, 'utf-8');
                        $nowTree   = $tree->get($nowLetter);
                        if ( !is_null($nowTree) ) {
                            $tmpWord .= $nowLetter;
                            if ( $nowTree->isEnd ) {
                                array_push($result, [
                                    'word'        => $tmpWord,
                                    'startOffset' => $start,
                                    'endOffset'   => $j + 1,
                                ]);
                                if ( $nowTree->hasNext() ) {
                                    $tree = $nowTree;
                                } else {
                                    $start   = $j;
                                    $tmpWord = '';
                                    $tree    = $tmpTree;
                                }
                            } else {
                                $tree = $nowTree;
                            }
                        } else {
                            $start   = $j;
                            $tmpWord = '';
                            $tree    = $tmpTree;
                        }
                    }
                }
            }
        }
        return $result;
    }
}