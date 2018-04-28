##微信支付集成(公众号,扫码,APP)
####目录说明
- **Common.php：公共方法**
- **WeChatPay.php：支付的方法和属性说明**
		**1.参数说明**
	
	```
	//***************公共参数******************//
    //支付请求地址
    const UNURL='https://api.mch.weixin.qq.com/pay/unifiedorder';
    //支付成功回调地址服务器回调地址
    const NOTIFY = 'http://dspx.tstmobile.com/api/live/notify';
    /***********************微信开放平台配置参数*********************//
    //微信开放平台的应用appid
    private $open_appid = '';
    //商户号（注册商户平台时，发置注册邮箱的商户id）
    private $open_mchid = '';
    //商户平台api支付处设置的key
    private $open_key = '';
    /*********************微信公众平台配置参数*****************************
    //商户API 密钥
    private $key = '';
    //公众号appID
    private $appid = '';
    //公众号AppSecret
    private $secret = '';
    //商户号id
    private $mchid = '';
	```
	**2.app支付方法**
	>wechat_apppay(\$body, \$out_trade_no, \$total_fee)//返回json数据app直接使用调用支付
	
	**3.微信公众号支付方法**
	>wechat_pubpay(\$body, \$out_trade_no, \$total_fee)//返回json数据前端直接使用调用支付
	
	**4.微信扫码支付**
	>wechat_getcode($out_trade_no)//获取二维码
	
	>wechat_codepay(\$body, \$out_trade_no, $total_fee)//扫码回调地址调用需在商户后台配置

	说明：\$body:订单描述;\$out_trade_no:订单号；\$total_fee:支付金额
	
**Notify.php：支付回调和金额验证方法**
```
public function pay_result()//支付回调业务处理
public function checkPrice($arr)//支付回调金额验证方法
```
		

	
	
	
	
			
