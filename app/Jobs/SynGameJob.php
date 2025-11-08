<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Def\Game as Games;
use App\Models\Def\MainGamePlat;
use App\Models\Language;
use App\Models\Currency;
use App\Lib\Cache\GameCache;

class SynGameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const SYNCGAME                              = 'syncGame';
    const LOGIN                                 = 'login';

    public function __construct() {
    }

    public function handle()
    {
        $this->syncGame();
    }

    public function syncGame()
    {
         $param = [
            'type' => 'gameplat'
         ];

         $output           = $this->request(config('game')['pub']['gameurl'].'/api/'.self::SYNCGAME,$param);
         $mainGamePlatArrs = [];

         if(isset($output['success'])) {
            $existMainGamePlat = MainGamePlat::orderBy('main_game_plat_id','asc')->first();
            if(!$existMainGamePlat){
                foreach ($output['data'] as $key => $value) {
                    $arr                            = [];
                    $arr['main_game_plat_id']       = $value['main_game_plat_id'];
                    $arr['main_game_plat_code']     = $value['main_game_plat_code'];
                    $arr['status']                  = $value['status'];
                    $arr['sort']                    = $value['sort'];
                    $arr['alias']                   = $value['alias'];
                    $arr['short']                   = $value['short'];
                    $arr['created_at']              = $value['created_at'];
                    $arr['updated_at']              = $value['updated_at'];
                    $mainGamePlatArrs[]             = $arr;
                }

                \DB::table('def_main_game_plats')->insert($mainGamePlatArrs);
            } else{
                $allMainGamePlats    = MainGamePlat::all();
                $allMainGamePlatsArr = [];

                foreach ($allMainGamePlats as $key => $value) {
                    $allMainGamePlatsArr[$value->main_game_plat_id] = $value;
                }

                foreach ($output['data'] as $key => $value) {
                    if(isset($allMainGamePlatsArr[$value['main_game_plat_id']])){
                        $curr = $allMainGamePlatsArr[$value['main_game_plat_id']];
                        if($curr->updated_at != $value['updated_at']){
                            $mainGamePlat                      = MainGamePlat::where('main_game_plat_id',$value['main_game_plat_id'])->first();
                            $mainGamePlat->main_game_plat_code = $value['main_game_plat_code'];
                            $mainGamePlat->sort                = $value['sort'];
                            $mainGamePlat->alias               = $value['alias'];
                            $mainGamePlat->short               = $value['short'];
                            $mainGamePlat->created_at          = $value['created_at'];
                            $mainGamePlat->updated_at          = $value['updated_at'];
                            $mainGamePlat->save();
                        }
                    } else {
                        $arr                            = [];
                        $arr['main_game_plat_id']       = $value['main_game_plat_id'];
                        $arr['main_game_plat_code']     = $value['main_game_plat_code'];
                        $arr['status']                  = $value['status'];
                        $arr['sort']                    = $value['sort'];
                        $arr['short']                   = $value['short'];
                        $arr['alias']                   = $value['alias'];
                        $arr['created_at']              = $value['created_at'];
                        $arr['updated_at']              = $value['updated_at'];
                        $mainGamePlatArrs[]             = $arr;
                    }
                }

                if(count($mainGamePlatArrs)){
                    \DB::table('def_main_game_plats')->insert($mainGamePlatArrs);
                }
            }
         } else {
            \Log::info('同步游戏失败');
         }

        $param['type'] = 'game';
        $output        = $this->request(config('game')['pub']['gameurl'].'/api/'.self::SYNCGAME,$param);

        $gameArr       = [];
        $i             = 0;
        if(isset($output['success'])) {
            $existGames  = Games::orderBy('game_id','asc')->first();
            //首次同步
            if(!$existGames){
                foreach ($output['data'] as $key => $value) {
                    $arr                            = [];
                    $arr['game_id']                 = $value['game_id'];
                    $arr['main_game_plat_id']       = $value['main_game_plat_id'];
                    $arr['game_category']           = $value['game_category'];
                    $arr['main_game_plat_code']     = $value['main_game_plat_code'];
                    $arr['game_name']               = $value['game_name'];
                    $arr['en_game_name']            = $value['en_game_name'];
                    $arr['game_code']               = $value['game_code'];
                    $arr['format']                  = $value['format'];
                    $arr['game_moblie_code']        = $value['game_moblie_code'];
                    $arr['game_icon_square_path']   = $value['game_icon_square_path'];
                    $arr['en_game_icon_square_path']= $value['en_game_icon_square_path'];
                    $arr['zh_status']               = $value['zh_status'];
                    $arr['en_status']               = $value['en_status'];
                    $arr['status']                  = $value['status'];
                    $arr['pageview']                = $value['pageview'];
                    $arr['is_recommend']            = $value['is_recommend'];
                    $arr['is_hot']                  = $value['is_hot'];
                    $arr['is_pool']                 = $value['is_pool'];
                    $arr['sort']                    = $value['sort'];
                    $arr['record_match_code']       = $value['record_match_code'];
                    $arr['created_at']              = $value['created_at'];
                    $arr['updated_at']              = $value['updated_at'];
                    $gameArr[]                      = $arr;

                    if(count($gameArr)==1000){
                        \DB::table('def_games')->insert($gameArr);
                        $gameArr = [];
                    }
                }
                if(count($gameArr)){
                    \DB::table('def_games')->insert($gameArr);
                    $gameArr = [];
                }
            } else {
                //更新
                $allGames    = Games::all();
                $allGamesArr = [];
                foreach ($allGames as $key => $value) {
                    $allGamesArr[$value->game_id] = $value;
                }

                foreach ($output['data'] as $key => $value) {
                    if(isset($allGamesArr[$value['game_id']])){
                        $curr = $allGamesArr[$value['game_id']];
                        if($curr->updated_at != $value['updated_at']){
                            $game                          = Games::where('game_id',$value['game_id'])->first();
                            $game->main_game_plat_id       = $value['main_game_plat_id'];
                            $game->game_category           = $value['game_category'];
                            $game->main_game_plat_code     = $value['main_game_plat_code'];
                            $game->game_name               = $value['game_name'];
                            $game->en_game_name            = $value['en_game_name'];
                            $game->game_code               = $value['game_code'];
                            $game->game_moblie_code        = $value['game_moblie_code'];
                            $game->format                  = $value['format'];
                            $game->game_icon_square_path   = $value['game_icon_square_path'];
                            $game->en_game_icon_square_path= $value['en_game_icon_square_path'];
                            $game->zh_status               = $value['zh_status'];
                            $game->en_status               = $value['en_status'];
                            $game->status                  = $value['status'];
                            $game->pageview                = $value['pageview'];
                            $game->is_recommend            = $value['is_recommend'];
                            $game->is_hot                  = $value['is_hot'];
                            $game->is_pool                 = $value['is_pool'];
                            $game->sort                    = $value['sort'];
                            $game->record_match_code       = $value['record_match_code'];
                            $game->updated_at              = $value['updated_at'];
                            $game->save();
                        }
                    } else {
                        $arr                            = [];
                        $arr['game_id']                 = $value['game_id'];
                        $arr['main_game_plat_id']       = $value['main_game_plat_id'];
                        $arr['game_category']           = $value['game_category'];
                        $arr['main_game_plat_code']     = $value['main_game_plat_code'];
                        $arr['game_name']               = $value['game_name'];
                        $arr['en_game_name']            = $value['en_game_name'];
                        $arr['game_code']               = $value['game_code'];
                        $arr['format']                  = $value['format'];
                        $arr['game_moblie_code']        = $value['game_moblie_code'];
                        $arr['game_icon_square_path']   = $value['game_icon_square_path'];
                        $arr['en_game_icon_square_path']= $value['en_game_icon_square_path'];
                        $arr['zh_status']               = $value['zh_status'];
                        $arr['en_status']               = $value['en_status'];
                        $arr['status']                  = $value['status'];
                        $arr['pageview']                = $value['pageview'];
                        $arr['is_recommend']            = $value['is_recommend'];
                        $arr['is_hot']                  = $value['is_hot'];
                        $arr['is_pool']                 = $value['is_pool'];
                        $arr['sort']                    = $value['sort'];
                        $arr['record_match_code']       = $value['record_match_code'];
                        $arr['created_at']              = $value['created_at'];
                        $arr['updated_at']              = $value['updated_at'];
                        $gameArr[]                      = $arr;

                        if(count($gameArr)==1000){
                            \DB::table('def_games')->insert($gameArr);
                            $gameArr  = [];
                        }
                    }
                }

                if(count($allGamesArr)){
                    \DB::table('def_games')->insert($gameArr);
                    $gameArr  = [];
                }
            }
         }
         if(count($gameArr)){
            \DB::table('def_games')->insert($gameArr);
         }

        $param['type'] = 'language';
        $output        = $this->request(config('game')['pub']['gameurl'].'/api/'.self::SYNCGAME,$param);

        if(isset($output['success'])) {

            //语言
            $languageArr       = [];
            foreach ($output['data']['languages'] as $key => $value) {
                $language = Language::where('name',$value['name'])->first();
                if(!$language){
                    $arr                            = [];
                    $arr['id']                      = $value['id'];
                    $arr['name']                    = $value['name'];
                    $arr['zh_name']                 = $value['zh_name'];
                    $arr['created_at']              = date('Y-m-d H:i:s');
                    $arr['updated_at']              = date('Y-m-d H:i:s');
                    $languageArr[]                  = $arr;
                }
            }

            if(count($languageArr)){
                \DB::table('def_language')->insert($languageArr);
            }

            //币种
            $currencys       = [];
            foreach ($output['data']['currencys'] as $key => $value) {
                $currency = Currency::where('name',$value['name'])->first();
                if(!$currency){
                    $arr                            = [];
                    $arr['id']                      = $value['id'];
                    $arr['name']                    = $value['name'];
                    $arr['zh_name']                 = $value['zh_name'];
                    $arr['created_at']              = date('Y-m-d H:i:s');
                    $arr['updated_at']              = date('Y-m-d H:i:s');
                    $currencys[]                  = $arr;
                }
            }

            if(count($currencys)){
                \DB::table('def_currency')->insert($currencys);
            }
        }

         return true;
    }

    public function updateGame()
    {
        $param['type'] = 'game';
        $output        = $this->request(config('game')['pub']['gameurl'].'/api/'.self::SYNCGAME,$param);

        $gameArr       = [];
        $i             = 0;
        if(isset($output['success'])) {
            $existGames  = Games::orderBy('game_id','asc')->first();
            $allGames    = Games::all();
            $allGamesArr = [];
            foreach ($allGames as $key => $value) {
                $allGamesArr[$value->game_id] = 1;
            }

            foreach ($output['data'] as $key => $value) {
                if(!isset($allGamesArr[$value->game_id])){
                    $arr                            = [];
                    $arr['game_id']                 = $value['game_id'];
                    $arr['main_game_plat_id']       = $value['main_game_plat_id'];
                    $arr['game_category']           = $value['game_category'];
                    $arr['main_game_plat_code']     = $value['main_game_plat_code'];
                    $arr['game_name']               = $value['game_name'];
                    $arr['en_game_name']            = $value['en_game_name'];
                    $arr['game_code']               = $value['game_code'];
                    $arr['format']                  = $value['format'];
                    $arr['game_moblie_code']        = $value['game_moblie_code'];
                    $arr['game_icon_square_path']   = $value['game_icon_square_path'];
                    $arr['en_game_icon_square_path']= $value['en_game_icon_square_path'];
                    $arr['zh_status']               = $value['zh_status'];
                    $arr['en_status']               = $value['en_status'];
                    $arr['status']                  = $value['status'];
                    $arr['pageview']                = $value['pageview'];
                    $arr['is_recommend']            = $value['is_recommend'];
                    $arr['is_hot']                  = $value['is_hot'];
                    $arr['is_pool']                 = $value['is_pool'];
                    $arr['sort']                    = $value['sort'];
                    $arr['record_match_code']       = $value['record_match_code'];
                    $arr['created_at']              = $value['created_at'];
                    $arr['updated_at']              = $value['updated_at'];
                    $gameArr[]                      = $arr;

                    if(count($gameArr)==1000){
                        \DB::table('def_games')->insert($gameArr);
                        $gameArr = [];
                    }
                }
            }

            if(count($gameArr)){
                \DB::table('def_games')->insert($gameArr);
                $gameArr = [];
            }
        }
        return true;
    }

    public function auth() 
    {
        $url   = config('game')['pub']['gameurl'].'/api/'.self::LOGIN;
        $param = [
            'username' => \Yaconf::get(YACONF_PRO_ENV.'.GAME_SYN_ACCONT', ''),
            'password' => \Yaconf::get(YACONF_PRO_ENV.'.GAME_SYN_PASSWORD', ''),
            'key'      => \Yaconf::get(YACONF_PRO_ENV.'.GAME_SYN_KEY', ''),
        ];

        $ch = curl_init($url); //请求的URL地址
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param); //$post_data JSON类型字符串
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
        $output    = curl_exec($ch);
        $error     = curl_error($ch);

        curl_close($ch);
        if (!empty($error)) {
            return false;
           \Log::info('错误信息是'.$error);
        } else {
           $output = json_decode($output,true);
           if(isset($output['success']) && $output['success'] == true) {
                GameCache::setPlatToken('carrier_team',$output['data']);

                return  $output['data']['token'];
           } else {
                \Log::info('游戏鉴权错误请求的参数是',$param);
                \Log::info('游戏鉴权错误请求的返回值是',$output);
                return false;
           }
        }
    }

    public function request($url, $param=array(),$header=[])
    {
       $tokenTime = GameCache::getPlatToken('carrier_team');
       if(!$tokenTime) {
           $token = $this->auth();
            if(!$token) {  
                return false;
            }

            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Accept: application/json';
       } else {
            $explode = explode('____',$tokenTime);

            if($explode[1]<time()) {
               $token = $this->auth();
               if(!$token) {
                    return false;
               } 
            } else {
               $header[] = 'Authorization: Bearer ' . $explode[0];
               $header[] = 'Accept: application/json';
            }
       }

        $ch = curl_init($url); //请求的URL地址
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param); //$post_data JSON类型字符串
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if(count($header)) {
          curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }


        $output    = curl_exec($ch);
        $error     = curl_error($ch);

        curl_close($ch);
    
        if (!empty($error)) {
            \Log::info('错误信息是'.$error);
            return false;
        } else {
           $output = json_decode($output,true);
           
           return $output;
        }
    }
}
