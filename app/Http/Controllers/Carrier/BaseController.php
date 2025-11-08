<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    protected $carrierUser = null;
    protected $authToken   = null;

    public function __construct () 
    {
        $tokenHeader = request()->header('Authorization');
        if(!empty($tokenHeader) && strpos($tokenHeader,'bearer')!==false) {
            $this->authToken = explode(' ', $tokenHeader)[1];
        }

        //$this->carrierUser = auth("carrier")->user();

    }
}
