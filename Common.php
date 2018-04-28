<?php
namespace WeChatPay;
class Common{
    //微信授权URL(目的是获取code)
    const CODEURL = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
    //获取用户的openid的URL
    const OPENIDURL = 'https://api.weixin.qq.com/sns/oauth2/access_token?';
    /**
     * 获取签名:$key为商户后台的api安全密钥
     * @param $arr 数组
     * @param $key 支付API密钥：商户后台配置有
     * @return string 返回加密字符串
     */
    public function getSign($arr,$key){
        //去除数组的空值
        array_filter($arr);
        if(isset($arr['sign'])){
            unset($arr['sign']);
        }
        //ASCII码从小到大排序
        ksort($arr);
        //进行url参数格式组装排序
        $str = $this->arrToUrl($arr) . '&key=' . $key;
        //使用md5 加密 转换成大写
        return strtoupper(md5($str));
    }
    /**
     * 获取带签名的数组
     * @param $arr
     * @return mixed
     */
    public function setSign($arr,$key){
        $arr['sign'] = $this->getSign($arr,$key);
        ksort($arr);
        return $arr;
    }
    /**
     * 防止中文和网址的转义(反转义),生成不带key的URL的key=>value值
     * @param $arr 数组
     * @return string 字符串
     */
    public function arrToUrl($arr){
        return urldecode(http_build_query($arr));
    }
    /**
     * 校验签名
     * @param $arr array
     * @return bool  true|false
     */
    public function checkSign($arr){
        //获取新的签名
        $sgin = $this->getSign($arr);
        if($sgin==$arr['sgin']){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 获取指定长度的随机字符串
     * @param $length 字符串长度
     * @return $str 字符串
     */
    public function getRandChar($length){
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;

        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        return $str;
    }
    /**
     * 获取当前服务器的IP
     * @return string
     */
    function get_client_ip(){
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "unknown";
        }
        return $cip;
    }
    /*
     * 接收微信返回的post数据
     * @return xml
     */
    public function getPost(){
        return file_get_contents('php://input');
    }
    /**
     * 将Xml文件转数组
     * @param $xml
     * @return array
     */
    public function XmlToArr($xml){
        if($xml == '') return '';
        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $arr;
    }
    /**
     * 将数组转化成xml
     * @param $arr array
     * @return $xml xml
     */
    public function ArrToXml($arr){
        if(!is_array($arr) || count($arr) == 0) return '';

        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    /**
     * 以curl的post请求
     * @param $url 请求地址
     * @param $postfields 发送的数据
     * @return mixed
     */
    public function postXmlCurl($url,$postfields){
        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = false; //是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_POST] = true;
        $params[CURLOPT_SSL_VERIFYPEER] = false;//禁用证书校验
        $params[CURLOPT_SSL_VERIFYHOST] = false;
        $params[CURLOPT_POSTFIELDS] = $postfields;
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        return $content;
    }
    /**
     * 记录到文件
     * @param $file 文件名
     * @param $data 写入文件的内容
     */

    public  function logs($file,$data){
        $data = is_array($data) ? print_r($data,true) : $data;
        file_put_contents('./logs/' .$file, $data);
    }
    /**
     * 获取微信openId
     * @param null $openid
     * @return mixed
     */
    public function getOpenId($appid,$secret){
        if(isset($_SESSION['openid'])){
            return $_SESSION['openid'];
        }else{
            //1.用户访问一个地址 先获取到code
            if(!isset($_GET['code'])){
                $redurl = 'http'.$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $url = self::CODEURL . "appid=" .$appid ."&redirect_uri={$redurl}&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
                //构建跳转地址 跳转
                header("location:{$url}");
            }else{
                //2.根据code获取到openid
                //调用接口获取openid
                $openidurl = self::OPENIDURL . "appid=" . $appid . "&secret=".$secret . "&code=" . $_GET['code'] . "&grant_type=authorization_code";
                $data = file_get_contents($openidurl);
                $arr = json_decode($data,true);
                $_SESSION['openid'] = $arr['openid'];
                return $_SESSION['openid'];
            }
        }
    }
}