<?php

use Illuminate\Http\Request;

Route::get('/', function (Request $request) {
    return redirect('admin/login');
});

Route::group(['namespace' => "Admin"], function () {
Route::post('login',                       							'AuthController@login')->name('admin/login');
Route::post('sendcode',                       					    'AuthController@sendcode')->name('admin/sendcode');
Route::post('updatepassword',                       			    'AuthController@updatePassword')->name('admin/updatepassword');
Route::post('logout',                       			            'AuthController@logout')->name('admin/logout');

//游戏回调验证
Route::get('game/icgcallback',                       			    'GameController@icgCallback')->name('game/icgcallback');
Route::post('game/igcallback',                       			    'GameController@igCallback')->name('game/igcallback');
Route::get('game/tfcallback',                       			    'GameController@tfCallback')->name('game/tfcallback');
Route::post('game/tfcallback',                       			    'GameController@tfCallback')->name('game/tfcallback');

Route::post('game/changeline',                       			    'GameController@changeLine')->name('game/changeline');

//同步游戏
Route::post('game/syncGame',                       			         'GameController@syncGame')->name('game/syncGame');

//同步视频
Route::post('game/syncvideo',                                        'VideoController@syncVideo')->name('video/syncVideo');

//支付回调
Route::get('pay/paycallback/{paycode}',                       	    'PayController@callback')->name('pay/paycallback');
Route::post('pay/paycallback/{paycode}',                       	    'PayController@callback')->name('pay/paycallback');
Route::post('pay/behalfcallback/{paycode}',                       	'PayController@behalfCallback')->name('pay/behalfcallback');
Route::get('pay/behalfcallback/{paycode}',                       	'PayController@behalfCallback')->name('pay/behalfcallback');
Route::post('pay/digitalcallback',                                  'PayController@digitalCallback')->name('pay/digitalcallback');
Route::get('pay/digitalcallback',                                   'PayController@digitalCallback')->name('pay/digitalcallback');

//代付三方gopay反查接口
Route::get('pay/reversecheck',                                      'PayController@reverseCheck')->name('pay/reversecheck');
Route::post('pay/reversecheck',                                      'PayController@reverseCheck')->name('pay/reversecheck');

//短信回调
Route::post('sms/smspassagecallback/{type}',                        'SmsController@smspassageCallback')->name('sms/smspassagecallback');
Route::get('sms/smspassagecallback/{type}',                         'SmsController@smspassageCallback')->name('sms/smspassagecallback');

//运营商管理
Route::post('carrieradd/{id?}',                       			    'CarrierController@carrierAdd')->name('admin/createcarrier');
Route::post('carrierlist',                       			        'CarrierController@carrierList')->name('admin/carrierlist');
Route::post('carrierchangestatus/{id}',                       	    'CarrierController@carrierChangeStatus')->name('admin/carrierchangestatus');
Route::post('carrieruserlist',                       	            'CarrierController@carrierUserList')->name('admin/carrieruserlist');
Route::post('carrieruseradd',                       	            'CarrierController@carrierUserAdd')->name('admin/carrieruseradd');
Route::post('carrierUserUpdatePassword/{id?}',                      'CarrierController@carrierUserUpdatePassword')->name('admin/carrierUserUpdatePassword');
Route::post('carrieruserchangestatus/{id}',                       	'CarrierController@carrierUserChangeStatus')->name('admin/carrieruserchangestatus');
Route::post('carrieruserclosegoogle/{id}',                          'CarrierController@carrierUserCloseGoogle')->name('admin/carrieruserclosegoogle');
Route::post('carrierserviceteamlist/{id}',                       	'CarrierController@carrierServiceTeamList')->name('admin/carrierserviceteamlist');
Route::post('carriergameplats/{id}',                                'CarrierController@carrierGameplats')->name('admin/carriergameplats');
Route::post('carriergameplatssave/{id}',                            'CarrierController@carrierGameplatsSave')->name('admin/carriergameplatssave');
Route::post('carrierpayfactorys/{id}',                              'CarrierController@carrierPayFactorys')->name('admin/carrierpayfactorys');
Route::post('carrierpayfactoryssave/{id}',                          'CarrierController@carrierPayFactorysSave')->name('admin/carrierpayfactoryssave');
Route::post('carrierremainquotaadd/{id}',                           'CarrierController@carrierRemainQuotaAdd')->name('admin/carrieripslistadd');
Route::post('carrieripslist/{id}',                                  'CarrierController@carrierIpsList')->name('admin/carrieripslist');
Route::post('carrieripsadd/{id}',                                   'CarrierController@carrierIpsAdd')->name('admin/carrieripsadd');
Route::post('carrieripsdel/{id}',                                   'CarrierController@carrierIpsDel')->name('admin/carrieripsdel');
Route::post('carriercasinoadd/{id}',                                'CarrierController@carrierCasinoAdd')->name('admin/carriercasinoadd');
Route::post('carrierselfopointupdate/{id}',                         'CarrierController@selfpointupdate')->name('admin/carrierselfopointupdate');
Route::post('carriercasinopointupdate/{id}',                        'CarrierController@pointupdate')->name('admin/carriercasinopointupdate');
Route::post('carriercasinopointlist/{id}',                          'CarrierController@pointlist')->name('admin/carriercasinopointlist');
Route::post('templatelist',                                         'CarrierController@templateList')->name('admin/templatelist');
Route::post('carriersflushplayer/{id}',                             'CarrierController@carriersFlushPlayer')->name('admin/carriersflushplayer');

//游戏平台管理
Route::post('gameplatlist',                                         'GamePlatController@gamePlatList')->name('admin/gameplatlist');
Route::post('gameplatscalelist/{id}',                               'GamePlatController@gameplatScaleList')->name('admin/gameplatscalelist');
Route::post('updatescale/{id}',                                     'GamePlatController@updateScale')->name('admin/updatescale');
Route::post('gameplatchangestatus/{id}',                            'GamePlatController@gamePlatChangeStatus')->name('admin/gameplatchangestatus');
Route::post('gameplatdel/{id}',                                     'GamePlatController@gameplatDel')->name('admin/gameplatdel');
Route::post('gameplatadd/{id?}',                                    'GamePlatController@gameplatAdd')->name('admin/gameplatadd');

//游戏管理
Route::post('gameadd/{id?}',                                        'GameController@gameAdd')->name('admin/gameadd');
Route::post('gamelist',                                             'GameController@gameList')->name('admin/gamelist');
Route::post('gamechangestatus/{id}',                                'GameController@gameChangeStatus')->name('admin/gamechangestatus');
Route::post('gamecarriers/{id}',                                    'GameController@gameCarriers')->name('admin/gamecarriers');
Route::post('gamecarrierssave/{id}',                                'GameController@gameCarriersSave')->name('admin/gamecarrierssave');

//支付类型管理
Route::post('banklist',                                              'BankController@bankList')->name('admin/banklist');
Route::post('bankadd/{id?}',                                         'BankController@bankAdd')->name('admin/bankadd');
Route::post('bankdel/{id}',                                          'BankController@bankDel')->name('admin/bankdel');

//支付厂商管理
Route::post('payfactoryadd/{id?}',                                  'PayChannelController@payFactoryAdd')->name('admin/payfactoryadd');
Route::post('payfactorylist',                                       'PayChannelController@payFactorylist')->name('admin/payfactorylist');
Route::post('payfactorychangestatus/{id}',                          'PayChannelController@payFactorylistChangeStatus')->name('admin/payfactorychangestatus');
Route::post('payfactorybankcode/{id}',                              'PayChannelController@payFactoryBankcode')->name('admin/payfactorybankcode');
Route::post('payfactorybankcodesave/{id}',                          'PayChannelController@payFactoryBankcodeSave')->name('admin/payfactorybankcodesave');

//支付渠道管理
Route::post('paychannellist',                                       'PayChannelController@payChannelList')->name('admin/paychannellist');
Route::post('paychanneladd/{id?}',                                  'PayChannelController@payChannelAdd')->name('admin/paychanneladd');

//短信渠道管理
Route::post('smspassagelist',                                       'SmsController@smsPassageList')->name('admin/smspassagelist');
Route::post('smspassageadd/{id?}',                                  'SmsController@smsPassageAdd')->name('admin/smspassageadd');

//开发管理
Route::post('developmentlist',                                      'DevelopmentController@developmentList')->name('admin/developmentlist');

//配额日志列表
Route::post('remainquotalist',                                      'RemainQuotaController@remainQuotaList')->name('admin/remainquotalist');

//文件上传
Route::post('fileupload/{directory?}',                              'CommonController@fileUpload')->name('admin/fileupload');

//初始化
Route::post('init',                                                  'CommonController@init')->name('admin/init');


//菜单管理
Route::post('menuslist',                       		                'RabcController@menusList')->name('admin/menuslist');
Route::post('menusadd/{id?}',                       		        'RabcController@menusAdd')->name('admin/menusadd');
Route::post('menusdel/{id}',                       		            'RabcController@menusDel')->name('admin/menusdel');

//路由管理
Route::post('premissionlist',                       		        'RabcController@premissionList')->name('admin/premissionlist');
Route::post('premissiondel/{id}',                       		    'RabcController@premissionDel')->name('admin/premissiondel');
Route::post('premissionadd/{id?}',                       		    'RabcController@premissionAdd')->name('admin/premissionadd');

//消息发布管理
Route::post('carriernotice/allcarrier',                             'CarrierController@allCarrier')->name('carriernotice/allcarrier'); 

//系统设置
Route::post('paramelist',                                           'SystemController@parameList')->name('system/paramelist'); 
Route::post('parameedit/{id}',                                      'SystemController@parameEdit')->name('system/parameedit'); 

//所有的语言
Route::post('alllanguages',                                         'SystemController@allLanguages')->name('system/alllanguages'); 
Route::post('allcurrencys',                                         'SystemController@allCurrencys')->name('system/allcurrencys'); 

//多前端前辍
Route::post('prefixdomainlist/{id}',                                 'SystemController@prefixDomainList')->name('carrier/prefixdomainlist');
Route::post('prefixdomain/{id?}',                                    'SystemController@prefixDomain')->name('carrier/prefixdomain');
Route::post('prefixdomaindel/{id}',                                  'SystemController@prefixDomainDel')->name('carrier/prefixdomaindel');

});

