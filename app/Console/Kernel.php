<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\Record\ApiRecordCommand;
use App\Console\Commands\Report\PlayerStatDayCommand;
use App\Console\Commands\Report\PlayerEarningsCommand;
use App\Console\Commands\Report\CarrierOnlineCommand;
use App\Console\Commands\SynKickOnlineCommand;
use App\Console\Commands\CreateGamePlatStatDayCommand;
use App\Console\Commands\MiddleToPlayerStatDayCommand;
use App\Console\Commands\CheckPlayerTransferCommand;
use App\Console\Commands\CreateMonthStatSeederCommand;
use App\Console\Commands\CreateCarrierStatDayCommand;
use App\Console\Commands\DeleteUnipayOrderCommand;
use App\Console\Commands\ReturnCommissionCommand;
use App\Console\Commands\BirthLevelGiftCommand;
use App\Console\Commands\CarrierActivityGiftCodeInvalidCommand;
use App\Console\Commands\StockStatCommand;
use App\Console\Commands\StockMemberStatCommand;
use App\Console\Commands\TransferGamePlatCommand;
use App\Console\Commands\UpdateGameCommand;
use App\Console\Commands\UpdateRealTimeDividendsCommand;
use App\Console\Commands\RefreshRankCommand;
use App\Console\Commands\SendRankCommand;
use App\Console\Commands\CreateCapitationFeeCommand;
use App\Console\Commands\UpdateRewardRateCommand;
use App\Console\Commands\GameDistributeCommand;
use App\Console\Commands\RealTimeStockCommand;
use App\Console\Commands\StatRegisterCodeCommand;
use App\Console\Commands\RealPlayerEarningsCommand;
use App\Console\Commands\IntelligentControlCommand;
use App\Console\Commands\ClearRegisterIpCommand;
use App\Console\Commands\UpdaePlayerLevelCommand;
use App\Console\Commands\BreakThroughCommand;
use App\Console\Commands\HedgingRechargeCommand;
use App\Console\Commands\UnderDirectRebateCommand;
use App\Console\Commands\DeleteRealReturnCommissionCommand;
use App\Console\Commands\refreshGameBalanceCommand;
use App\Console\Commands\StatSiteStockCommand;
use App\Console\Commands\UpdaePlayerGradeCommand;
use App\Console\Commands\BankStatCommand;
use App\Console\Commands\StockSelfCommand;
use App\Console\Commands\AutoTransferToCommand;
use App\Console\Commands\RefreshTodayStockCommand;
use App\Console\Commands\DeleteShortLinkCommand;
use App\Console\Commands\ToAlipayCommand;
//use Spatie\ShortSchedule\ShortSchedule;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SynKickOnlineCommand::class,
        ApiRecordCommand::class,
        PlayerEarningsCommand::class,
        PlayerStatDayCommand::class,
        CreateGamePlatStatDayCommand::class,
        MiddleToPlayerStatDayCommand::class,
        CheckPlayerTransferCommand::class,
        CreateMonthStatSeederCommand::class,
        CreateCarrierStatDayCommand::class,
        CarrierOnlineCommand::class,
        DeleteUnipayOrderCommand::class,
        ReturnCommissionCommand::class,
        BirthLevelGiftCommand::class,
        CarrierActivityGiftCodeInvalidCommand::class,
        StockStatCommand::class,
        StockMemberStatCommand::class,
        TransferGamePlatCommand::class,
        UpdateGameCommand::class,
        UpdateRealTimeDividendsCommand::class,
        RefreshRankCommand::class,
        SendRankCommand::class,
        CreateCapitationFeeCommand::class,
        UpdateRewardRateCommand::class,
        GameDistributeCommand::class,
        RealTimeStockCommand::class,
        StatRegisterCodeCommand::class,
        RealPlayerEarningsCommand::class,
        IntelligentControlCommand::class,
        ClearRegisterIpCommand::class,
        UpdaePlayerLevelCommand::class,
        BreakThroughCommand::class,
        HedgingRechargeCommand::class,
        UnderDirectRebateCommand::class,
        DeleteRealReturnCommissionCommand::class,
        refreshGameBalanceCommand::class,
        StatSiteStockCommand::class,
        UpdaePlayerGradeCommand::class,
        BankStatCommand::class,
        StockSelfCommand::class,
        AutoTransferToCommand::class,
        RefreshTodayStockCommand::class,
        DeleteShortLinkCommand::class,
        ToAlipayCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('kickonline')->name('kickonline')->everyMinute();
        $schedule->command('apiRecordFetch')->name('apiRecordFetch')->everyMinute();
        $schedule->command('playerStatDay')->name('playerStatDay')->everyMinute();
        $schedule->command('carrieractivitygiftcodeinvalid')->name('carrieractivitygiftcodeinvalid')->dailyAt('00:00');
        $schedule->command('deleterealreturncommiss')->name('deleterealreturncommiss')->dailyAt('00:00');
        $schedule->command('refreshtodaystock')->name('refreshtodaystock')->dailyAt('00:00');
        $schedule->command('createGamePlatStatDay')->name('createGamePlatStatDay')->dailyAt('00:01');
        $schedule->command('returnWaterToMiddle')->name('returnWaterToMiddle')->everyMinute();
        $schedule->command('middleToPlayerStatDayCommand')->name('middleToPlayerStatDayCommand')->everyMinute();
        $schedule->command('checkplayertransfer')->name('checkplayertransfer')->everyTenMinutes();
        $schedule->command('createMonthStat')->name('createMonthStat')->dailyAt('00:10');
        $schedule->command('createCarrierStatDay')->name('createCarrierStatDay')->everyMinute();
        $schedule->command('carrierOnline')->name('carrierOnline')->everyTenMinutes();
        $schedule->command('updateRealTimeDividends')->name('updateRealTimeDividends')->dailyAt('00:01');
        $schedule->command('deleteunipayorder')->name('deleteunipayorder')->dailyAt('00:15');
        $schedule->command('createcapitationfee')->name('createcapitationfee')->everyTenMinutes();
        $schedule->command('breakThrough')->name('breakThrough')->dailyAt('00:30');
        $schedule->command('returncommiss')->name('returncommiss')->dailyAt('00:40');
        $schedule->command('birthlevelgift')->name('birthlevelgift')->dailyAt('01:00');
        $schedule->command('StockMemberStat')->name('StockMemberStat')->dailyAt('01:05');
        $schedule->command('StockStat')->name('StockStat')->dailyAt('01:10');
        $schedule->command('playerEarnings')->name('playerEarnings')->dailyAt('03:00');
        $schedule->command('transfergameplat')->name('transfergameplat')->everyThirtyMinutes();
        $schedule->command('updateGame')->name('updateGame')->everyTenMinutes();
        $schedule->command('refreshrank')->name('refreshrank')->everyTenMinutes();
        $schedule->command('sendrank')->name('sendrank')->dailyAt('00:30');
        $schedule->command('updaterewardrate')->name('updaterewardrate')->everyTenMinutes();
        $schedule->command('refreshGameBalance')->name('refreshGameBalance')->everyTenMinutes();
        $schedule->command('hedgingrecharge')->name('hedgingrecharge')->everyTenMinutes();
        $schedule->command('gamedistribute')->name('gamedistribute')->everyTenMinutes();
        $schedule->command('autoTransferTo')->name('autoTransferTo')->everyMinute();
        $schedule->command('realtimestock')->name('realtimestock')->everyThirtyMinutes();
        $schedule->command('statregistercode')->name('statregistercode')->everyThirtyMinutes();
        $schedule->command('realplayerEarnings')->name('realplayerEarnings')->everyMinute();
        $schedule->command('intelligentcontrol')->name('intelligentcontrol')->everyThirtyMinutes();
        $schedule->command('clearregisterip')->name('clearregisterip')->everyMinute();
        $schedule->command('updatePlayerLevel')->name('updatePlayerLevel')->everyFiveMinutes();
        $schedule->command('updatePlayerGrade')->name('updatePlayerGrade')->everyMinute();
        $schedule->command('underdirectrebate')->name('underdirectrebate')->dailyAt('00:30');
        $schedule->command('BankStat')->name('BankStat')->dailyAt('00:02');
        $schedule->command('statsitestock')->name('statsitestock')->everyTenMinutes();
        $schedule->command('StockSelf')->name('StockSelf')->dailyAt('00:00');
        $schedule->command('deleteshortlink')->name('deleteshortlink')->dailyAt('23:57');
        $schedule->command('toalipay')->name('toalipay')->dailyAt('23:55');
    }

//    protected function shortSchedule(ShortSchedule $shortSchedule)
//    {
//        // 此命令每秒钟会运行一次
//        $shortSchedule->command('artisan-command')->everySecond();
//
//        // 此命令每30秒会运行一次
//        $shortSchedule->command('another-artisan-command')->everySeconds(30);
//
//        // 此命令每0.5秒会运行一次
//        $shortSchedule->command('another-artisan-command')->everySeconds(0.5);
//    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
