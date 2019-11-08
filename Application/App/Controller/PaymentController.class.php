<?php
namespace App\Controller;
use Think\Controller;
use Lib\Wx;
class PaymentController extends CommonController {

	// APP支付成功后,会调用你填写的回调地址 .
	// 返回参数详见微信文档:https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_7&index=3
	// 微信支付回调 案例
	public function wxapp_notify(){

		$success_xml = '<xml>
                                      <return_code><![CDATA[SUCCESS]]></return_code>
                                      <return_msg><![CDATA[OK]]></return_msg>
                              </xml>';

		$fail_xml = '<xml>
                              <return_code><![CDATA[FAIL]]></return_code>
                              <return_msg><![CDATA[ERROR]]></return_msg>
                          </xml>';;
		//接收微信返回的数据数据,返回的xml格式
		$xmlData = file_get_contents('php://input');
//		//将xml格式转换为数组
		$data = from_xml($xmlData);

//		$data_tmp = '{"appid":"wx92716de5a4d2898e","bank_type":"CFT","cash_fee":"1","fee_type":"CNY","is_subscribe":"N","mch_id":"1559428681","nonce_str":"8fQCPBXvrUSk0zoDyqstwT9VFKbOGIL7","openid":"oeiLvs0cHNpZ2_fBBfTD8vKwe5h0","out_trade_no":"bd35bd0c8c4cd07156fefe95e5b628f5","result_code":"SUCCESS","return_code":"SUCCESS","sign":"64B590D87D210182D876B9145BA67F9D","time_end":"20191108151240","total_fee":"1","trade_type":"APP","transaction_id":"4200000469201911087869698752"}';
//
//		$data = json_decode($data_tmp, true);

//		//为了防止假数据，验证签名是否和返回的一样。
//		//记录一下，返回回来的签名，生成签名的时候，必须剔除sign字段。
		$sign = $data['sign'];
		unset($data['sign']);
		if($sign == get_sign($data)){
			//签名验证成功后，处理业务逻辑
			//商户订单号
			$out_trade_no = $data['out_trade_no'];
			//支付宝交易号
			$trade_no = $data['transaction_id'];
			//交易状态
			$trade_result = strtolower($data['return_code']);
			//订单的实际金额
			$total_amount = $data['total_fee'] / 100;

			//付款时间
			$payment_time = $data['time_end'];

			//appid 和 商户id
			$app_id = $data['appid'];
			$mch_id = $data['mch_id'];

			$wxConfig = C('WXAPP_PAY_CONFIG');
			if($app_id != $wxConfig['appid'] || $mch_id != $wxConfig['mch_id']){
				// 商户id或者appid不正确
				write_log('log/wxpay/', '微信同步失败,商户id或者appid不正确,参数为' . json_encode($data));
				echo $fail_xml;
			}

			//验证订单的准确性
			if(!empty($out_trade_no)){
				//在这里可以通过返回的商家订单号查询出该订单的信息
				$order_model = D('Order');

				$res = $order_model->update_result($out_trade_no, $trade_result, $total_amount, strtotime($payment_time), $trade_no);

				if(is_string($res)){
					write_log('log/wxpay/', '微信同步失败,失败原因:' . $res . ',参数为' . json_encode($data));
					echo $fail_xml;
				}

				if($res ===false){
					write_log('log/wxpay/', '微信同步失败,更新数据库失败,参数为:' . json_encode($data));
					echo $fail_xml;
				}else{
					echo $success_xml;
				}
			}
		} else{
			write_log('log/wxpay/', '微信同步失败,验签失败,参数为:' . json_encode($data));
			echo $fail_xml;
		}
	}

	/**
	 * 阿里支付异步通知接口
	 * @date   2019/11/6 下午5:30
	 * @url    app/payment/alipay_notify/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function alipay_notify(){

		$aliConfig = C('ALIPAY_CONFIG');

		$params_json = '{
	"gmt_create":"2019-11-08 13:13:15","charset":"utf-8","seller_email":"905556960@qq.com","subject":"\u9700\u6c42\u53d1\u5e03\u8bda\u610f\u91d1","sign":"WuWM6WjWBKiZYXdM2FT98ZkKEuuEWG+fd7MMb7P+BelTdDdfzDwfUgPk\/oeVYjtlB0+hrxk5P2iq1YKQZad8WB3Xo9K8Au9K\/aZ4Owy16B4Ox8emTwz019xlIgc7V5sqJuzgDxaVVqUYiPEn29vM8mq4EeYPtNAmK6PRwrphXWt9zt7I6W7MO1L6XIW1NM2e4acRx4u+RR+OxUf2g6\/YQxask+YeZfp\/cp3fyUVA4EdrX7\/zoaSdKYEmwWWRrBuCQQXRYvQNZ\/K2CQIAyWaSYp\/UafEFPSzNJdlLd2Yltd7FUOl3F25rJu1gnWKvVMTGF4CZSmsH\/AJlj+cBLIcTcQ==","body":"test","buyer_id":"2088502963226630","invoice_amount":"0.01","notify_id":"2019110800222131316026630551885912","fund_bill_list":"[{&quot;amount&quot;:&quot;0.01&quot;,&quot;fundChannel&quot;:&quot;ALIPAYACCOUNT&quot;}]","notify_type":"trade_status_sync","trade_status":"TRADE_SUCCESS","receipt_amount":"0.01","app_id":"2019110568909721","buyer_pay_amount":"0.01","sign_type":"RSA2","seller_id":"2088631769825760","gmt_payment":"2019-11-08 13:13:16","notify_time":"2019-11-08 13:13:17","version":"1.0","out_trade_no":"ee40453c25cb67817a6f52fbbd9f5713","total_amount":"0.01","trade_no":"2019110822001426630521721438","auth_app_id":"2019110568909721","buyer_logon_id":"xdi***@163.com","point_amount":"0.00"}';

		$params = json_decode($params_json, true);
//		$params = $_POST;
		var_dump($params);

		Vendor('Alipay.aop.AopClient');
		$aop = new \AopClient();

		$aop->alipayrsaPublicKey = $aliConfig['alipayrsaPublicKey'];
		$flag = $aop->rsaCheckV1($params, NULL, "RSA2");

		if($flag){
			//商户订单号
			$out_trade_no = $_POST['out_trade_no'];
			//支付宝交易号
			$trade_no = $_POST['trade_no'];
			//交易状态
			$trade_status = $_POST['trade_status'];
			//订单的实际金额
			$total_amount = $_POST['total_amount'];

			//付款时间
			$payment_time = $_POST['gmt_payment'];
			//appid
			$appid = $_POST['app_id'];
			$seller_id = $_POST['seller_id'];
			//验证app_id是否为商户本身
			if($appid != $aliConfig['appId'] || $seller_id != $aliConfig['seller_id']){
				// 商户id或者pid不正确
//				file_put_contents('log/2.txt', json_encode($params), FILE_APPEND);
				write_log('log/alipay/', '支付宝同步失败,商户id或者pid不正确,参数为' . json_encode($params));
				return;
			}

			//验证订单的准确性
			if(!empty($out_trade_no)){
				//在这里可以通过返回的商家订单号查询出该订单的信息
				$order_model = D('Order');

				//判断交易通知状态是否为TRADE_SUCCESS或TRADE_FINISH
				if(in_array($trade_status, ['TRADE_FINISH', 'TRADE_SUCCESS'])){
					$trade_result = 'success';
				}elseif($trade_status == 'TRADE_FAIL'){
					$trade_result = 'fail';
				}

				$res = $order_model->update_result($out_trade_no, $trade_result, $total_amount, strtotime($payment_time), $trade_no);

				if(is_string($res)){
//					$this->result_return(null, 500, $res);
//					file_put_contents('log/2.txt', json_encode($params), FILE_APPEND);
					write_log('log/alipay/', '支付宝同步失败,失败原因:' . $res . ',参数为' . json_encode($params));
					return;
				}

				if($res ===false){
//					file_put_contents('log/2.txt', json_encode($params), FILE_APPEND);
					write_log('log/alipay/', '支付宝同步失败,更新数据库失败,参数为:' . json_encode($params));
					return;
				}
			}
        } else {
//			file_put_contents('log/2.txt', json_encode($params), FILE_APPEND);
			write_log('log/alipay/', '支付宝同步失败,验签失败,参数为:' . json_encode($params));
			return;
		}
	}
}