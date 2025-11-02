<?php
namespace App\Observers;

use App\Lib\Cache\CarrierCache;
use App\Models\Log\RemainQuota;
use App\Models\Lottery\SourceLottery;
use App\Models\Lottery\SscLottery;
use App\Models\Lottery\SscLotteryTime;
use App\Models\Lottery\SourceLotteryTime;
use App\Models\Lottery\SourceLotteryGroup;
use App\Models\CarrierServiceTeam;
use App\Models\CarrierPlayerGrade;
use App\Models\Carrier;
use App\Models\Player;
use App\Models\PlayerIpBlack;
use App\Models\PlayerLevel;
use App\Models\CarrierActivityLuckDraw;
use App\Jobs\CreatePreLotteryJob;
use App\Models\Def\Banks;

class CarrierObserver
{
    public function created(Carrier $carrier)
    {
        //插入问题
        $questionlist =[
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'支持哪些存款方式？','content'=>'集团为您提供多元化存款方式，有网银转账、银行卡转账、网银支付、银联扫码、数字人民币、虚拟币支付等（充值渠道会实时更改，请以当前显示存款方式为准）。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'什么是网银存款？','content'=>'网银存款指的是会员通过“网银转账”和“手机银行”两种方式，将存款金额转入我司指定账户的线下支付方式，存款前，请确认您的银行卡已开通网银功能。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'存款最低与最高限额是多少？','content'=>'目前单笔最低存款金额为100元，单笔最高限额根据充值方式不同限额也会不同、请您以选择的充值方式为准，如您有特殊要求可联系我司24小时在线客服团队进行咨询','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'支付宝充值提示交易存在风险怎么办？','content'=>'当前受到支付宝官方风控影响导致存款会有一定的限制。<br/>温馨提示：建议您更换存款金额重新获取支付尝试，如还是提示同一报错请您使用其他支付渠道进行存款。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'存款至旧的银行卡账户怎么办？','content'=>'为了保证您存款顺利到账，请您每次存款前务必登陆平台查看最新的银行卡账户，存至旧的银行卡账户将导致存款无法到账，损失将由您自行承担；我司充值通道和充值金额为实时更新，请勿保存或修改金额进行充值，以免不能正常到账。如有疑问可联系我司24小时在线客服团队进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'为什么我的存款成功但未到账？','content'=>'如成功转账后10分钟内未到账请您耐心等候片刻，如超过10分钟未到账请您第一时间联系我司24小时在线客服同时提供以下信息为您核实处理:<br/>1.会员账户<br/>2.存款方式<br/>3.支付人姓名<br/>4.存款成功截图','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'为什么没有我要的存款选项？','content'=>'为了提高您的存款体验，我司提供稳定高效的入款渠道，若需要其他存款渠道您可随时联系我司24小时在线客服并提供以下信息以便为您核实处理:<br/>1.会员账号；<br/>2.需要的存款方式。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'网银转账时提示无可用银行卡怎么办？','content'=>'当您使用网银转账存款渠道进行存款却提示无可用银行卡时，这可能由于当前使用该通道的用户较多导致，请您退出当前存款页面重新进入获取。如还有相同提示，请您第一时间联系我司7*24小时在线客服团队为您核实处理。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'金额输入完毕但【立即存款】的按钮是黑色？','content'=>'1.请您退出存款通道后刷新页面重新获取；<br/>2.请您更换其他金额或通道进行尝试，如还是无法进行存款，请您将该页面截图提供至7*24小时在线客服核实处理。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'存款附言是什么？为什么一定要填写？','content'=>'附言是系统识别对应款项的依据，转账时只有填写附言才能第一时间到账，所以请您重视附言的填写。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'微信为什么转不了钱？','content'=>'请您退出存款通道后刷新页面或者更换其他通道再次进行尝试，微信充值请在获取收款方后2分钟内充值，若超时支付我司将无法核实到账，如有疑问请提供截图联系7*24小时在线客服核实处理。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'微信充值没有二维码怎么办？','content'=>'若您使用微信存款是无法正常存款，请您刷新页面更换金额进行存款，当前受到微信官方风控影响导致存款会有一定的限制。若您当前无法存款可以选择其他方式进行存款。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'存款不成功，是什么问题？','content'=>'因单一存款方式可能存在监管、管控等问题而导致入款失败，您可多开启几种存款方式，切换其他存款方式进行尝试或者联系7*24在线客服提供以下信息：<br/>1.会员账号<br/>2.存款方式<br/>3.存款失败截图','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>1,'title'=>'微信转账不显示金额怎么办？','content'=>'1.建议您退出账号重新登录后进入页面刷新查看，选择已有相关金额进行充值。<br/>2.使用网银转账、虚拟币存款等其他现有通道进行充值。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'游戏账户里有钱为什么无法取款？','content'=>'1.确认场馆余额是否已转账至中心账户，只有主账号有对应的余额才可进行取款。<br/>2.可以点击APP首页--转账--免转钱包按钮开启免转功能，或者点击【一键回收】按钮将所有场馆余额回收至账户中心再进行取款','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'取款需要什么要求？','content'=>'若您未申请任何红利优惠，投注满一倍流水即可申请取款，例如：您存款100，在该笔存款后累计下注达到100的有效投注，即可进行取款操作，若您有申请其他优惠，则需要满足优惠活动相对应有效流水，才可办理取款，如需更多信息可联系我司24小时在线客服团队进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'取款支持的银行有哪些？','content'=>'目前为您提供71家取款银行:农业银行、工商银行、邮政银行、建设银行、中国银行、交通银行、中信银行、平安银行、光大银行、浦发银行、广发银行、华夏银行、招商银行、民生银行、兴业银行、安徽省农村信用社、北京农商行、北京银行、成都农商银行、成都银行、承德银行、大连农村商业银行、大连银行、东莞农村商业银行、东莞银行、福建省农村信用社、甘肃省农村信用社、广东省农村信用社、广西北部湾银行、广州农商银行、广州银行、贵阳银行、贵州省农村信用社、贵州银行、哈尔滨银行、海南省农村信用社、邯郸银行、杭州银行、河北省农村信用社、河北银行、河南省农村信用社、黑龙江省农村信用社、恒丰银行  、湖北省农信社、湖北银行、湖南省农村信用社、徽商银行  吉林省农村信用社、江苏省农村信用社、江苏银行、江西省农村信用社、江西银行、兰州银行、南京银行  内蒙古农村信用社、内蒙古银行、宁波银行、厦门银行、山东省农村信用社、山西省农村信用社、上海银行、四川省农村信用社、台州银行、天津银行、温州银行、云南省农村信用社、浙江省农村信用社、上海农村商业银行、深圳农村商业银行、重庆农村商业银行  、江苏农村商业银行。 温馨提示：系统不定期会有所调整，具体还请您以页面显示为准。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'为什么取款成功但没有到账？','content'=>'取款申请显示成功，说明我司已经向您申请取款的银行账号进行转账操作。可能是银行处理中，或是您的收款银行退回了我司的款项，导致您的取款没有到账。如遇该情况请您提交取款成功的银行卡交易记录截图和银行APP流水视频，及时联系我司24小时在线客服为您处理。<br/>温馨提示：若银行延迟到账，一般6小时内您的银行卡可收到该笔款项，取款被退回，在我司确认收到退款之后，会将您该笔金额调整到您的游戏账号的【中心账户】，您重新申请即可。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'取款到账时间有多久？','content'=>'财务进行转账后一般3-15分钟即可到账，若超过30分钟还未到账可及时联系我司24小时在线客服团队为您核查。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'首次取款需要注意什么？','content'=>'首次取款时，请您务必确认绑定银行卡为您本人户名银行卡，（取款银行卡姓名必须与注册姓名一致）。取款过程中，如有任何疑问可联系我司7*24小时在线客服团队进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'忘记取款密码怎么办？','content'=>'如果您忘记取款密码，请及时联系我司7*24小时在线客服团队并提供账号信息以便为您核实处理，切勿把信息泄漏至不相关人员，保护好个人隐私。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'申请取款需要注意什么？','content'=>'1.取款绑定的银行卡姓名需要与游戏账户注册的姓名一致；<br/>2.取款绑定银行卡的信息要正确；<br/>3.若您未申请任何红利优惠，投注满一倍有效流水即可申请取款；<br/>4.若申请红利，则需要满足优惠活动注明的有效投注额要求；<br/>5.全天24小时都进行取款申请；<br/>6.请您关注相对应等级的取款次数及限制要求。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'取款为什么需要审核？','content'=>'取款审核是相关部门在给您办理出款之前一个简单的步骤，为了确保客户资金安全，所以需要核实相关信息。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'为什么取款失败？','content'=>'1.点击APP右下角【我的】--投注记录，查看是否满足对应的有效流水。<br/>2.点击查看取款银行信息是否正确；<br/>3.绑定的银行卡是否为我司支持的出款银行卡我司目前支持出款银行：农业银行、工商银行、邮政银行、建设银行、中国银行、交通银行、中信银行、平安银行、光大银行、浦发银行、广发银行、华夏银行、招商银行、民生银行、兴业银行、安徽省农村信用社、北京农商行、北京银行、成都农商银行、成都银行、承德银行、大连农村商业银行、大连银行、东莞农村商业银行、东莞银行、福建省农村信用社、甘肃省农村信用社、广东省农村信用社、广西北部湾银行、广州农商银行、广州银行、贵阳银行、贵州省农村信用社、贵州银行、哈尔滨银行、海南省农村信用社、邯郸银行、杭州银行、河北省农村信用社、河北银行、河南省农村信用社、黑龙江省农村信用社、恒丰银行  、湖北省农信社、湖北银行、湖南省农村信用社、徽商银行  吉林省农村信用社、江苏省农村信用社、江苏银行、江西省农村信用社、江西银行、兰州银行、南京银行  内蒙古农村信用社、内蒙古银行、宁波银行、厦门银行、山东省农村信用社、山西省农村信用社、上海银行、四川省农村信用社、台州银行、天津银行、温州银行、云南省农村信用社、浙江省农村信用社、上海农村商业银行、深圳农村商业银行、重庆农村商业银行  、江苏农村商业银行）；<br/>若还是无法解决请您联系我司7*24小时在线客服团队提供以下信息为您处理：<br/>1.会员账号<br/>2.出款金额','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'游戏流水还差多少可进行提款？','content'=>'首先确认自己是否有申请优惠活动，找到对应优惠活动查看所需流水，再点击APP右下角【我的】--投注记录查看已打流水，无申请优惠活动情况下一倍流水即可出款。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>2,'title'=>'预约取款如何取消？','content'=>'首先确认自己是否有申请优惠活动，找到对应优惠活动查看所需流水，再点击APP右下角【我的】--投注记录查看已打流水，无申请优惠活动情况下一倍流水即可出款。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'如何注册账号？注意事项是什么？','content'=>'1.请您点击登录界面【注册】，进入注册页面，填写用户名和密码信息即可注册成功。温馨提示：注册前请确保您已满18周岁并已阅读过我司【条款和隐私政策】；<br/>2.为了规范网站游戏账户管理，每一位会员只允许注册一个游戏账户；<br/>3.同一用户名，邮箱及手机号码只能注册一个游戏账户，如果该信息已被使用，将无法再次被用于注册新账户。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'为什么要绑定真实姓名？','content'=>'为了保护您的资金安全，账号绑定姓名需与您绑定的取款银行卡一致，确保您取款时能顺利在您指定的银行账户里到账，避免他人盗用替领的情况发生。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'绑定手机号码时为什么会显示“暂不支持该手机号类型绑定，请更换其他手机号”？','content'=>'我司不支持绑定170、162、165、167、171等虚拟号码段，请使用绑定其他号码，感谢配合。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'是否可以绑定多张银行卡？','content'=>'为了体贴用户，每个会员账户可以绑定多张银行卡进行取款操作，如有疑问可联系我司24小时在线客服团队进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'如何查询存款/取款记录？','content'=>'电脑网页端PC：点击网站右上角【您的会员账号】 --【交易记录】。<br/>APP/手机网页端H5：点击右下角【我的】--【交易记录】。如需更多信息请您联系我司24小时在线客服团队进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'如何修改账户登录密码？','content'=>'电脑网页端PC：点击网站右上角【您的会员账号】--【去修改】进行登陆密码修改。<br/>APP/手机网页端H5：点击右下角【我的】--【设置】--【修改密码】进行登陆密码修改。<br/>温馨提示：为了保障您的账户和资金安全，请妥善保管您的账户密码及个人信息，切勿向他人泄露。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'如何申请注销账户？','content'=>'暂时不接受任何理由注销会员账户，如有需要可随时联系我司24小时在线客服团队申请冻结/停用您的会员账户。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'注册时显示【已超过最大注册数量】怎么办？','content'=>'如果当您注册时出现【已超过最大注册数量】时，请您耐心等待，这是我司为用户提供的安全机制，同一个IP在1小时内注册超过10个账号就会有此提示，建议您更换IP重新注册或耐心等待1小时后注册，如有疑问可联系我司24小时在线客服团队咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'场馆金额突然变少或者变为负数是什么原因？','content'=>'大部分金额变少或者变为负数是系统对注单进行二次结算导致的结果，若您对金额部分有异议可以联系平台7*24小时在线客服进行详细的咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'可以解绑银行卡号和手机号码吗？','content'=>'解绑银行卡可登录APP点击【我的】--【设置】--【银行卡管理】页面即可进行解绑操作（温馨提示：同一张银行卡解绑后无法再次绑定）。<br/>您在平台绑定的手机号码无法进行解绑，如您有其他疑问请联系7*24小时在线客服以便为您核实处理。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'忘记已注册的用户账号怎么办？','content'=>'电脑网页端PC：点击网站右侧【在线客服】联系我司7*24小时在线客服团队，为您提供必要的协助。<br/>APP/手机网页端H5：点击下方【客服】联系我司7*24小时在线客服团队，为您提供必要的协助。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'登录账户提示被锁是什么原因？','content'=>'如您输入错误密码20次以上，为保障会员账户安全，系统会自动锁定会员账号，您可以联系我司7*24小时在线客服进行沟通核实处理。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'忘记密码怎么办？','content'=>'1.请您在登录页面，点击【忘记密码】按钮；<br/>2.进入找回密码页面，填写您的用户名，通过您预留的电话或者邮箱获取新密码。<br/>注：如未绑定手机号码和邮箱可联系我司在线客服提供相关信息进行吗，密码重置。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'怎么删除绑定的银行卡？','content'=>'请您登录APP点击【我的】--【设置】--【银行卡管理页面】，即可进行解绑操作。<br/>温馨提示：银行卡解绑之后就无法再次使用解绑过的银行卡绑定任何账户。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'可以选择冻结账号吗？','content'=>'请您联系我司7*24小时在线客服团队，同时提供以下信息：<br/>1.账号<br/>2.账号注册姓名<br/>3.账号已绑定手机号<br/>4.登录地区<br/>5.首笔存款截图','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'钱包被锁定是什么原因？','content'=>'您在提交提款申请后资金会先进行锁定，该笔资金暂时不可以进行游戏','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>3,'title'=>'在网站注册之后，个人信息安全能得到保障吗？','content'=>'关于客户账户资料安全以及客户隐私权，请您参考隐私保护规则。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>4,'title'=>'什么是优惠活动？','content'=>'优惠活动是指您存款后额外获得的收益，平台将不定期推出形式多样的优惠活动，回馈广大新老玩家的支持与厚爱，当您申请优惠并审核通过后，即可获得该活动相应的优惠奖励。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>4,'title'=>'如何申请优惠活动？','content'=>'请您登录账号后点击优惠活动页面，选择相应的优惠活动申请。申请前建议您了解具体的活动内容，如有疑问可随时联系我司24小时在线客服团队进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>4,'title'=>'红利计算方式','content'=>'有效投注额达到【（本金+红利）x流水倍数】即可取款。<br/>如:存款1000元，申请获得200元红利，总计需12倍流水出款，计算公式:（1000+200）*12=14400<br/>如有疑问可联系我司24小时在线客服团队进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>4,'title'=>'活动申领时间和活动时间有什么区别？','content'=>'活动时间指该活动开始至结束的时间。活动申领时间指将整个活动时间，划分为单个或多个周期，每个周期内，您仅可申领一次优惠活动。如活动时间为2020-01-01-2020-2-29，以一个月为一个周期，则1月份和2月份，您分别可申领一次优惠活动，若申领周期内未申请参阅活动，则视为放弃该周期的优惠，如有疑问可联系我司24小时在线客服团队进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>4,'title'=>'返水什么时候发放？','content'=>'返水是实时结算的，每日返水总额从1元起开始取整数计算，1元以下返水总额将不进行计算。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>4,'title'=>'为什么返水金额不对？','content'=>'如果您对返水金额产生疑问，请您随时联系我司7*24小时在线客服团队以便为您核实处理。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>5,'title'=>'什么是代理？','content'=>'代理即为平台合作伙伴，成为平台代理，平台会为您提供一条代理链接，凡是在您代理链接下注册的会员，只要产生负盈利您都可获得相应比例返佣。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>5,'title'=>'如何加盟代理？','content'=>'电脑网页端PC：点击网站上方【合营伙伴】即可申请加入。<br/>APP/手机网页端H5：点击右下角【我的】--【加入我们】，即可申请加入。<br/>平台非常重视每一个合作伙伴，我司竭诚为您提供业内最顶级的服务。如需了解更多信息，请添加合营计划页面的联系方式，或点击【合营咨询】询问合营在线客服。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>5,'title'=>'平台代理有何优势？','content'=>'平台代理起步佣金35%。高出同行业平均水平，最高达60%的回报，全行业最高。为了确保每个合作伙伴的信息安全，我们提供双重加密的代理后台，具有行业领先的实时数据交换更新，和独一无二的后台开启密匙，平台诚邀您的加入。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>5,'title'=>'平台有代理APP吗？','content'=>'平台代理APP是平台倾情打造并自主研发的一款实用性超强的掌上代理后台。如您已成为平台代理，您可联系我司代理维护专员获取APP下载域名及操作步骤。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>6,'title'=>'虚拟货币充值未到账怎么办？','content'=>'若长时间充值未到账请提供USDT充币截图咨询在线客服。大额转账推荐使用ERC协议 ，几分钟到数十几分钟可到账，ERC协议转账手续费一般收取1-5USDT不等 ；小额/中额转账推荐使用TRC协议， 几秒到几分钟可到账，TRC协议部分同一交易所之间转账免手续费，不同交易所之间转账手续费一般收取1USDT左右。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>6,'title'=>'什么是虚拟货币充值？','content'=>'虚拟货币是去中心化的区块链货币，因此避免了中心监控，从而使得入款成功率以及入款速度大大提升，为您带来更为舒适的出入款体验！','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>6,'title'=>'如何进行虚拟货币充值？有教程吗？','content'=>'您需要先在平台支持的虚拟币三方（例如币安、币汇、MEXC、易币付）完成并注册登录，对三方虚拟币进行充值后，即可在平台存款界面选择虚拟货币充值，通过平台虚拟货币充值给出的钱包地址使用三方进行转账，成功后系统会自动到款。<br/>虚拟货币的存提款教程和文案教程可在官网查看，请您点击存款--点击虚拟币存款--下方显示文案教程+视频教程！','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>6,'title'=>'什么是钱包地址？','content'=>'钱包地址可以通俗理解为“银行卡账号”，是虚拟货币的出入款识别码，通过钱包地址进行出入款转账，是最为重要的凭据。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>6,'title'=>'虚拟货币充值订单号有什么用？','content'=>'当入款出现问题时，可提供虚拟货币订单用于查询问题进度。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>6,'title'=>'为什么取款无法绑定虚拟币钱包地址？','content'=>'使用银行卡成功取款一次后才可绑定虚拟钱包，页面会自动显示（添加钱包地址）。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>6,'title'=>'为什么虚拟币充值成功后需要划转法币至币币账户？','content'=>'可参考平台场馆钱包功能机制，法币与币币也是跨端口的，而只有币币才可进行提币至我司账户，因此需要划转。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>6,'title'=>'如何使用数字钱包取款？','content'=>'点击【取款】按钮后，可以看到【银行卡取款】【钱包取款】，选择钱包取款后，绑定您所使用的交易所USDT充值地址，取款后系统会自动转账至您的虚拟币账户。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>7,'title'=>'什么是三方钱包？','content'=>'三方钱包是存放与RMB兑换比例1:1的加密货币的地方，三方钱包采用金融级别运维风控系统，多维防护，确保资产安全，去中心化，不受银行风控影响。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>7,'title'=>'使用三方钱包在平台怎么进行取款？','content'=>'1.首页点击“取款” <br/>2.选择“对应的三方钱包取款”（如已经绑定三方钱包地址点击“立即取款”即可） <br/>3.打开三方钱包APP，点击“收款” <br/>4.复制三方钱包收款地址，切换回平台APP绑定页面 <br/>5.按照页面提示填写完成后点击“绑定地址” <br/>最后选择地址，输入”金额“点击”立即取款“完成取款','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>7,'title'=>'为什么取款无法绑定三方钱包地址？','content'=>'使用银行卡成功取款一次后才可绑定三方钱包。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>8,'title'=>'转账后金额丢失是什么原因？','content'=>'由于平台场馆紧急维护，或是系统超时等原因，可能会发生转账后金额丢失的情况，我司会自动帮您补上金额至主账户，您也可以联系我司24小时在线客服团队进行处理，一般处理时间为5-30分钟。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>8,'title'=>'免转钱包功能说明','content'=>'当您打开【免转钱包】按钮后，进入某场馆时，中心钱包的金额自动带入到对应的游戏场馆中，当您进入取款页面时，会自动回收所有场馆的余额，直接进行取款申请的操作即可。温馨提示：若因网络原因造成场馆的余额没有及时回收到中心钱包，点击转账页面上面【一键回收】按钮即可进行二次回收场馆余额。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>8,'title'=>'主账户转场馆，场馆转主账户失败怎么办？','content'=>'如遇场馆与主账户之间转账失败，建议您首先刷新页面，再查看场馆是否处于维护当中或是否存在频繁转账操作，此外您可联系我司24小时在线客服团队为您核实。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>8,'title'=>'主场馆为什么不能一眼看到所有资金？','content'=>'当您在【中心钱包】不能看到您的所有资金时，请您检查是否已开启【免转】功能，建议您进入【转账】界面进行查看，如有疑问可联系我司24小时在线客服团队进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>8,'title'=>'转账失败会是什么原因导致？','content'=>'如果游戏场馆正在维护，则该场馆余额无法显示，也无法进行转账操作，请您耐心等待，维护完成即会恢复正常。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>8,'title'=>'场馆维护期间钱转不出来怎么办？','content'=>'1.为保障会员的权益，所有场馆在维护状态下均不支持转账，存款、取款操作；<br/>2.正常场馆进行例行维护，我司最少会提前2小时发布公告进行轮播通知；<br/>3.如遇到场馆紧急维护，为了避免金额丢失或者其他问题的出现，场馆及钱包将会临时关闭，请您耐心等待相应场馆维护完成后再进行转账操作，给您带来不便敬请见谅。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'为什么我没有办法登录？','content'=>'请确认您是否在限制的国家/区域，或检查你的网络使用状态，若还是无法登陆您可联系我司24小时在线客服团队进行处理。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'APP遇到卡顿怎么办？','content'=>'建议您先检查网络是否良好，如遇APP卡顿问题时请您清除缓存后尝试。如果以上操作还是解决不了您的问题，请您录制卡顿视频及APP设备信息截图提交我司24小时在线客服团队为您进行核实处理。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'登录账户提示被锁定是什么原因？','content'=>'为了保障会员账号的安全，若您输入错误密码20次以上，系统自动锁定账号，建议您联系我司7*24小时在线客服团队进行沟通解除锁定。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'如果投注时网络断开怎么办？','content'=>'如果您在使用手机APP投注时网络断开，建议您在网络恢复后第一时间进入游戏查看，如果没有生成任何投注单号，表明您之前没有下注成功，所以建议您在网络良好的情况下进行游戏，避免不必要的损失产生。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'我为什么收不到短信验证码？','content'=>'若以下方式无法接收验证码请您第一时间联系我司7*24小时在线客服团队为您解决。<br/>1.请您检查手机号码是否支持接收验证码(我司不支持绑定的虚拟号码段：170、162、165、167、171等)<br/>2.请查看短信是否被拦截','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'比赛视频无法播放怎么办？','content'=>'请检查您的网络是否存在异常情况，尝试切换4G/WiFi，由于视频直播需要使用大量手机流量<br/>例如：您使用WiFi进行观看。若视频仍无法播放可联系我司7*24小时在线客服团队进行处理。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'为什么WIFI可以登录，数据流量无法登录账号？','content'=>'1.点击APP首页下方--我的--设备信息截图及数据流量登录IP提交至7*24小时在线客服核实详情及处理。<br/>2.若您在游戏中的一些违规行为对我司造成一定的影响，例如恶意投诉我司财务相关等行为，会导致您的IP地址可能会被风控部门限制。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'网页版的网址打不开是否跟浏览器有关？','content'=>'请您遇到该种情况时，切换其他网址进行尝试，同时使用其他浏览器进行访问。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'我是否可以成为代理？','content'=>'若您有意成为我司代理，请您点击APP首页下方--我的--加入我们进行查看，或联系7*24在线客服进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'怎么申请更改个人资料？','content'=>'您在成功注册后，可以通过手机网页H5、电脑网页pc或者其他设备登陆账号，修改个人基本信息资料。若您在过程中遇到无法处理的问题，请联系7*24小时在线客服以便为您尽快核实处理。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'为什么登录时会显示【检测到您的账号可能存在异常】？','content'=>'为保护用户账号安全，使用不同IP或者设备登录账号时以防止他人盗用您的登录会触发安全机制验证，请按提示进行验证即可。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'综合APP为什么会出现WAP和APP的选择？','content'=>'两者游戏内容没有区别，WAP模式与APP模式只是不同的端口登入游戏，可以预防一方出现问题，另一方还可以正常运行，不影响玩家游戏。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'我司游戏支持的端口有哪些？','content'=>'我司目前支持三个端口进行游戏，分别是PC电脑网页端、H5手机网页端、APP。<br/>温馨提示：首页菜单栏点击“手机APP”即可进行APP下载。如下载过程中遇到任何问题可联系7*24小时在线客服团队进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'如何防止DNS网络劫持？','content'=>'PC电脑网页端：<br/>1.单击任务栏右边网络小图标，找到“打开网络和共享中心”点击打开<br/>2.左边菜单栏中找到“更改适配器设置”。<br/>3.使用鼠标右键点击正在使用的“本地链接”，打开“属性”。<br/>4.选中“Internet协议版本4(TCP/IPv4)”--点击打开“属性”。<br/>5.点击“使用下面的DNS服务器地址”，修改首选DNS服务器：144.144.144.144 备用DNS服务器：8.8.8.8<br/>H5手机网页端：<br/>4G切换WIFI，WIFI切换4G进行尝试。<br/>若还是无法解决，请联系7*24小时在线客服进行咨询。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'客服态度不好，平台功能不完善该如何反馈？','content'=>'当您遇到服务体验不好是，请点击APP首页下方【我的】-【意见反馈】将您遇到的问题截图提交，平台会继续优化改进，致力给所有用户带来更好的体验。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'在哪里完成个人资料的填写？','content'=>'手机APP点击【我的】--【修改个人资料】按流程进行绑定，同时在第一次出款时，系统也会提示您补充必要的个人资料。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'提供那么多信息会不会泄露？','content'=>'我司系统绝对安全，也绝不会泄露客户任何的个人资料给任何商业机构，此外，我们会要求交易往来的银行、中转、代理等严格对客户的资料进行保密。所有的存款将会视为贸易户口，并不会交给其他人士进行交易，请您放心、安心。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')],
            ['carrier_id' =>$carrier->id,'type'=>9,'title'=>'怎么让APP在手机上授信？','content'=>'请您点击手机自带的【设置功能--设备管理】，选择相应的企业应用，点击【信任】即可。','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]
        ];

        \DB::table('inf_carrier_questions')->insert($questionlist);

        $confs = [
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'digital_finance_min_recharge',               
                'value'      => '100',
                'remark'     => '数字币最小充值金额',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'digital_finance_max_recharge',               
                'value'      => '1000000',
                'remark'     => '数字币最大充值金额',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'withdraw_first_audit',             
                'value'      => '',
                'remark'     => '提现审核角色1',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'withdraw_second_audit',           
                'value'      => '',
                'remark'     => '提现审核角色2',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'default_user_name',              
                'value'      => 'default_agent',
                'remark'     => '网站默认代理用户名',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'web_send_boot_token',               
                'value'      => '',
                'remark'     => '小飞机机器人token',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'admin_white_Ip_List',
                'value'      => '',
                'remark'     => '管理员登录Ip白名单',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'continuous_unpaid',                
                'value'      => 8,
                'remark'     => '连续未支付次数',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'continuous_unpaid_froze',                
                'value'      => 15,
                'remark'     => '连续未支付次数冻结帐号',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'ban_hour',                
                'value'      => 1,
                'remark'     => '禁止提交充值时间',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'carrier_bank_gift',                
                'value'      => 0,
                'remark'     => '商户自有银行卡存送比例',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'carrier_usdt_gift',                
                'value'      => 0,
                'remark'     => '商户自有USDT地址存送比例',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'delunpaidday',
                'value'      => 0,              
                'remark'     => '自动删除多少小时前的数据',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'unpay_frequency_hidden',
                'value'      => 3,              
                'remark'     => '不抗投诉多少次未付隐藏',    // 不抗投诉的多少次未付隐藏
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'default_lottery_odds',
                'value'      => 1980,              
                'remark'     => '彩票默认赔率',    // 棋牌模式低于三个人的计价金额
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'small_group_withdraw_wallet',
                'value'      => json_encode([]),              
                'remark'     => '小众钱包列表',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'casino_venue_rate',
                'value'      => 10,              
                'remark'     => '真人场馆百分比',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'electronic_venue_rate',
                'value'      => 10,              
                'remark'     => '电子场馆百分比',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'esport_venue_rate',
                'value'      => 10,              
                'remark'     => '电竞场馆百分比',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'fish_venue_rate',
                'value'      => 10,              
                'remark'     => '捕鱼场馆百分比',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'card_venue_rate',
                'value'      => 10,              
                'remark'     => '棋牌场馆百分比',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'lottery_venue_rate',
                'value'      => 10,              
                'remark'     => '彩票流场馆百分比',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'sport_venue_rate',
                'value'      => 10,              
                'remark'     => '体育场馆百分比',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'disable_phone_number_segment',
                'value'      => '165,170,192,162',             
                'remark'     => '禁止注册手机号段',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'clearbetflowlimitamount',
                'value'      => 2,            
                'remark'     => '存款时清空流水最低金额',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'okpay_down',
                'value'      => '',            
                'remark'     => 'OkPay下载网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'okpay_tutorial',
                'value'      => '',            
                'remark'     => 'OkPay教程网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'gopay_down',
                'value'      => '',            
                'remark'     => 'GoPay下载网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'gopay_tutorial',
                'value'      => '',            
                'remark'     => 'GoPay教程网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'topay_down',
                'value'      => '',            
                'remark'     => 'ToPay下载网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'topay_tutorial',
                'value'      => '',            
                'remark'     => 'ToPay教程网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'ebpay_down',
                'value'      => '',            
                'remark'     => 'EbPay下载网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'ebpay_tutorial',
                'value'      => '',            
                'remark'     => 'EbPay教程网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'wanb_down',
                'value'      => '',            
                'remark'     => 'WanbPay下载网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'wanb_tutorial',
                'value'      => '',            
                'remark'     => 'WanbPay教程网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'jdpay_down',
                'value'      => '',            
                'remark'     => 'JdPay下载网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'jdpay_tutorial',
                'value'      => '',            
                'remark'     => 'JdPay教程网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'kdpay_down',
                'value'      => '',            
                'remark'     => 'KdPay下载网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'kdpay_tutorial',
                'value'      => '',            
                'remark'     => 'KdPay教程网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'nopay_down',
                'value'      => '',            
                'remark'     => 'NoPay下载网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'nopay_tutorial',
                'value'      => '',            
                'remark'     => 'NoPay教程网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'bobipay_down',
                'value'      => '',            
                'remark'     => 'BobiPay下载网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'bobipay_tutorial',
                'value'      => '',            
                'remark'     => 'BobiPay教程网址',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'enable_limit_one_withdrawal',
                'value'      => 0,            
                'remark'     => '每次仅能提一笔',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'default_language_code',                
                'value'      => 'zh-cn',
                'remark'     => '默认站点语言',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'supportMemberLangMap',                
                'value'      => 'zh-cn',
                'remark'     => '可选语言列表',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'min_tranin_gameplat_amount',               
                'value'      => 1,
                'remark'     => '转入三方游戏最小金额',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'enable_auto_pay',               
                'value'      => 0,
                'remark'     => '开启自动付款',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'auto_pay_single_limit',               
                'value'      => 1000,
                'remark'     => '自动付款单笔金额限制',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'carrier_id' => $carrier->id,
                'sign'       => 'auto_pay_day_limit',               
                'value'      => 5000,
                'remark'     => '自动付款每天金额限制',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];

        \DB::Table('conf_carrier_web_site')->insert($confs);

        //添加额度调整记录
        if($carrier->remain_quota > 0) {
            $remainQuota                         = new RemainQuota();
            $remainQuota->carrier_id             = $carrier->id;
            $remainQuota->amount                 = $carrier->remain_quota;
            $remainQuota->before_remainquota     = 0;
            $remainQuota->remainquota            = $carrier->remain_quota;
            $remainQuota->direction              = 1;
            $remainQuota->mark                   = '创建商户时添加';
            $remainQuota->save();
        }

        //生成活动数据
        $carrier_id        = $carrier->id;

        //生成IP黑名单
        $playerIpBlack                      = new PlayerIpBlack();
        $playerIpBlack->carrier_id          = $carrier_id;
        $playerIpBlack->save();

        $banks             = Banks::get();
        $carrierBankTypes  = [];

        foreach ($banks as $key => $value) {
            $row                        = [];
            $row['bank_name']           = $value->bank_name;
            $row['bank_code']           = $value->bank_code;
            $row['bank_background_url'] = $value->bank_background_url;
            $row['currency']            = $value->currency;
            $row['carrier_id']          = $carrier->id;
            $row['created_at']          = date('Y-m-d H:i:s');
            $row['updated_at']          = date('Y-m-d H:i:s');
            $carrierBankTypes[]         = $row;
        }
           
        \DB::table('inf_carrier_bank_type')->insert($carrierBankTypes);

        
        $carrierActivityLuckDraw                        = new CarrierActivityLuckDraw();
        $carrierActivityLuckDraw->carrier_id            = $carrier_id;
        $carrierActivityLuckDraw->game_category         = 0;
        $carrierActivityLuckDraw->name                  = '轮盘模板';
        $carrierActivityLuckDraw->startTime             = 1637596800;
        $carrierActivityLuckDraw->endTime               = 1640188799;
        $carrierActivityLuckDraw->signup_type           = 1;
        $carrierActivityLuckDraw->content               = config('activity')['zh']['10'];
        $carrierActivityLuckDraw->vi_content            = config('activity')['vi']['10'];
        $carrierActivityLuckDraw->en_content            = config('activity')['en']['10'];
        $carrierActivityLuckDraw->tl_content            = config('activity')['tl']['10'];
        $carrierActivityLuckDraw->th_content            = config('activity')['th']['10'];
        $carrierActivityLuckDraw->id_content            = config('activity')['id']['10'];
        $carrierActivityLuckDraw->hi_content            = config('activity')['hi']['10'];
        $carrierActivityLuckDraw->number                = 6;
        $carrierActivityLuckDraw->prize_json            = '[{"bonus":"1","probability":"100"},{"bonus":"2","probability":"100"},{"bonus":"3","probability":"100"},{"bonus":"4","probability":"100"},{"bonus":"5","probability":"100"},{"bonus":"6","probability":"500"}]';
        $carrierActivityLuckDraw->number_luck_draw_json = '[{"amount":"10","number":"1","isshow":"true"}]';
        $carrierActivityLuckDraw->person_account        = 0;
        $carrierActivityLuckDraw->payout                = 0;
        $carrierActivityLuckDraw->status                = 0;
        $carrierActivityLuckDraw->save();

        //更新商户数量
        CarrierCache::updateCarrierIds();
    }
}