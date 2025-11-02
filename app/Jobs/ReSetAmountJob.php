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
use App\Models\PlayerAccount;
use App\Models\Player;
use App\Models\Carrier;
use App\Models\PlayerTransfer;
use App\Lib\Cache\CarrierCache;
use App\Game\Game;
use App\Lib\Clog;

class ReSetAmountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $mainGamePlatId = null;
    public $carrier        = null;
    const LOGIN            = 'login';

    public function __construct($mainGamePlatId,$carrier) {
        $this->mainGamePlatId = $mainGamePlatId;
        $this->carrier        = $carrier;
    }

    public function handle()
    {
        $this->resetAmount();
    }

    public function resetAmount()
    {
        $defaultUserName  = CarrierCache::getCarrierConfigure($this->carrier->id,'default_user_name');
        $playerAlls       = Player::where('carrier_id',$this->carrier->id)->where('is_tester',0)->where('frozen_status',0)->where('user_name','<>',$defaultUserName)->get();

        foreach($playerAlls as $key => $value){
            $playerGameAccount = PlayerGameAccount::where('main_game_plat_id',$this->mainGamePlatId)->where('player_id',$value->player_id)->where('exist_transfer',1)->first();
            if($playerGameAccount){
                //有转入游戏操作
                request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
                request()->offsetSet('password',$playerGameAccount->password);
                request()->offsetSet('mainGamePlatCode',$playerGameAccount->main_game_plat_code);

                $game   = new Game($this->carrier,$playerGameAccount->main_game_plat_code);
                $output = $game->getBalance();

                if(is_array($output) && $output['success']) {
                    $currentAmount = $output['data']['balance'];
                    if($currentAmount >= 1){
                        request()->offsetSet('price',intval($currentAmount));
                        $output = $game->transferTo($value);
                        if(is_array($output) && $output['success']){
                            sleep(1);
                        } else{
                            //转帐失败
                            Clog::jobAbnormal('帐号'.$playerGameAccount->account_user_name.'转帐失败');
                        }
                    } else {
                         //游戏中的钱少于1
                    }
                } else {
                    //查询异常
                    Clog::jobAbnormal('帐号'.$playerGameAccount->account_user_name.'查询失败');
                }
            }
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
