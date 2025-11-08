<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Carrier;

class BaseController extends Controller
{
    protected $carrierUser = null;
    protected $authToken   = null;

    public function __construct () 
    {
        \Log::info('进入基本层');
        $tokenHeader = request()->header('Authorization');
        if(!empty($tokenHeader) && strpos($tokenHeader,'bearer')!==false) {
            $this->authToken = explode(' ', $tokenHeader)[1];
        }

        //$this->carrierUser = auth("carrier")->user();

    }
}
