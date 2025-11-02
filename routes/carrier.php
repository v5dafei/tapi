<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('carrier/login');
});

Route::group(['namespace' => "Carrier"], function () {
Route::post('login',                       							'AuthController@login')->name('carrier/login');
Route::post('switchlogin',                                          'AuthController@switchLogin')->name('carrier/switchlogin');

Route::post('sendcode',                       					    'AuthController@sendcode')->name('carrier/sendcode');
Route::post('updatepassword',                       			    'AuthController@updatePassword')->name('carrier/updatepassword');
Route::post('logout',                       			            'AuthController@logout')->name('carrier/logout');
// 谷歌验证
Route::get('getGoogle',                       						'AuthController@getGoogle')->name('carrier/getGoogle');
Route::post('bindGoogle',                       					'AuthController@bindGoogle')->name('carrier/bindGoogle');
Route::post('closeGoogle/{id}',                       			    'AuthController@closeGoogle')->name('carrier/closeGoogle');

//初始化
Route::post('init',                                                     'SystemController@init')->name('carrier/init');

//菜单列表
Route::post('menus',                       			                    'SystemController@menus')->name('carrier/menus');

//当前额度
Route::post('remainquota',                       			            'SystemController@remainquota')->name('carrier/remainquota');

//所有会员列表
Route::post('playerlist',                       			            'PlayerController@playerList')->name('carrier/playerlist');
Route::post('playeradd',                       			                'PlayerController@playerAdd')->name('carrier/playeradd');
Route::post('playerinfo/{id}',                       		            'PlayerController@playerInfo')->name('carrier/playerinfo');
Route::post('updateplayerinfo/{id}',                       		        'PlayerController@updatePlayerInfo')->name('carrier/updateplayerinfo');
Route::post('kickplayeroutline/{id}',                       			'PlayerController@kickPlayerOutline')->name('carrier/kickplayeroutline');
Route::post('changeplayerstatus/{id}',                       			'PlayerController@changePlayerStatus')->name('carrier/changeplayerstatus');
Route::post('changeplayerfrozenstatus/{id}',                       		'PlayerController@changePlayerFrozenStatus')->name('carrier/changeplayerfrozenstatus');
Route::post('changeplayerpassword/{id}',                       		    'PlayerController@changePlayerPassword')->name('carrier/changeplayerpassword');
Route::post('playertransfer/{id}',                       		        'PlayerController@playerTransfer')->name('carrier/playertransfer');
Route::post('addreduce/{id}',                       		            'PlayerController@addreduce')->name('carrier/addreduce');
Route::post('playerbalanceinfo/{id}',                       		    'PlayerController@playerBalanceInfo')->name('carrier/playerbalanceinfo');
Route::post('playerfinanceinfo/{id}',                       		    'PlayerController@playerFinanceinfo')->name('carrier/playerfinanceinfo');
Route::post('playertransferlist/{id}',                       		    'PlayerController@playerTransferList')->name('carrier/playertransferlist');
Route::post('playercasinotransferlist',                       		    'PlayerController@playerCasinoTransferList')->name('carrier/playercasinotransferlist');
Route::post('playercasinotransfercheck/{id}',                           'PlayerController@playerCasinoTransferCheck')->name('carrier/playercasinotransfercheck');
Route::post('playercasinotransfersetting/{id}',                         'PlayerController@playerCasinoTransferSetting')->name('carrier/playercasinotransfersetting');
Route::post('playergameplats/{id}',                       		        'PlayerController@playerGameplats')->name('carrier/playergameplats');
Route::post('playerlogininfo/{id}',                       		        'PlayerController@playerLoginInfo')->name('carrier/playerlogininfo');
Route::post('playerbanklist/{id?}',                       		        'PlayerController@playerBankList')->name('carrier/playerbanklist');
Route::post('playerbankdelete/{id}',                       		        'PlayerController@playerbankDelete')->name('carrier/playerbankdelete');
Route::post('playerbankedit/{id}',                       		        'PlayerController@playerBankEdit')->name('carrier/playerbankedit');

Route::post('playeralipaylist/{id?}',                                    'PlayerController@playerAlipayList')->name('carrier/playeralipaylist');
Route::post('playeralipaydelete/{id}',                                   'PlayerController@playerAlipayDelete')->name('carrier/playeralipaydelete');
Route::post('playeralipayedit/{id}',                                     'PlayerController@playerAlipayEdit')->name('carrier/playeralipayedit');

Route::post('playerdigitaladdresslist/{id?}',                       	'PlayerController@playerDigitalAddressList')->name('carrier/playerdigitaladdresslist');
Route::post('playerdigitaladdressdelete/{id}',                       	'PlayerController@playerDigitalAddressDelete')->name('carrier/playerdigitaladdressdelete');
Route::post('playerdigitaladdressedit/{id}',                       	    'PlayerController@playerDigitalAddressEdit')->name('carrier/playerdigitaladdressedit');
Route::post('playerexchangelist/{id?}',                       		    'PlayerController@playerExchangeList')->name('carrier/playerexchangelist');
Route::post('changeagentline/{id}',                       		        'PlayerController@changeAgentline')->name('carrier/changeagentline');
Route::post('changeplayerdelayorder/{id}',                       	    'PlayerController@changePlayerDelayOrder')->name('carrier/changeplayerdelayorder');   //卡奖
Route::post('scorelist/{id}',                                           'PlayerController@scoreList')->name('carrier/scorelist');
Route::post('playergrouplist',                                          'PlayerController@playerGroupList')->name('carrier/playergrouplist');
Route::post('playergameaccountlist',                                    'PlayerController@playerGameAccountList')->name('carrier/playergameaccountlist');     

Route::post('playerspreadlist',                                         'PlayerController@playerSpreadlist')->name('carrier/playerspreadlist'); 
Route::post('bindbankcard/{id}',                       		            'PlayerController@bindBankcard')->name('carrier/bindbankcard');
Route::post('cancelmobilecode/{id}',                                    'PlayerController@cancelMobileCode')->name('carrier/cancelmobilecode');
Route::post('freezearbitrage/{id}',                                     'PlayerController@freezeArbitrage')->name('carrier/freezearbitrage');

//会员业绩统计
Route::post('performancestat/{id}',                                     'PlayerController@performanceStat')->name('carrier/performancestat');

//团队业绩统计
Route::post('teamperformancestat/{id}',                                  'PlayerController@teamPerformanceStat')->name('carrier/teamperformancestat');

//所有银行卡列表
Route::post('memberbanklist',                                           'PlayerController@memberBankList')->name('carrier/memberbanklist');
Route::post('memberdigitaladdresslist',                                 'PlayerController@memberDigitalAddressList')->name('carrier/memberdigitaladdresslist');

//所有支付宝列表
Route::post('memberalipaylist',                                          'PlayerController@memberAlipayList')->name('carrier/memberalipaylist');

//变更直播号状态
Route::post('livestreamingchange/{id}',                                 'PlayerController@liveStreamingChange')->name('player/livestreamingchange');   

//变更对冲号状态
Route::post('hedgingChange/{id}',                                       'PlayerController@hedgingChange')->name('player/hedgingChange');  

//游戏相关
Route::post('getbalance/{id}',                       		             'GameController@getBalance')->name('carrier/getbalance');
Route::post('transferin/{id}',                       		             'GameController@transferIn')->name('carrier/transferin');
Route::post('transferto/{id}',                       		             'GameController@transferTo')->name('carrier/transferto');
Route::post('kick/{id}',                       		                     'GameController@kick')->name('carrier/kick');
Route::post('changelock/{id}',                       		             'GameController@changeLock')->name('carrier/changelock');
Route::post('changerepair/{id}',                       		             'GameController@changeRepair')->name('carrier/changerepair');

//下级
Route::post('directlyunder/{id}',                       		         'PlayerController@directlyUnder')->name('carrier/directlyunder');
Route::post('allunder/{id}',                       		                 'PlayerController@allUnder')->name('carrier/allunder');

//获取赔率与返水
Route::post('odds/{id}',                       		                      'PlayerController@odds')->name('carrier/odds');
Route::post('setplayersalary/{id}',                       		          'PlayerController@setPlayerSalary')->name('carrier/setplayersalary');


//用户等级列表
Route::post('playerlevellist',                       			         'PlayerLevelController@playerLevelList')->name('carrier/playerlevellist');
Route::post('playerleveladd',                       			         'PlayerLevelController@playerLevelAdd')->name('carrier/playerleveladd');
Route::post('playerleveldel/{id}',                       			     'PlayerLevelController@playerLevelDel')->name('carrier/playerleveldel');


Route::post('playerlevelthirdpaylist/{id}',                       	     'PlayerLevelController@playerLevelThirdpayList')->name('carrier/playerlevelthirdpaylist');
Route::post('playerlevelthirdpayupdate/{id}',                       	 'PlayerLevelController@playerlevelThirdpayupdate')->name('carrier/playerlevelthirdpayupdate');
Route::post('playerlevelcarrierbanklist/{id}',                       	 'PlayerLevelController@playerLevelCarrierBankList')->name('carrier/playerlevelcarrierbanklist');
Route::post('playerlevelcarrierbankupdate/{id}',                       	 'PlayerLevelController@playerlevelCarrierBankupdate')->name('carrier/playerlevelcarrierbankupdate');

Route::post('playergameplatlimit/{id}',                                   'PlayerController@gameplatLimit')->name('carrier/playergameplatlimit');                     //积分兑换
Route::post('playergameaccountclear/{id}',                       	      'PlayerController@playerGameAccountClear')->name('carrier/playergameaccountclear');

//用户层级列表
Route::post('playergradelist',                       			         'PlayerLevelController@playerGradeList')->name('carrier/playergradelist');
Route::post('playergradeadd',                       			         'PlayerLevelController@playerGradeAdd')->name('carrier/playergradeadd');
Route::post('playergradedel/{id}',                                       'PlayerLevelController@playerGradeDel')->name('carrier/playergradedel');


//会员域名列表
Route::post('playerinvitecodelist',                       			     'PlayerController@playerInvitecodeList')->name('carrier/playerinvitecodelist');
Route::post('updateplayerinvitecode/{id}',                       		 'PlayerController@updatePlayerinvitecode')->name('carrier/updateplayerinvitecode');

//会员资金
Route::post('depositlist',                       			            'FinanceController@depositList')->name('carrier/depositlist');
Route::post('depositcollect',                       			        'FinanceController@depositCollect')->name('carrier/depositcollect');
Route::post('depositauditlist',                       			        'FinanceController@depositAuditList')->name('carrier/depositauditlist');
Route::post('depositaudit/{id}',                       			        'FinanceController@depositAudit')->name('carrier/depositaudit');
Route::post('withdrawlist',                       			            'FinanceController@withdrawList')->name('carrier/withdrawlist');
Route::post('agentwithdrawlist',                                        'FinanceController@agentWithdrawList')->name('carrier/agentwithdrawlist');
Route::post('withdrawauditlist',                       			        'FinanceController@withdrawAuditList')->name('carrier/withdrawauditlist');
Route::post('withdrawaudit',                       			            'FinanceController@withdrawAudit')->name('carrier/withdrawaudit');
Route::post('withdrawsuccess',                       			        'FinanceController@withdrawsuccess')->name('carrier/withdrawsuccess');
Route::post('withdrawslimitlist',                       			    'FinanceController@withdrawsLimitList')->name('carrier/withdrawslimitlist');
Route::post('withdrawslimitcomplete',                       			'FinanceController@withdrawsLimitComplete')->name('carrier/withdrawslimitcomplete');
Route::post('resetwithdrawslimit/{id}',                       			'FinanceController@resetWithdrawsLimit')->name('carrier/resetwithdrawslimit');
Route::post('addwithdrawslimit/{id}',                       			'FinanceController@addWithdrawsLimit')->name('carrier/addwithdrawslimit');
Route::post('collectionfactorylist',                                    'FinanceController@collectionFactoryList')->name('carrier/collectionfactorylist');
Route::post('collectionpaychannellist',                                 'FinanceController@collectionPaychannelList')->name('carrier/collectionpaychannellist');
Route::post('report/earnlinglist',                       		        'ReportController@earnlingList')->name('report/earnlinglist');
Route::post('report/cardearnlinglist',                                  'ReportController@cardEarnlingList')->name('report/cardearnlinglist');
Route::post('report/financebriefing',                                   'FinanceController@financeBriefing')->name('report/financebriefing'); 

Route::post('report/realearnlinglist',                                   'ReportController@realEarnlingList')->name('report/realearnlinglist');

//实时分红详情
Route::post('report/realearnlingdesc/{id}',                              'ReportController@realearnlingDesc')->name('report/realearnlingdesc');

//一键发放
Route::post('report/sendallearnling',                                   'ReportController@sendAllEarnling')->name('report/sendallearnling');

//充提统计
Route::post('financestat',                                              'FinanceController@stat')->name('carrier/financestat');

//佣金列表
Route::post('report/commissionlist',                                    'ReportController@commissionList')->name('report/commissionlist');
Route::post('report/sendcommission',                                    'ReportController@sendCommission')->name('report/sendcommission');
Route::post('report/cancelcommission',                                  'ReportController@cancelCommission')->name('report/cancelcommission');

Route::post('report/sendearnling',                       		        'ReportController@sendEarnling')->name('report/sendearnling'); 
Route::post('report/cancelsendearnling',                                'ReportController@cancelSendEarnling')->name('report/cancelsendearnling');
Route::post('report/accumulationnext',                                  'ReportController@accumulationNext')->name('report/accumulationnext');  
Route::post('transferlist',                       			            'FinanceController@transferList')->name('carrier/transferlist');
Route::post('transfertypelist',                       		            'FinanceController@transferTypeList')->name('carrier/transfertypelist'); 
Route::post('paymentonbehalflist',                       		        'FinanceController@paymentOnBehalfList')->name('carrier/paymentonbehalflist');
Route::post('paymentonbehalf',                       		            'FinanceController@paymentOnBehalf')->name('carrier/paymentonbehalf'); 
Route::post('checkpaymentonbehalf/{id}',                       		    'FinanceController@checkPaymentOnBehalf')->name('carrier/checkpaymentonbehalf'); 
Route::post('withdrawcancel/{id}',                       		        'FinanceController@withdrawCancel')->name('carrier/withdrawcancel'); 
Route::post('financialstat',                       		                'FinanceController@financialStat')->name('carrier/financialstat');
Route::post('pageFinancialStat',                       		            'FinanceController@pageFinancialStat')->name('carrier/pageFinancialStat');
Route::post('giftlist',                                                 'FinanceController@giftList')->name('carrier/giftlist');
Route::post('redbaglist',                                               'FinanceController@redbagList')->name('carrier/redbaglist');
Route::post('playergamestat',                                           'FinanceController@playerGameStat')->name('carrier/playergamestat');    //玩家游戏统计
Route::post('specialgamestat',                                          'FinanceController@specialGamestat')->name('carrier/specialgamestat');    //玩家游戏统计
Route::post('gifttypelist',                                             'FinanceController@giftTypelist')->name('carrier/gifttypelist');

//添加银行卡黑名单
Route::post('arbitragebankadd/{id}',                                    'FinanceController@arbitrageBankAdd')->name('carrier/arbitragebankadd'); 
Route::post('arbitragebankalist',                                       'FinanceController@arbitrageBankaList')->name('carrier/arbitragebankalist'); 
       
//扣减与理赔
Route::post('addreducelist',                       		                'SystemController@addReduceList')->name('carrier/addreducelist');

//优惠活动
Route::post('activitylist',                       		                'ActivityController@activityList')->name('carrier/activitylist');
Route::post('activitysave',                       		                'ActivityController@activitySave')->name('carrier/activitysave');
Route::post('activitydel',                       		                'ActivityController@activityDel')->name('carrier/activitydel');
Route::post('activitychangestatus',                       		        'ActivityController@activityChangeStatus')->name('carrier/activitychangestatus');
Route::post('activitieslist',                       		            'ActivityController@activitiesList')->name('carrier/activitieslist');
Route::post('changeactivitystatus',                       		        'ActivityController@changeActivityStatus')->name('carrier/changeactivitystatus');
Route::post('activitiesreport',                       		            'ActivityController@activitiesReport')->name('carrier/activitiesreport');
Route::post('activitiesauthlist',                       		        'ActivityController@activitiesAuthList')->name('carrier/activitiesauthlist');
Route::post('activitiesauthhistory',                       		        'ActivityController@activitiesAuthHistory')->name('carrier/activitiesauthhistory');
Route::post('activitiesauth',                       		            'ActivityController@activitiesAuth')->name('carrier/activitiesauth');
Route::post('activitiesreceivegiftcenter',                              'ActivityController@activitiesReceiveGiftCenter')->name('carrier/activitiesreceivegiftcenter');

//会员投注记录
Route::post('betflowlist',                       		                'GameController@betflowList')->name('carrier/betflowlist');

//签到人员列表
Route::post('activitysigninlist',                                       'ActivityController@activitySignInList')->name('carrier/activitysigninlist');

//注册赠送人员列表
Route::post('activityplayerregistergiftlist',                            'ActivityController@activityPlayerRegisterGiftList')->name('carrier/activityplayerregistergiftlist');

//排行榜列表
Route::post('ranklist',                                                  'ActivityController@rankList')->name('carrier/ranklist');

//添加或编辑
Route::post('addrank/{id?}',                                             'ActivityController@addRank')->name('carrier/addrank');

Route::post('flowcommissionlist',                                         'ActivityController@flowcommissionlist')->name('carrier/flowcommissionlist');

//实时佣金列表
Route::post('realflowcommissionlist',                                     'ActivityController@realFlowcommissionList')->name('carrier/realflowcommissionlist');

//实时佣金详情
Route::post('realflowcommissiondesc/{id}',                                 'ActivityController@realFlowcommissionDesc')->name('carrier/realflowcommissiondesc');

//变更排行榜状态
Route::post('changerankstatus/{id}',                                      'ActivityController@changeRankStatus')->name('carrier/changerankstatus');

//业绩查询
Route::post('performanceinquire',                                        'PlayerController@performanceInquire')->name('player/performanceinquire');

//优惠活动图片列表
Route::post('activitiesimglist',                       		             'ImgController@activitiesImgList')->name('carrier/activitiesimglist');
Route::post('activitysaveone',                       		             'ActivityController@activitySaveOne')->name('carrier/activitysaveone');
Route::post('activitysavetwo',                       		             'ActivityController@activitySaveTwo')->name('carrier/activitysavetwo');
Route::post('activityinfo/{id}',                       		             'ActivityController@activityInfo')->name('carrier/activityinfo');

//轮盘活动列表
Route::post('activitiesluckdrawlist',                       		     'ActivityController@activitiesLuckdrawList')->name('carrier/activitiesluckdrawlist');
Route::post('activitiesluckdrawadd/{id?}',                       		 'ActivityController@activitiesLuckdrawAdd')->name('carrier/activitiesluckdrawadd');
Route::post('activitiesluckdrawaedit/{id}',                       		 'ActivityController@activitiesLuckdrawEdit')->name('carrier/activitiesluckdrawaedit');
Route::post('activitiesluckdrawstatus/{id}',                       		 'ActivityController@activitiesLuckdrawStatus')->name('carrier/activitiesluckdrawstatus');
Route::post('activityplayerluckdrawlist',                       		 'ActivityController@activityPlayerLuckDrawList')->name('carrier/activityplayerluckdrawlist');

//体验券活动
Route::post('activitygiftcodelist',                                      'ActivityController@giftcodeList')->name('carrier/activitygiftcodelist');
Route::post('activitygiftcodesave',                                      'ActivityController@giftcodeSave')->name('carrier/activitygiftcodesave');
Route::post('activitygiftcodedistribute',                                'ActivityController@giftcodeDistribute')->name('carrier/activitygiftcodedistribute');
Route::post('activitygiftcodedel/{id}',                                  'ActivityController@giftcodeDel')->name('carrier/activitygiftcodedel');
Route::post('activitygiftcodepersonlist',                                'ActivityController@giftcodePersonPersonList')->name('carrier/activitygiftcodepersonlist');
Route::post('activityofflinegiftcode',                                   'ActivityController@activityOfflineGiftcode')->name('carrier/activityofflinegiftcode');

//闯关活动
Route::post('taskadd/{id?}',                                             'ActivityController@taskAdd')->name('carrier/taskadd');
Route::post('taskchangestatus/{id}',                                    'ActivityController@taskChangeStatus')->name('carrier/taskchangestatus');
Route::post('taskdel/{id}',                                              'ActivityController@taskDel')->name('carrier/taskdel');
Route::post('tasklist',                                                  'ActivityController@taskList')->name('carrier/tasklist');

Route::post('activitiesbreakthroughplayerlist',                          'ActivityController@activitiesBreakThroughPlayerList')->name('carrier/activitiesbreakthroughplayerlist');

//人头费列表
Route::post('capitationfeelist',                                        'ActivityController@capitationFeeList')->name('carrier/capitationfeelist');
Route::post('capitationfeelchangestatus/{id}',                          'ActivityController@capitationFeelChangeStatus')->name('carrier/capitationfeelchangestatus');

//资金管理
Route::post('paychannellist',                       		            'PayChannelController@paychannelList')->name('carrier/paychannellist');
Route::post('paychannechangestatus/{id}',                               'PayChannelController@paychanneChangeStatus')->name('carrier/paychannechangestatus');
Route::post('paychanneladd',                       		                'PayChannelController@paychannelAdd')->name('carrier/paychanneladd');
Route::post('paychannelunbind/{id}',                       		        'PayChannelController@paychannelUnbind')->name('carrier/paychannelunbind');
Route::post('paychannelbind/{id?}',                       		        'PayChannelController@paychannelBind')->name('carrier/paychannelbind');
Route::post('thirdpaylist/{id}',                       		            'PayChannelController@thirdPayList')->name('carrier/thirdpaylist');
Route::post('thirdpayadd',                       		                'PayChannelController@thirdPayAdd')->name('carrier/thirdpayadd');
Route::post('allpaychannel/{id}',                       		        'PayChannelController@allPaychannel')->name('carrier/allpaychannel');
Route::post('allthirdpartpaylist',                       		        'PayChannelController@allThirdpartpayList')->name('carrier/allthirdpartpaylist');
Route::post('paychannellistnopage',                                     'PayChannelController@paychannelListNopage')->name('carrier/paychannellistnopage');

//数据监控列表
Route::post('datamonitor',                                              'SystemController@dataMonitor')->name('carrier/datamonitor');

//通道分组管理
Route::post('paychannelgrouplist',                                      'PayChannelController@payChannelGroupList')->name('carrier/paychannelgrouplist');
Route::post('paychannelgroupadd/{id?}',                                 'PayChannelController@payChannelGroupAdd')->name('carrier/paychannelgroupadd');
Route::post('paychannelgroupchangestatus/{id}',                         'PayChannelController@payChannelGroupChangeStatus')->name('carrier/paychannelgroupchangestatus');
Route::post('paychannelgroupdel/{id}',                                  'PayChannelController@payChannelGroupDel')->name('carrier/paychannelgroupdel');
Route::post('allcarrierpaychannel',                                     'PayChannelController@allCarrierPaychannel')->name('carrier/allcarrierpaychannel');

//银行卡类型管理
Route::post('banktypelist',                                             'PayChannelController@banktypeList')->name('carrier/banktypelist'); 
Route::post('banktypepagelist',                                         'CarrierBankTypeController@bankList')->name('carrier/banktypepagelist');
Route::post('banktypeadd/{id?}',                                        'CarrierBankTypeController@bankAdd')->name('carrier/banktypeadd');
Route::post('banktypedel/{id}',                                         'CarrierBankTypeController@bankDel')->name('carrier/banktypedel');

//收款银行卡管理
Route::post('cashbanklist',                       		                'PayChannelController@cashBanklist')->name('carrier/cashbanklist');
Route::post('cashbankadd',                       		                'PayChannelController@cashbankAdd')->name('carrier/cashbankadd');
Route::post('changecashbankstatus/{id}',                       		    'PayChannelController@changeCashBankStatus')->name('carrier/changecashbankstatus');

Route::post('payfactory/{id}',                                           'PayChannelController@payFactory')->name('carrier/payfactory');  

//收款数字币管理
Route::post('digitaladd/{id?}',                                          'PayChannelController@digitalAdd')->name('carrier/digitaladd');
Route::post('digitalchangestatus/{id?}',                                 'PayChannelController@digitalChangeStatus')->name('carrier/digitalchangestatus');
Route::post('digitallist',                                               'PayChannelController@digitalList')->name('carrier/digitallist');
Route::post('digitaltype',                                               'PayChannelController@digitaltype')->name('carrier/digitaltype');


//系统设置
Route::post('system/websiteinfo',                       		        'SystemController@websiteInfo')->name('system/websiteinfo');
Route::post('system/websitesave',                       		        'SystemController@websiteSave')->name('system/websitesave');
Route::post('system/websitemultiplesave',                               'SystemController@websiteMultipleSave')->name('system/websitemultiplesave');
Route::post('system/telegramchannel',                       		    'SystemController@telegramChannel')->name('system/telegramchannel');
Route::post('system/telegrambotsave',                       		    'SystemController@telegramBotsave')->name('system/telegrambotsave');
Route::post('system/telegramchannelsave',                       		'SystemController@telegramChannelSave')->name('system/telegramchannel');
Route::post('system/getalllanguage',                       		        'SystemController@getAllLanguage')->name('system/getalllanguage');

//游戏设置
Route::post('system/platlist',                       		            'SystemController@platList')->name('system/platlist');
Route::post('system/platsave/{carrierplatid}',                          'SystemController@platSave')->name('system/platsave');
Route::post('system/gamelist/{platid}',                       		    'SystemController@gameList')->name('system/gameList');
Route::post('system/changestatus/{carriergameid}',                      'SystemController@changeStatus')->name('system/changestatus');
Route::post('system/changerecommend/{carriergameid}',                   'SystemController@changeRecommend')->name('system/changerecommend');
Route::post('system/changehot/{carriergameid}',                         'SystemController@changeHot')->name('system/changehot');
Route::post('system/gamesave/{carriergameid}',                          'SystemController@gameSave')->name('system/gamesave');

//角色管理
Route::post('system/serviceteamadd/{id?}',                              'SystemController@serviceTeamAdd')->name('system/serviceteamadd');            
Route::post('system/serviceteamstatus/{id}',                            'SystemController@serviceTeamStatus')->name('system/serviceteamstatus');
Route::post('system/serviceteamslist',                                  'SystemController@serviceTeamList')->name('system/serviceteamslist');
Route::post('system/grouppermission/{id}',                              'SystemController@groupPermission')->name('system/grouppermission');
Route::post('system/serviceteampermissionsave/{id}',                    'SystemController@serviceTeamPermissionSave')->name('system/serviceteampermissionsave');

//员工管理
Route::post('system/carrieruserlist',                                   'SystemController@carrierUserList')->name('system/carrieruserlist');
Route::post('system/carrieruserstatus/{id}',                            'SystemController@carrierUserStatus')->name('system/carrieruserstatus');
Route::post('system/carrieruseradd',                                    'SystemController@carrierUserAdd')->name('system/carrieruseradd');
Route::post('system/carrieredititem/{id}',                              'SystemController@carrierEditItem')->name('system/carrieredititem');
Route::post('system/carrieruserresetpassword/{id}',                     'SystemController@carrierUserResetPassword')->name('system/carrieruserresetpassword');

//系统消息
Route::post('system/systemnoticelist/',                                 'SystemController@systemNoticeList')->name('system/systemnoticelist');

//消息管理
Route::post('mesage/messagesave',                       		         'MessageController@messageSave')->name('message/messagesave');
Route::post('message/messagelist',                       		         'MessageController@messageList')->name('message/messagelist');
Route::post('message/memberlist/{playerid?}',                            'MessageController@memberList')->name('message/memberlist');

//图片管理
//上传
Route::post('fileupload/{directory}',                       		    'ImgController@fileUpload')->name('carrier/fileupload');

//图片管理
Route::post('carrierimg/imgsave/{carrierimgid?}',                       'ImgController@imgSave')->name('carrier/carrierimgsave');
Route::post('carrierimg/imglist',                                       'ImgController@imgList')->name('carrier/carrierimglist');
Route::post('carrierimg/imgdel/{carrierimgid}',                         'ImgController@imgDel')->name('carrier/carrierimgdel');
Route::post('carrierimg/categorylist',                                  'ImgController@categoryList')->name('carrier/carrierimgcategorylist');

//问题管理
Route::post('article/questionlists',                                     'ArticleController@questionLists')->name('article/questionlists');
Route::post('article/questionadd/{id?}',                                 'ArticleController@questionAdd')->name('article/questionadd');
Route::post('article/questiondelete/{id}',                               'ArticleController@questionDelete')->name('article/questiondelete');
Route::post('article/questiontypelist',                                  'ArticleController@questionTypeList')->name('article/questiontypelist');

//反馈列表
Route::post('article/feedbacklist',                                     'ArticleController@feedbackList')->name('article/feedbacklist');

//报表中心
Route::post('report/statdaylist',                       		         'ReportController@statdayList')->name('report/statdaylist');              //个人日报表
Route::post('report/totalstatdaylist',                       		     'ReportController@totalStatdayList')->name('report/totalstatdaylist');    //用户总报表
Route::post('report/gameplatlist',                       		         'ReportController@gameplatList')->name('report/gameplatlist');            //游戏平台报表
Route::post('report/lotteryplatlist',                       		     'ReportController@lotteryPlatList')->name('report/lotteryplatlist');       //彩票游戏报表
Route::post('report/winandloselist',                       		         'ReportController@winAndLoseList')->name('report/winandloselist');         //商户盈亏报表
Route::post('report/carriermonthstatlist',                       		 'ReportController@carrierMonthStatList')->name('report/carriermonthstatlist'); //商户盈亏报表
Route::post('player/playeroperatelog',                       		     'PlayerController@playerOperateLog')->name('player/playeroperatelog'); 

Route::post('report/agentstatdaylist',                                   'ReportController@agentStatdayList')->name('report/agentstatdaylist');              //个人日报表
Route::post('report/agenttotalstatdaylist',                              'ReportController@agentTotalStatdayList')->name('report/agenttotalstatdaylist');    //用户总报表


//树型结构
Route::post('player/agents',                                              'PlayerController@agents')->name('player/agents');  

//黑名单管理
Route::post('system/playeripblack',                                       'SystemController@playerIpblack')->name('system/playeripblack');
Route::post('system/playeripblackupdate',                                 'SystemController@playerIpblackUpdate')->name('system/playeripblackupdate');

//操作日志
Route::post('log/list',                       			                  'LogController@list')->name('log/list');
Route::post('carrierremainquota/list',                       			  'LogController@carrierRemainquotaList')->name('carrierremainquota/list');
Route::post('log/playerlevelupdatelist',                                  'LogController@playerLevelUpdateList')->name('log/playerlevelupdatelist');

//首页相关接口
Route::post('home/toollist',                       			               'HomeController@toolList')->name('home/toollist');
Route::post('home/lotterywebsitelist',                       			   'HomeController@lotterywebsiteList')->name('home/lotterywebsitelist');
Route::post('home/statreport',                       			           'HomeController@statReport')->name('home/statreport');

//用户可选钱包和数字币地址
Route::post('allthirdwallet',                                              'FinanceController@allThirdWallet')->name('carrier/allthirdwallet');
Route::post('agentdepositpaychannel',                                      'FinanceController@agentDepositPaychannel')->name('carrier/agentdepositpaychannel');

//一键修改会员等级赔率
Route::post('system/changelottrewater',                                    'SystemController@changeLottRewater')->name('system/changelottrewater');

//所有语言
Route::post('alllanguages',                                                'SystemController@allLanguages')->name('system/alllanguages');
Route::post('allsites',                                                    'SystemController@allSites')->name('system/allsites');

//横版菜单列表
Route::post('horizontalmenuslist',                                         'SystemController@horizontalMenusList')->name('carrier/horizontalmenuslist');
Route::post('changehorizontalmenusstatus/{id}',                            'SystemController@changeHorizontalMenusStatus')->name('carrier/changehorizontalmenusstatus');
Route::post('updatehorizontalmenus/{id?}',                                 'SystemController@updateHorizontalMenus')->name('carrier/updatehorizontalmenus');
Route::post('horizontalmenutype',                                          'SystemController@horizontalMenuType')->name('carrier/horizontalmenutype');

//返佣额度
Route::post('guaranteedlist',                                              'SystemController@guaranteedList')->name('carrier/guaranteedlist');
Route::post('guaranteedadd/{id?}',                                          'SystemController@guaranteedAdd')->name('carrier/guaranteedadd');
Route::post('guaranteeddel/{id}',                                          'SystemController@guaranteedDel')->name('carrier/guaranteeddel');

Route::post('allprefix',                                                    'SystemController@prefixList')->name('carrier/allprefix');
Route::post('allprefixsetting',                                             'SystemController@allPrefixSetting')->name('carrier/allprefixsetting');

//充提统计
Route::post('rechargwithdrawstat',                                           'SystemController@rechargWithdrawStat')->name('carrier/rechargwithdrawstat');

//检测上级的分红，充值，提款，自已周期内的充值与提款，总共的充值与提款。
Route::post('safedetect',                                                    'SystemController@safeDetect')->name('carrier/safedetect');

//库存列表。
Route::post('stocklist',                                                    'SystemController@stockList')->name('carrier/stocklist');

//体验券转化
Route::post('voucherconvertlist',                                           'SystemController@voucherConvertList')->name('carrier/voucherconvertlist');

//人头关卡设置列表
Route::post('capitationfeelevelslist',                                       'SystemController@capitationFeeLevelsList')->name('carrier/capitationfeelevelslist');

//人头关卡列表
Route::post('capitationfeelevelsadd/{id?}',                                   'SystemController@capitationFeeLevelsAdd')->name('carrier/capitationfeelevelsadd');

//删除人头关卡
Route::post('capitationfeelevelsdel/{id?}',                                   'SystemController@capitationFeeLevelsDel')->name('carrier/capitationfeelevelsdel');


//首页弹窗
Route::post('poplist',                                                         'SystemController@popList')->name('carrier/poplist');
Route::post('popsave/{id?}',                                                   'SystemController@popSave')->name('carrier/popsave');
Route::post('popchangestatus/{id}',                                            'SystemController@popChangeStatus')->name('carrier/popchangestatus');
Route::post('popdelete/{id}',                                                  'SystemController@popDelete')->name('carrier/popdelete');
Route::post('activitiespopimglist',                                            'ImgController@activitiesPopImgList')->name('carrier/activitiespopimglist');
//流水前十查询
Route::post('waterquery',                                                       'SystemController@waterQuery')->name('carrier/waterquery');

//游戏监控
Route::post('gamemonitor/{id}',                                                 'SystemController@gameMonitor')->name('carrier/gamemonitor');

//业绩清空
Route::post('clearperformance/{id}',                                            'SystemController@clearPerformance')->name('carrier/clearperformance');

//查询本代理下本日取款的投注情况
Route::post('sameagentbetflow/{id}',                                            'PlayerController@sameAgentBetflow')->name('carrier/sameagentbetflow');

//设置钱包姓名
Route::post('thirdwalletsetname/{id}',                                           'SystemController@thirdWalletSetname')->name('carrier/thirdwalletsetname');

//改变挂起状态
Route::post('withdrawchangesuspend/{id}',                                        'SystemController@withdrawChangeSuspend')->name('carrier/withdrawchangesuspend');

//域名列表
Route::post('domainlist',                                                         'SystemController@domainlist')->name('carrier/domainlist');
Route::post('domainadd',                                                          'SystemController@domainAdd')->name('carrier/domainadd');
Route::post('domaindel/{id}',                                                      'SystemController@domainDel')->name('carrier/domaindel');
Route::post('alldomain',                                                          'SystemController@allDomain')->name('carrier/alldomain');

//拉黑银行卡
Route::post('batchbankcardbacklist',                                              'SystemController@batchBankcardBackList')->name('system/batchbankcardbacklist');
//查看本期分红
Route::post('showplayerearnings/{id}',                                             'SystemController@showPlayerearnings')->name('system/showplayerearnings');

//补数据
Route::post('changestatusupplementary/{id}',                                       'PlayerController@changeStatuSupplementary')->name('carrier/changestatusupplementary');

//用户查询
Route::post('regresslist',                                                         'PlayerController@regressList')->name('carrier/regresslist');

//发放回归礼金
Route::post('sendregress',                                                         'PlayerController@sendRegress')->name('carrier/sendregress');

//捕拉数据
Route::post('againgetbetflow',                                                      'SystemController@againGetBetflow')->name('carrier/againgetbetflow');

//生成投注单
Route::post('createbetflow/{id}',                                                    'SystemController@createBetflow')->name('carrier/createbetflow');

//批量发放彩金
Route::post('batchsendgift',                                                          'SystemController@batchSendGift')->name('carrier/batchsendgift');

//批量设置分红与保底
Route::post('batchsetwage',                                                          'SystemController@batchSetWage')->name('carrier/batchsetwage');

//首存充提
Route::post('firstdepositwithdrawal/{id}',                                            'SystemController@firstDepositWithdrawal')->name('carrier/firstdepositwithdrawal');

//建立帐号关联
Route::post('createaccountassociate',                                                 'SystemController@createAccountAssociate')->name('carrier/createaccountassociate');

//改变自动发发分红状态
Route::post('autodividenddistribution/{id}',                                           'SystemController@autoDividendDistribution')->name('carrier/autodividenddistribution');

//P图专项
Route::post('fraudrecharge/{id}',                                                       'SystemController@fraudRecharge')->name('system/fraudRecharge');

//公告列表
Route::post('noticelist',                                                                'SystemController@noticeList')->name('carrier/noticelist');

//新增或编辑公告
Route::post('noticeedit/{id?}',                                                            'SystemController@noticeEdit')->name('carrier/noticeedit');

//删除公告
Route::post('noticedel/{id}',                                                             'SystemController@noticeDel')->name('carrier/noticedel');

Route::post('allcurrencys',                                                               'SystemController@allCurrencys')->name('carrier/allcurrencys'); 

Route::post('changewthdrawmobilestatus/{id}',                                              'SystemController@changeWthdrawMobileStatus')->name('carrier/changewthdrawmobilestatus'); 

//获取设置
Route::post('currencysettinglist',                                                         'SystemController@currencySettingList')->name('carrier/currencysettinglist'); 

Route::post('currencysettingsave',                                                         'SystemController@currencySettingSave')->name('carrier/currencysettingsave'); 

//获取所有游戏线路
Route::post('allgameline',                                                                 'SystemController@allGameline')->name('carrier/allgameline'); 

});
