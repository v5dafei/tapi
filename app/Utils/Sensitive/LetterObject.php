<?php

namespace App\Utils\Sensitive;

class LetterObject
{
    public $value;
    public $frequency;

    public function __construct ( $value ) {
        $this->value     = $value;
        $this->frequency = 1;
    }
}