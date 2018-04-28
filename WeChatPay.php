<?php
namespace WeChatPay;
include 'Common.php';
include 'phpqrcode/phpqrcode.php';
Class WeChatPay extends Common{
    //********************************公共配置参数开始***************************************************//
    //支付请求地址
    const UNURL='https://api.mch.weixin.qq.com/pay/unifiedorder';
    //支付成功回调地址服务器回调地址
    const NOTIFY = 'http://dspx.tstmobile.com/api/live/notify';
    //********************************公共配置参数结束***************************************************//
    //********************************app支付开放平台相关配置参数开始***************************************************//
    //微信开放平台的应用appid
    private $open_appid = '';
    //商户号（注册商户平台时，发置注册邮箱的商户id）
    private $open_mchid = '';
    //商户平台api支付处设置的key
    private $open_key = '';
    //*********************************app支付开放平台相关配置参数结束**************************************************//
    //*********************************微信公共好支付和扫码支付公共配置开始**************************************************//
    //商户API 密钥
    private $key = '';
    //公众号appID
    private $appid = '';
    //公众号AppSecret
    private $secret = '';
    //商户号id
    private $mchid = '';
    //*********************************微信公共好支付和扫码支付公共配置结束**************************************************//
    //生成支付所有的配置参数
    public function __construct(){
        //开放平台
        $this->open_appid= config('OPEN_APPId');
        $this->open_mchid= config('OPEN_MCHID');
        $this->open_key= config('OPEN_KEY');
        //公众号
        $this->appid= config('APPId');
        $this->mchid= config('MCHID');
        $this->key= config('KEY');
        $this->secret = config('SECRET');
    }
    //*****************************************APP支付生成统一订单************************************************/
    //微信App支付生成订单
    public function wechat_apppay($body, $out_trade_no, $total_fee){
        $data["appid"] = $this->open_appid;
        $data["body"] = $body;
        $data["mch_id"] = $this->open_mchid;
        $data["nonce_str"] = $this->getRandChar(32);
        $data["notify_url"] = self::NOTIFY;
        $data["out_trade_no"] = $out_trade_no;
        $data["spbill_create_ip"] = $this->get_client_ip();
        $data["total_fee"] = $total_fee;
        $data["trade_type"] = "APP";
        //按照参数名ASCII字典序排序并且拼接API密钥生成签名
        $data = $this->setSign($data,$this->open_key);
        //配置xml最终得到最终发送的数据
        $xml = $this->ArrToXml($data);
        $response = $this->postXmlCurl(self::UNURL,$xml);
        //将微信返回的结果xml转成数组
        $arr  = $this->XmlToArr($response);
        //app调起支付进行二次签名Ios
        $params = [
            'appid' => $this->open_appid,
            'partnerid' =>$this->open_mchid,
            'prepayid' => $arr['prepay_id'],
            'package' =>'Sign=WXPay',
            'noncestr' =>$this->getRandChar(32),
            'timestamp'=>strval(time()),
        ];
        $params = $this->setSign($params,$this->open_key);
        echo json_encode($params);exit;
    }
    //*****************************************微信公众号统一生成订单************************************************/
    //微信公众号支付生成订单
    public function wechat_pubpay($body, $out_trade_no, $total_fee){
        /**
         * 1.构建原始数据
         * 2.加入签名
         * 3.将数据转换为XML
         * 4.发送XML格式的数据到接口地址
         */
        session_start();
        $params = [
            'appid' => $this->appid,
            'mch_id' => $this->mchid,
            'nonce_str' => $this->getRandChar(32),
            'body' => $body,
            'out_trade_no' => $out_trade_no,
            'total_fee' => $total_fee,
            'spbill_create_ip' => $this->get_client_ip(),
            'notify_url' => self::NOTIFY,
            'trade_type' => 'JSAPI',
            'openid' => $this->getOpenId($this->appid,$this->secret),
        ];
        //设置签名
        $params = $this->setSign($params,$this->key);
        //设置将数组转化成xml
        $xmldata = $this->ArrToXml($params);
        //写入log日志
        $resdata = $this->postXmlCurl(self::UNURL, $xmldata);
        $arr = $this->XmlToArr($resdata);
        //获取微信公众号支付需要的json数据
        $params = [
            'appId' => $this->appid,
            'timeStamp' =>strval(time()),
            'nonceStr' => md5(time()),
            'package' =>'prepay_id=' . $arr['prepay_id'],
            'signType' =>'MD5',
        ];
        ///进行签名
        $params['paySign'] = $this->getSign($params,$this->key);
        return json_encode($params);
    }
    //*****************************************微信扫码支付统一生成订单************************************************/
    //微信公众号支付生成订单
    /**
     * 1.获取二维码
     */
    public function wechat_getcode($out_trade_no){
        $params = [
            'appid'             => $this->appid,
            'mch_id'            => $this->mchid,
            'product_id' 	=> $out_trade_no,//订单号(产品ID)
            'time_stamp' 	=> time(),
            'nonce_str' 	=> md5(time()),
        ];
        $QRul =  'weixin://wxpay/bizpayurl?' . $this->arrToUrl($this->setSign($params,$this->key));
        echo \QRcode::png($QRul);exit;
    }
    /**
     * 2.进行扫码支付
     */
    public function wechat_codepay($body, $out_trade_no, $total_fee){
        //调用统一下单API
        $params = [
            'appid'=> $this->appid,
            'mch_id'=> $this->mchid,
            'nonce_str'=>md5(time()),
            'body'=> $body,
            'out_trade_no'=> $out_trade_no,
            'total_fee'=> $total_fee,
            'spbill_create_ip'=>$this->get_client_ip(),
            'notify_url'=> self::NOTIFY,
            'trade_type'=>'NATIVE',
        ];
        //设置签名
        $params = $this->setSign($params,$this->key);
        //将数组转为xml
        $xml = $this->ArrToXml($params);
        //发送数据到统一下单API地址
        $response = $this->postXmlCurl(self::UNURL,$xml);
        $arr = $this->XmlToArr($response);
        if($arr['result_code'] == 'SUCCESS' && $arr['return_code'] == 'SUCCESS'){
            //获取扫码支付所需要的xml数据
            $return_params = [
                'return_code'  => 'SUCCESS',
                'appid'  => $this->appid,
                'mch_id'  => $this->mchid,
                'nonce_str'  => $this->getRandChar(32),
                'prepay_id'  => $arr['prepay_id'],
                'result_code'=> 'SUCCESS'
            ];
            //返回的xml
            $return_params = $this->setSign($return_params,$this->key);
            $return_xml = $this->ArrToXml($return_params);
            echo $return_xml;
        }else{
            $this->logs('error.txt', json_encode($arr));
            return false;
        }
    }
}