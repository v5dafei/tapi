
<?php

use Illuminate\Http\Request;


Route::group(['namespace' => "Web"], function () {

Route::post('register',                       						'AuthController@register')->name('player/register');

Route::post('login',                       							'AuthController@login')->name('player/login');
Route::post('forumlogin',                       					'AuthController@forumLogin')->name('player/forumlogin');  //论坛登录
Route::post('logout',                       			            'AuthController@logout')->name('player/logout');
Route::post('tokenlogin',                                           'AuthController@tokenlogin')->name('player/tokenlogin');
Route::post('retrievepassordforphone',                              'AuthController@retrievePassordForPhone')->name('player/retrievepassordforphone');  //找回密码
Route::post('sendsms',                                              'AuthController@sendSms')->name('player/sendsms');



//资源
Route::post('init',                                                     'SystemController@init')->name('system/init');
Route::post('newinit',                                                  'SystemController@newInit')->name('system/newinit');

//游戏相关
Route::post('getBalance',                       					    'GameController@getBalance')->name('game/getBalance');
//Route::post('transferIn',                       					    'GameController@transferIn')->name('game/transferIn');
Route::post('fasttransfer',                       					    'GameController@fastTransfer')->name('game/fastTransfer');
//Route::post('transferTo',                       					    'GameController@transferTo')->name('game/transferTo');
Route::post('transfer',                       					        'GameController@transfer')->name('game/transfer');
Route::post('kick',                       					            'GameController@kick')->name('game/kick');
Route::post('joinGame',                       					        'GameController@joinGame')->name('game/joinGame');
Route::post('joinMobileGame',                       					'GameController@joinMobileGame')->name('game/joinMobileGame');
Route::post('joinGameLotteryLobby',                       			    'GameController@joinGameLotteryLobby')->name('game/joinGamelotterylobby');
Route::post('joinMobileGameLotteryLobby',                       	    'GameController@joinMobileGameLotteryLobby')->name('game/joinMobileGamelotterylobby');
Route::post('electronic/recommandlist',                                 'GameController@recomandElectronicList')->name('game/recommandelectroniclist');
Route::post('electronic/categorylist',                                  'GameController@electronicCategoryList')->name('game/electroniccategorylist');
Route::post('electronic/list',                                          'GameController@electronicList')->name('game/electroniclist');
Route::post('horizontalelectronic/list',                                'GameController@horizontalElectronicList')->name('game/horizontalelectroniclist');
Route::post('live/list',                                                'GameController@liveList')->name('game/livelist');
Route::post('sport/list',                                               'GameController@sportList')->name('game/sportlist');
Route::post('card/list',                                                'GameController@cardList')->name('game/cardlist');
Route::post('card/sublist',                                             'GameController@cardSubList')->name('game/cardsublist');
Route::post('fish/list',                                                'GameController@fishList')->name('game/fishlist');
Route::post('esport/list',                                              'GameController@esportList')->name('game/esport');
Route::post('lottery/list',                                             'GameController@lotteryList')->name('game/lotterylist');
Route::post('lottery/getlotterycode',                                   'GameController@getLotteryCode')->name('game/getlotterycode');             //获取时时彩的开奖号码
Route::post('lottery/recentresults',                                    'GameController@getLatestresults')->name('game/getLatestresults');         //获取AE5分彩的最近一期
Route::post('card/recommandlist',                                       'GameController@recomandCardList')->name('game/recommandcardlist');
Route::post('hotgamelist',                                              'GameController@hotGameList')->name('game/hotgamelist');                    //获取热门游戏
Route::post('fish/search',                                              'GameController@fishSearch')->name('game/fishsearch');
Route::post('card/search',                                              'GameController@cardSearch')->name('game/cardsearch');  
Route::post('fish/categorylist',                                        'GameController@fishCategorylist')->name('game/fishcategorylist');   //捕鱼分灰

//退出游戏
Route::post('exitgame',                                                 'GameController@exitGame')->name('player/exitgame');

//生成内置的游戏列表
Route::post('solidifyjson',                                             'GameController@solidifyjson')->name('game/solidifyjson');  

//获取商户主域名
Route::post('payouttop',                                                'SystemController@payouttop')->name('system/payouttop');

Route::post('plats/list',                                               'GameController@platsList')->name('game/platslist');                           //获取平台列表
Route::post('adv/list',                                                 'AdvController@advList')->name('game/advlist');                                //获取广告图片
Route::post('player/vip',                                               'PlayerController@getVip')->name('player/vip');                                //获取VIP等级相关信息
Route::post('player/balance',                                           'PlayerController@getBalance')->name('player/balance');                        //获取用户余额
Route::post('player/gethavemessage',                                    'PlayerController@getHaveMessage')->name('player/gethavemessage');             //获取是否有未读站内信
Route::post('player/info',                                              'PlayerController@info')->name('player/info');                                 //获取玩家基本信息
Route::post('player/updateinfo',                                        'PlayerController@updateinfo')->name('player/updateinfo');                     //更新玩家基本信息
Route::post('player/changepassword',                                    'PlayerController@playerChangePassword')->name('player/changepassword');       //修改密码
Route::post('player/changepaypassword',                                 'PlayerController@playerChangePayPassword')->name('player/changepaypassword'); //修改支付密码
Route::post('player/checkpaypassword',                                  'PlayerController@playerCheckPayPassword')->name('player/checkpaypassword');
Route::post('player/updatemoblie',                                      'PlayerController@updateMoblie')->name('player/updatemoblie');                  //修改手机号码

Route::post('player/bankcardlist',                                      'PlayerController@bankcardList')->name('player/bankcardlist');                 //获取银行卡列表
Route::post('player/rechargegroupchannellist',                          'PlayerController@rechargeGroupChannellist')->name('player/rechargegroupchannellist');   //获取充值分组渠道列表
Route::post('system/banktypelist',                                      'SystemController@banktypeList')->name('system/banktypelist');                 //所有银行卡类型
Route::post('player/bankcardadd/{id?}',                                 'PlayerController@bankcardAdd')->name('player/bankcardadd');                   //添加银行卡
Route::post('player/bankcarddel/{id}',                                  'PlayerController@bankcardDel')->name('player/bankcarddel');                   //删除银行卡

Route::post('player/alipaylist',                                        'PlayerController@alipayList')->name('player/alipaylist');                 //获取支付宝列表
Route::post('player/alipayadd/{id?}',                                   'PlayerController@alipayAdd')->name('player/alipayadd');                   //添加支付宝
Route::post('player/alipaydel/{id}',                                    'PlayerController@alipayDel')->name('player/alipaydel');                   //删除支付宝


Route::post('player/messagelist',                                       'PlayerController@messageList')->name('player/messagelist');                   //获取站内信
Route::post('player/unreadmessagenumber',                               'PlayerController@unReadMessageNumber')->name('player/unreadmessagenumber');  //未读消息数量
Route::post('player/messagechangestatus/{id}',                          'PlayerController@messageChangeStatus')->name('player/messagechangestatus');   //变更消息状态
Route::post('player/messagedelete',                                     'PlayerController@messageDelete')->name('player/messagedelete');                   //获取站内信
Route::post('player/platlist',                                          'PlayerController@platList')->name('player/platlist');                         //获取游戏平台和余额啊
Route::post('player/refreshplat/{platcode}',                            'PlayerController@refreshPlat')->name('player/refreshplat');                   //获取游戏平台和余额啊
Route::post('player/betflowlist',                                       'PlayerController@betflowList')->name('player/betflowlist');                   //获取投注记录
Route::post('player/betflowliststat',                                   'PlayerController@betflowListStat')->name('player/betflowliststat'); 
Route::post('player/existbackwater',                                    'PlayerController@existBackwater')->name('player/existbackwater');             //是否存在洗码
Route::post('player/level',                                             'PlayerController@level')->name('player/level');                               //获取玩家基本信息
Route::post('player/authstatus',                                        'PlayerController@authStatus')->name('player/authstatus');                     //获取玩家的认证状态
Route::post('player/getstyle',                                          'PlayerController@getStyle')->name('player/getstyle');                         //获取玩家的移动前台显示样式0=素样式，1=荤样式
Route::post('player/setstyle',                                          'PlayerController@setStyle')->name('player/setstyle');                         //设置玩家的移动前台样式
Route::post('player/promotestat',                                       'PlayerController@promoteStat')->name('player/promotestat');
Route::post('player/digitaladd/{id?}',                                  'PlayerController@digitalAdd')->name('player/digitaladd');
Route::post('player/digitaldelete/{id?}',                               'PlayerController@digitalDelete')->name('player/digitaldelete');
Route::post('player/digitallist',                                       'PlayerController@digitalList')->name('player/digitallist');
Route::post('player/digitalextra',                                      'PlayerController@digitalExtra')->name('player/digitalextra');
Route::post('player/digitaldeposit',                                    'PayController@digitalDeposit')->name('player/digitaldeposit');
Route::post('player/digitaltype',                                       'PlayerController@digitaltype')->name('player/digitaltype');
Route::post('player/myinvitelist',                                      'PlayerController@myInviteList')->name('player/myinvitelist');
Route::post('player/myinvitebetstat',                                   'PlayerController@myInviteBetStat')->name('player/myinvitebetstat');
Route::post('player/vipsalary',                                         'PlayerController@vipSalary')->name('player/vipsalary');                
Route::post('player/getbetflowstat',                                    'PlayerController@getBetFlowStat')->name('player/getbetflowstat');

//提款方式列表
Route::post('player/withdrawmethodlist',                                 'PlayerController@withdrawMethodList')->name('player/withdrawmethodlist');                 //获取银行卡列表

//获取免提直充记录
Route::post('player/nowithdrawlist',                                    'PlayerController@noWithdrawList')->name('player/nowithdrawlist');

//用户今日返水分类别
Route::post('fileupload/{directory}',                       		    'PlayerController@fileUpload')->name('player/fileupload');                     //头像上传

//活动
Route::post('activitycategory',                                         'ActivityController@activityCategory')->name('web/activitycategory');          // 申请活动
Route::post('activityslist',                                            'ActivityController@activitiesList')->name('web/activitiesList');              // 活动列表
Route::post('activitysdesc/{id}',                                       'ActivityController@activitiesDesc')->name('web/activitiesDesc');   
Route::post('activityApply',                                            'ActivityController@activityApply')->name('web/activityApply');                // 申请活动
Route::post('activitySignIn',                                           'ActivityController@activitySignIn')->name('web/activitysignin');                // 申请活动
Route::post('activityGetSignInList',                                    'ActivityController@activityGetSignInList')->name('web/activitygetsigninlist');    // 连续签到列表
Route::post('activityGetSignInInfo',                                    'ActivityController@activityGetSignInInfo')->name('web/activitygetsignininfo');

Route::post('receivegift/{id}',                                         'ActivityController@receiveGift')->name('player/receivegift');                         // 福利中心领取彩金
Route::post('receivegiftlist',                                          'ActivityController@receivegiftList')->name('player/receivegiftlist');                 // 福利中心列表
Route::post('receivegiftstat',                                          'ActivityController@receivegiftStat')->name('player/receivegiftstat');

//首页弹窗列表
Route::post('activity/poplist',                                         'ActivityController@popList')->name('activity/poplist');

//获取可申请的活动列表
Route::post('enableapplyactivitylist',                                   'ActivityController@enableApplyActivityList')->name('game/enableapplyactivitylist');

//充提
Route::post('deposit',                                                  'PayController@deposit')->name('player/deposit');                               //充值
Route::post('player/getdepositamount',                                  'PayController@getDepositAmount')->name('player/getdepositamount');             //获取前台充值金额
Route::post('player/depositpaylist',                                    'PlayerController@depositPayList')->name('player/depositpaylist');              //获取充值记录
Route::post('player/withdrawlist',                                      'PlayerController@withdrawList')->name('player/withdrawlist');                  //获取提现记录
Route::post('player/transferlist',                                      'PlayerController@transferList')->name('player/transferList');                  //帐变记录
Route::post('player/withdrawapply',                                     'PlayerController@withdrawApply')->name('player/withdrawApply');                //提现申请
Route::post('player/alipaywithdrawapply',                               'PlayerController@alipayWithdrawApply')->name('player/alipaywithdrawapply');    //提现申请
Route::post('player/digitalwithdrawapply',                              'PlayerController@digitalWithdrawApply')->name('player/digitalwithdrawapply');  //提现申请

Route::post('player/transfertypelist',                                  'PlayerController@transferTypeList')->name('player/transfertypelist');          //帐变记录
Route::post('player/withdrawnotice',                                    'PlayerController@withdrawNotice')->name('player/withdrawnotice');                //提现申请
Route::post('player/withdrawlimit',                                     'PlayerController@withdrawLimit')->name('player/withdrawlimit');                //提现申请

//代理
Route::post('agent/createmember',                                       'PlayerController@createMember')->name('agent/createmember');
Route::post('agent/createregisterlink',                                 'PlayerController@createRegisterLink')->name('agent/createregisterlink');
Route::post('agent/getRegisterlinks/{id}',                              'PlayerController@getRegisterLinks')->name('agent/getRegisterLinks');
Route::post('agent/deleteregisterlinks',                                'PlayerController@deleteRegisterLinks')->name('agent/deleteregisterlinks');
Route::post('agent/playerinvitecodelist',                               'PlayerController@playerInvitecodeList')->name('agent/playerInvitecodeList');

Route::post('agent/teaminfo',                       		            'PlayerController@teamInfo')->name('agent/teamInfo');                            // 团队管理
Route::post('agent/setbonus',                       		            'PlayerController@setBonus')->name('agent/setBonus');                            // 团队管理-- 设置奖金组    
Route::post('agent/playerearnings',                                     'PlayerController@playerEarnings')->name('agent/playerEarnings');                // 代理分红    
Route::post('agent/playerdividend',                                     'PlayerController@playerDividend')->name('agent/playerDividend');                // 发放分红    
Route::post('agent/winandloselist',                                     'PlayerController@winAndLoseList')->name('agent/winandloselist');                // 团队盈亏   
Route::post('agent/earningsdatelist',                                   'PlayerController@earningsDateList')->name('agent/earningsdatelist');            // 团队盈亏 
Route::post('agent/getsetoptions',                                      'PlayerController@getSetOptions')->name('agent/getsetoptions');                  //获取开户的设置选项
Route::post('agent/getplayersetting',                                   'PlayerController@getPlayerSetting')->name('agent/getplayersetting');
Route::post('agent/getoptions',                                         'PlayerController@getoptions')->name('agent/getoptions'); 
Route::post('agent/getinviteoptions',                                   'PlayerController@getInviteOptions')->name('agent/getinviteoptions');
Route::post('agent/playerinvitecodesave/{id}',                          'PlayerController@playerinvitecodesave')->name('agent/playerinvitecodesave');

//业绩查询
Route::post('agent/achievement',                                         'PlayerController@achievement')->name('agent/achievement'); 
//我的直属
Route::post('agent/directlyunderinfo',                                   'PlayerController@directlyunderInfo')->name('agent/directlyunderinfo'); 

//下级会员财务报表
Route::post('report/subordinatefinancestat',                             'PlayerController@subordinateFinanceStat')->name('player/subordinatefinancestat');
Route::post('report/subordinatebetflowstat',                             'PlayerController@subordinateBetflowStat')->name('player/subordinatebetflowstat');

//轮盘信息
Route::post('activitiesluckdrawinfo',                                   'PlayerController@activitiesLuckDrawInfo')->name('player/activitiesluckdrawinfo');                 // 幸运轮盘
Route::post('luckdrawextract',                                          'PlayerController@luckDrawExtract')->name('player/luckdrawextract');                               // 抽奖
Route::post('existluckdrawextract',                                     'PlayerController@existLuckDrawExtract')->name('player/existluckdrawextract');                     // 是否存在轮盘活动

//联系我们
Route::post('system/marqueenotice',                                     'SystemController@marqueeNotice')->name('system/marqueenotice');

//注册协议
Route::post('system/registerprotocol',                                  'SystemController@registerProtocol')->name('system/registerprotocol');

//问题分类
Route::post('system/questiontype',                                     'SystemController@questionType')->name('system/questiontype');
Route::post('system/questionlist',                                     'SystemController@questionList')->name('system/questionlist');
Route::post('system/allquestionlist',                                  'SystemController@allquestionList')->name('system/allquestionlist');

//vip详情
Route::post('system/vipdesc',                                           'SystemController@vipDesc')->name('system/vipdesc');

//提交返馈
Route::post('player/feedbacksave',                                      'PlayerController@feedbackSave')->name('player/feedbacksave');
Route::post('system/captcha',                                            'SystemController@captcha')->name('system/captcha');
Route::get('system/captcha',                                             'SystemController@captcha')->name('system/captcha');


Route::post('player/setdeductionsmethod',                                'PlayerController@setdeDuctionsMethod')->name('player/setdeductionsmethod');

//游戏添加收藏
Route::post('player/gamecollectstatuschange/{id}',                        'PlayerController@gameCollectStatusChange')->name('player/gamecollectstatuschange');
Route::post('player/gamecollectlist',                                     'PlayerController@gameCollectList')->name('player/gamecollectlist');

//菜单
Route::post('system/menus',                                             'SystemController@menus')->name('system/menus');                 //所有银行卡类型

Route::post('system/newmenus',                                           'SystemController@newMenus')->name('system/newmenus');                 //新接口
//彩票热门游戏
Route::post('lottery/hotlist',                                          'LotteryController@hotList')->name('lottery/hotlist');                 //所有银行卡类型

//三方钱包类型
Route::post('thirdwallethelp',                                           'SystemController@thirdWalletHelp')->name('system/thirdwallethelp');  //所有银行卡类型
Route::post('allowwithdrawmethod',                                       'SystemController@allowWithdrawMethod')->name('system/allowwithdrawmethod'); 

//钱包与保险箱互转
Route::post('mutualtransfer',                                            'PlayerController@mutualTransfer')->name('system/mutualtransfer'); 

//所有提现通道
Route::post('withdrawalchannel',                                         'PlayerController@withdrawalChannel')->name('player/withdrawalchannel'); 

Route::post('gitappdownurl',                                             'SystemController@gitAppDownUrl')->name('system/gitappdownurl'); 

//推广赚钱
Route::post('promoteandmakemoney',                                      'PlayerController@promoteAndMakeMoney')->name('player/promoteandmakemoney'); 
//领取佣金
Route::post('getcommission',                                            'PlayerController@getCommission')->name('player/getcommission');
//业绩查询
Route::post('performanceinquiry',                                       'PlayerController@performanceinQuiry')->name('player/performanceinquiry'); 
//我的直属
Route::post('mydirectlyunder',                                          'PlayerController@myDirectlyunder')->name('player/mydirectlyunder'); 

//我的团队
Route::post('myteam',                                                    'PlayerController@myTeam')->name('player/myteam'); 

//我的直属
Route::post('newmydirectlyunder',                                         'PlayerController@newMyDirectlyunder')->name('player/newmydirectlyunder'); 


//设置保底
Route::post('setguaranteed',                                            'PlayerController@setGuaranteed')->name('player/setguaranteed'); 

//设置分红
Route::post('setearning',                                               'PlayerController@setEarning')->name('player/setearning');

//代理合营
Route::post('agencyjointventure',                                        'PlayerController@agencyJoinTventure')->name('player/agencyjointventure'); 

//佣金领取记录
Route::post('commissionlog',                                            'PlayerController@commissionLog')->name('player/commissionlog');
//个人中心拓展
Route::post('personexpand',                                            'PlayerController@personExpand')->name('player/personexpand');

//排行榜
Route::post('ranklist',                                                'PlayerController@rankList')->name('player/ranklist');

//排行榜领取记录
Route::post('rankhistorylist',                                          'PlayerController@rankHistoryList')->name('player/rankhistorylist');

//业绩简报
Route::post('performancebriefing',                                      'PlayerController@performanceBriefing')->name('player/performancebriefing');

//能绑定的三方钱包
Route::post('enablebindthirdwalletlist',                                'PlayerController@enableBindThirdwalletList')->name('player/enablebindthirdwalletlist');

//佣金设置列表
Route::post('guaranteedlist',                                            'SystemController@guaranteedList')->name('player/guaranteedlist');

//兑换券兑换
Route::post('voucherexchange',                                           'SystemController@voucherExchange')->name('player/voucherexchange');

//代理体验券列表
Route::post('agentvoucherlist',                                           'SystemController@agentVoucherList')->name('player/agentvoucherlist');

//百胜棋牌
Route::post('card/basonlist',                                             'GameController@cardBasonList')->name('game/cardbasonlist');

//百胜捕鱼
Route::post('fish/basonlist',                                             'GameController@fishBasonList')->name('game/fishbasonlist');

//生成游戏初始化界面
Route::post('customizehall',                                              'GameController@customizeHall')->name('game/customizehall');

//闯关分类列表
Route::post('breakthroughlist',                                            'TaskController@breakThroughList')->name('task/breakthroughlist');

//领取任务彩金
Route::post('receivebreakthroughgift',                                     'TaskController@receiveBreakThroughGift')->name('task/receivebreakthroughgift');

//新业绩查询接口
Route::post('newperformanceinquiry',                                        'PlayerController@newPerformanceinQuiry')->name('player/newperformanceinquiry');

//新业绩查询接口
Route::post('newperformanceindesc',                                         'PlayerController@newPerformanceinDesc')->name('player/newperformanceindesc');

//新业绩查询接口1
Route::post('newperformanceindesc1',                                         'PlayerController@newPerformanceinDesc1')->name('player/newperformanceindesc1');

//佣金设置列表
Route::post('newguaranteedlist',                                            'SystemController@newGuaranteedList')->name('system/newguaranteedlist');

//佣金设置列表
Route::post('newguaranteedlist1',                                            'SystemController@newGuaranteedList1')->name('system/newguaranteedlist1');

//站内转帐
Route::post('insidetransfer',                                               'PlayerController@insideTransfer')->name('player/insidetransfer');

//人头费闯关
Route::post('capitationfeelevelslist',                                      'PlayerController@capitationFeeLevelsList')->name('player/capitationfeelevelslist');

//人头费闯关
Route::post('receivecapitationfeelevels/{id}',                               'PlayerController@receiveCapitationFeeLevels')->name('player/receivecapitationfeelevels');

//首页
Route::post('index',                                                            'SystemController@index')->name('system/index');

//场馆费用搜索
Route::post('venuefeeslist',                                                     'PlayerController@venueFeesList')->name('player/venuefeeslist');

//直属优惠报表
Route::post('directfeeslist',                                                     'PlayerController@directFeesList')->name('player/directfeeslist');

//直属优惠类型
Route::post('directfeestypelist',                                                 'SystemController@directTypeFeesList')->name('system/directfeestypelist');

//我的数据
Route::post('mydata',                                                             'PlayerController@mydata')->name('system/mydata');

//我的总览
Route::post('overview',                                                            'PlayerController@overview')->name('system/overview');

//全部数据
Route::post('alldata',                                                             'PlayerController@alldata')->name('system/alldata');

//我的业绩
Route::post('myperformance',                                                        'PlayerController@myPerformance')->name('system/alldata');

//我的佣金
Route::post('mycommission',                                                        'PlayerController@myCommission')->name('system/mycommission');

//直属数据
Route::post('underdata',                                                           'PlayerController@underData')->name('system/underdata');

//直属投注
Route::post('underbetflows',                                                        'PlayerController@underBetflows')->name('system/underbetflows');

//直属投注详情
Route::post('underbetflowsdesc/{id}',                                                'PlayerController@underBetflowsDesc')->name('system/underbetflowsdesc');

//直属详情
Route::post('underdesc/{id}',                                                        'PlayerController@underDesc')->name('system/underdesc');

//直属领取
Route::post('underreceive',                                                         'PlayerController@underReceive')->name('system/underreceive');

//错误日志
Route::post('errorlog',                                                             'SystemController@errorLog')->name('system/errorlog');

//体验券列表
Route::post('voucherlist',                                                           'PlayerController@voucherList')->name('system/voucherlist');

//动态客服
Route::post('customerservice',                                                       'SystemController@customerService')->name('system/customerservice');

//动态检测是否分红转入钱包
Route::post('popdetection',                                                          'SystemController@popDetection')->name('system/popdetection');

//进入游戏检测
Route::post('popcommission',                                                          'SystemController@popCommission')->name('system/popcommission');

//跳转检测
Route::post('jumpdetect',                                                             'SystemController@jumpDetect')->name('system/jumpdetect');

//直属财务
Route::post('underfinance',                                                         'PlayerController@underFinance')->name('system/underfinance');

Route::post('player/selectrebate',                                      'PlayerController@selectRebate')->name('player/selectrebate');                //用户查看自已可领的返水
Route::post('player/rebatehistory',                                     'PlayerController@rebateHistory')->name('player/rebatehistory');              //用户返水历史
Route::post('player/rebateproportion',                                  'PlayerController@rebateProportion')->name('player/rebateproportion');        //返水比例
Route::post('player/getrebate',                                         'PlayerController@getRebate')->name('player/getrebate');

//返佣比例
Route::post('player/rebateratio',                                        'PlayerController@rebateRatio')->name('player/rebateratio');
Route::post('player/getrealrebate',                                      'PlayerController@getRealRebate')->name('player/getrealrebate');
Route::post('player/getinviteplayer',                                    'PlayerController@getInvitePlayer')->name('player/getinviteplayer');

//返佣
Route::post('player/allvip',                                             'PlayerController@allVip')->name('player/allvip');

//人头费列表
Route::post('player/capitationfeelist',                                  'PlayerController@capitationFeeList')->name('player/capitationfeelist');


//公告列表
Route::post('noticelist',                                                'SystemController@noticeList')->name('system/noticelist');

});



