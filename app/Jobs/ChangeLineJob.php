<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Def\Game as Games;
use App\Models\Def\MainGamePlat;
use App\Models\PlayerGameAccount;
use App\Models\Player;
use App\Models\Carrier;
use App\Game\Game;

class ChangeLineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $mainGamePlatId = null;
    const LOGIN            = 'login';

    public function __construct($mainGamePlatId) {
        $this->mainGamePlatId = $mainGamePlatId;
    }

    public function handle()
    {
        $this->changeLine();
    }

    public function changeLine()
    {
        $carriers                 = Carrier::where('is_forbidden',0)->get();
        $mainGamePlat             = MainGamePlat::where('main_game_plat_id',$this->mainGamePlatId)->first();
        $mainGamePlat->changeLine = 1;
        $mainGamePlat->save();

        foreach ($carriers as $key => $value) {
            $playerGameAccounts = PlayerGameAccount::where('carrier_id',$value->id)->where('main_game_plat_id',$this->mainGamePlatId)->where('exist_transfer',1)->get();
            foreach ($playerGameAccounts as $k => $v) {
                //查询余额
                request()->offsetSet('accountUserName',$v->account_user_name);
                request()->offsetSet('password',$v->password);
                request()->offsetSet('mainGamePlatCode',$mainGamePlat->main_game_plat_code);

                \Log::info('平台代码是'.$mainGamePlat->main_game_plat_code);
                $game   = new Game($value,$mainGamePlat->main_game_plat_code);
                $output = $game->getBalance();

                if(is_array($output) && $output['success']) {
                    $currentAmount = $output['data']['balance'];
                    if(($value->currency=='VND' && $currentAmount<1000) || $currentAmount < 1){
                        $v->delete();
                    } else {
                        //转出游戏
                        $player = Player::where('player_id',$v->player_id)->first();
                        request()->offsetSet('price',intval($currentAmount));
                        $output = $game->transferTo($player);

                        if(is_array($output) && $output['success']){
                            $v->delete();
                            sleep(1);
                        }
                    }
                }
            }
        }

        $playerGameAccounts = PlayerGameAccount::where('main_game_plat_id',$this->mainGamePlatId)->first();
        if(!$playerGameAccounts){
            $mainGamePlat->changeLine = 2;
            $mainGamePlat->save();
        }

         return true;
    }

    public function auth() {
        $url   = config('game')['pub']['gameurl'].'/api/'.self::LOGIN;
        $param = [
            'username' => $this->carrier->apiusername,
            'password' => $this->carrier->apipassword,
            'key'      => $this->carrier->apikey,
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
                GameCache::setPlatToken('carrier_'.$this->carrier->id,$output['data']);

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
       $tokenTime = GameCache::getPlatToken('carrier_'.$this->carrier->id);
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
