<?php

namespace App\Utils\Sensitive;

class LetterTable
{
    private $letterTable = [];

    private static $instance = null;

    private function __construct () {

    }

    public static function instance () {
        if ( is_null(self::$instance) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __clone () {
        return self::$instance;
    }

    public function set ( $letter ) {
        if ( !$this->isExists($letter) ) {
            $letterObject               = new LetterObject($letter);
            $this->letterTable[$letter] = $letterObject;
        } else {
            $letterObject            = $this->get($letter);
            $letterObject->frequency = $letterObject->frequency + 1;
        }
    }

    public function get ( $letter ) {
        return $this->isExists($letter) ? $this->letterTable[$letter] : null;
    }

    public function isExists ( $letter ) {
        return isset($this->letterTable[$letter]);
    }
}