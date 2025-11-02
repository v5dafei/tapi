
<?php

use Illuminate\Http\Request;

Route::get('/', function (Request $request) {
    return redirect('home/index');
});

Route::group(['namespace' => "Agent"], function () {


Route::post('login',                                                       'AuthController@login')->name('agent/login');
Route::post('frontlogin',                                                  'AuthController@frontLogin')->name('agent/frontlogin');
Route::post('logout',                                                      'AuthController@logout')->name('agent/logout');
Route::post('register',                                                    'AuthController@register')->name('agent/register');
Route::get('getgoogle',                                                    'AuthController@getGoogle')->name('agent/getgoogle');
Route::post('bindgoogle',                                                  'AuthController@bindGoogle')->name('agent/bindgoogle');
Route::post('closegoogle',                                                 'AuthController@closeGoogle')->name('agent/closegoogle');

Route::post('captcha',                                                     'AgentController@captcha')->name('agent/captcha');
Route::post('balance',                                                     'AgentController@balance')->name('agent/balance'); 
Route::post('checkpaypassword',                                            'AgentController@checkPaypassword')->name('agent/checkpaypassword');
Route::post('websiteinfo',                                                 'AgentController@websiteInfo')->name('agent/websiteinfo');

//首页
Route::post('subactivestat',                                               'AgentController@subActiveStat')->name('agent/subactivestat');                        //下级/当月活跃人数
Route::post('registerfirstdepositstat',                                    'AgentController@registerFirstDepositStat')->name('agent/registerfirstdepositstat');  //注册与首存月报表
Route::post('winorlossstat',                                                'AgentController@winorlossStat')->name('agent/winorlossstat');                       //总输赢与净输赢月报表
Route::post('depositwithdrawstat',                                          'AgentController@depositWithdrawStat')->name('agent/depositwithdrawstat');            //存款与取款月报表
Route::post('advlist',                                                      'AgentController@advList')->name('agent/advlist');   

//会员管理
Route::post('playerdetailstat',                                             'AgentController@playerDetailStat')->name('agent/playerdetailstat');     //本结算周基统计
Route::post('memberdetailstat/{id}',                                        'AgentController@memberDetailStat')->name('agent/memberdetailstat');     //本结算周基统计
Route::post('subordinatelist',                                              'AgentController@subordinateList')->name('agent/subordinatelist');       //会员列表
Route::post('refill',                                                       'AgentController@refill')->name('agent/refill');                         //代充或给给会员发放礼金
Route::post('refilllist',                                                   'AgentController@refillList')->name('agent/refilllist');                 //钱包记录
Route::post('digitaladd/{id?}',                                             'AgentController@digitalAdd')->name('agent/digitaladd');                 //顷定数字币地址
Route::post('deposit',                                                      'AgentController@deposit')->name('agent/deposit'); 
Route::post('withdrawapply',                                                'AgentController@withdrawApply')->name('agent/withdrawapply'); 
Route::post('digitaladdresslist',                                           'AgentController@digitalAddressList')->name('agent/digitaladdresslist'); 
Route::post('childbetstat/{id}',                                            'AgentController@childbetStat')->name('agent/childbetstat'); //查看下级数据统计


//个人中心
Route::post('changepassword',                                               'AgentController@changePassword')->name('agent/changepassword');       //修改密码
Route::post('changepaypassword',                                            'AgentController@changePayPassword')->name('agent/changepaypassword'); //修改支付密码
Route::post('channellist',                                                  'AgentController@channeLlist')->name('agent/channellist'); //修改支付密码

//推广链接
Route::post('promotelinklist',                                               'AgentController@promoteLinklist')->name('agent/promotelinklist'); 

//会员投注记录
Route::post('betlist',                                                       'AgentController@betList')->name('agent/betlist'); 
Route::post('dividendlist',                                                  'AgentController@dividendList')->name('agent/dividendlist');
Route::post('rechargelist',                                                  'AgentController@rechargeList')->name('agent/rechargelist'); 
Route::post('withdrawlist',                                                  'AgentController@withdrawList')->name('agent/withdrawlist');  
Route::post('selfwithdrawlist',                                              'AgentController@selfWithdrawList')->name('agent/selfwithdrawlist'); 

//佣金报表
Route::post('settlementdatelist',                                            'AgentController@settlementDateList')->name('agent/settlementdatelist'); 
Route::post('commissionpersonalreport',                                      'AgentController@commissionPersonalReport')->name('agent/commissionpersonalreport'); 
Route::post('commissionteamreport',                                          'AgentController@commissionTeamReport')->name('agent/commissionteamreport'); 

//财务报表
Route::post('financepersonalreport',                                         'AgentController@financePersonalReport')->name('agent/financepersonalreport'); 
Route::post('financeteamreport',                                             'AgentController@financeTeamreport')->name('agent/financeteamreport'); 
Route::post('financeexchangerate',                                           'AgentController@financeExchangeRate')->name('agent/financeexchangerate'); 
Route::post('digitaltype',                                                   'AgentController@digitaltype')->name('agent/digitaltype');

//佣金规则
Route::post('init',                                                           'AgentController@init')->name('agent/init');
Route::post('venuefee',                                                       'AgentController@venuefee')->name('agent/venuefee');

//会员开通的游戏平台
Route::post('membergameplat',                                                 'AgentController@memberGameplat')->name('agent/membergameplat');
Route::post('gamebalance',                                                    'AgentController@gameBalance')->name('agent/gamebalance');

//设置保底
Route::post('setguaranteed',                                                  'AgentController@setGuaranteed')->name('agent/setguaranteed'); 

//设置分红
Route::post('setearning',                                                     'AgentController@setEarning')->name('agent/setearning'); 

//我的名片
Route::post('mycarte',                                                        'AgentController@myCarte')->name('agent/mycarte'); 

//修改名片
Route::post('mycartesave',                                                     'AgentController@mycarteSave')->name('agent/mycartesave'); 

//上级名片
Route::post('parentcarte',                                                     'AgentController@parentCarte')->name('agent/parentcarte'); 

//创建代理
Route::post('createagent',                                                     'AgentController@createAgent')->name('agent/createagent');

//首页统计
Route::post('indexstat',                                                       'AgentController@indexstat')->name('agent/indexstat');

//分员列表
Route::post('memberlist',                                                       'AgentController@memberList')->name('agent/memberlist');

//获取结算的开始日期
Route::post('settlementstart',                                                  'AgentController@settlementStart')->name('agent/settlementstart');

//我的体验券
Route::post('agentvoucherlist',                                                 'AgentController@agentVoucherList')->name('agent/agentvoucherlist');

//财务报表直属报表
Route::post('financepersonalhistoryreport',                                     'AgentController@financePersonalHistoryReport')->name('agent/financepersonalhistoryreport');

//财务报表直属时间段搜索
Route::post('membertimeintervalstat',                                           'AgentController@memberTimeIntervalStat')->name('agent/membertimeintervalstat');

//财务报表团队报表
Route::post('financeteamhistoryreport',                                           'AgentController@financeTeamHistoryReport')->name('agent/financeteamhistoryreport');

//财务报表团队报表时间段搜索
Route::post('teamtimeintervalstat',                                               'AgentController@teamTimeIntervalStat')->name('agent/teamtimeintervalstat');

//发放体验券
Route::post('sendagentvoucher',                                                 'AgentController@sendAgentvoucher')->name('agent/sendagentvoucher');

});


