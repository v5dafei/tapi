<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' =>  \Yaconf::get(YACONF_PRO_ENV.'.MAILGUN_DOMAIN'),
        'secret' =>  \Yaconf::get(YACONF_PRO_ENV.'.MAILGUN_SECRET'),
        'endpoint' =>  \Yaconf::get(YACONF_PRO_ENV.'.MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' =>  \Yaconf::get(YACONF_PRO_ENV.'.POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' =>  \Yaconf::get(YACONF_PRO_ENV.'.AWS_ACCESS_KEY_ID'),
        'secret' =>  \Yaconf::get(YACONF_PRO_ENV.'.AWS_SECRET_ACCESS_KEY'),
        'region' =>  \Yaconf::get(YACONF_PRO_ENV.'.AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
