<?php
namespace WeChatPay;
include 'Common.php';
class Notify
{
    public function pay_result() {
        $common = new Common();
        //获取微信服务器提交过来的通知数据
        $xml = $common->getPost();
        //将XML格式的数据转换为数组
        $arr = $common->XmlToArr($xml);
        //验证订单金额
        if($this->checkPrice($arr)){
            //更新订单状态
            $params = [
                'return_code'    => 'SUCCESS',
                'return_msg'    => 'OK'
            ];
            return json_encode($arr);
        }
    }
    //校验订单金额 根据订单号$arr['out_trade_no'] 在商户系统内查询订单金额 并和$arr['total_fee']做比较
    public function checkPrice($arr){
        $common = new Common();
        if($arr['return_code'] == 'SUCCESS' && $arr['result_code'] == 'SUCCESS'){
            if($arr['total_fee'] == 1){//生产环境需要根据订单号在数据库中查询金额
                return true;
            }else{
                $common->logs('log.txt', '订单金额不匹配!微信支付系统提交过来的金额为' . $arr['total_fee']);
            }
        }else{
            $common->logs('log.txt', '通知状态有误!');
        }
    }
}