<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CarrierActivityGiftCode;
use App\Models\PlayerHoldGiftCode;

class CarrierActivityGiftCodeInvalidCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carrieractivitygiftcodeinvalid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'carrieractivitygiftcodeinvalid';

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
        //更新过期的
        CarrierActivityGiftCode::where('status',0)->where('endTime','<',time())->update(['status'=>-1]);
        PlayerHoldGiftCode::where('status',0)->where('endTime','<',time())->update(['status'=>-1]);
    }
}