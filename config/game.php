<?php
return [
    'pub' => [
        'AddressIp'          => \Yaconf::get(YACONF_PRO_ENV.'.AddressIp', '185.189.160.48') ,
        'gameurl'            => \Yaconf::get(YACONF_PRO_ENV.'.gameurl', 'https://game.v5api.xyz') ,
        'expires_in'         => 21600,
        'lotterylobby'       => [2501,2659,2793],
        'mobilelotterylobby' => [2501,2659,2793],
        'electronic'         => ['mg','jdb','at','cq9','pt','sw','pg','lgd','ps','jili','pp','bt','fc','fg','i8','mw','bng','db','cg','bbin','ag','habanero'],
        'nologout'           => ['ag','tcg','at','rmg','mg','ky','og','bti','ds88','evo','fb','fl','ksesports','kp','pmty','pmzr','pmcp','sw','v8'], 
        'nocheckout'         => ['ag','pt','cq9','ab','mg','ae'],
    ]
];