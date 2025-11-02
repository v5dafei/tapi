<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Models\CarrierPreFixDomain;
use App\Models\Player;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\PlayerBankCard;
use App\Models\Log\BankStat;
use App\Models\PlayerAlipay;
use App\Models\Log\AlipayStat;

class BankStatCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'BankStat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bank Stat';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $carriers = Carrier::all();
        foreach ($carriers as $key => $value) {
            $carrierPreFixDomains = CarrierPreFixDomain::all();
            foreach ($carrierPreFixDomains as $k => $v) {
                $enableCouponsBankStore = CarrierCache::getCarrierMultipleConfigure($value->id,'enable_coupons_bank_store',$v->prefix);
                if($enableCouponsBankStore==1){
                    $codeGiftPlayerIds      = PlayerTransfer::where('prefix',$v->prefix)->where('type','code_gift')->pluck('player_id')->toArray();
                    $playerDepositPayLogIds = PlayerDepositPayLog::where('prefix',$v->prefix)->where('status',1)->pluck('player_id')->toArray();

                    //领了体验券未充值
                    $deletePlayerIds        = array_diff($codeGiftPlayerIds, $playerDepositPayLogIds);
                    $addCardAccounts        = PlayerBankCard::whereIn('player_id',$deletePlayerIds)->pluck('card_account')->toArray();
                    $allBankStat            = BankStat::pluck('banknumber')->toArray();
                    $addCardAccounts        = array_diff($addCardAccounts, $allBankStat);
                    $data = [];
                    foreach ($addCardAccounts as $key1 => $value1) {
                        $rows               = [];
                        $rows['banknumber'] = $value1;
                        $rows['created_at'] = date('Y-m-d H:i:s');
                        $rows['updated_at'] = date('Y-m-d H:i:s');
                        $data[]             = $rows;
                    }

                    \DB::table('log_bank_stat')->insert($data);

                    //删除
                    $deleteCardAccounts        = PlayerBankCard::whereIn('player_id',$playerDepositPayLogIds)->pluck('card_account')->toArray();
                    BankStat::whereIn('banknumber',$deleteCardAccounts)->delete();

                    //支付宝处理
                    $addCardAccounts        = PlayerAlipay::whereIn('player_id',$deletePlayerIds)->pluck('account')->toArray();
                    $allBankStat            = AlipayStat::pluck('banknumber')->toArray();
                    $addCardAccounts        = array_diff($addCardAccounts, $allBankStat);
                    $data = [];
                    foreach ($addCardAccounts as $key1 => $value1) {
                        $rows               = [];
                        $rows['banknumber'] = $value1;
                        $rows['created_at'] = date('Y-m-d H:i:s');
                        $rows['updated_at'] = date('Y-m-d H:i:s');
                        $data[]             = $rows;
                    }

                    \DB::table('log_alipay_stat')->insert($data);

                    //删除
                    $deleteAlipays        = PlayerAlipay::whereIn('player_id',$playerDepositPayLogIds)->pluck('account')->toArray();
                    AlipayStat::whereIn('banknumber',$deleteAlipays)->delete();
                }
            }
        }
    }
}