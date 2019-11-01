<?php

namespace Lib\Ali;

class Alipay
{
	public function ali_pay($subject, $order_sn, $total_amount)
	{
		//导入支付宝类
		Vendor('Alipay.aop.AopClient');
		Vendor('Alipay.aop.request.AlipayTradeAppPayRequest');
		$aop = new \AopClient();
		$aliConfig = C('ALI_CONFIG');
		//配置基本参数
		//网关
		$aop->gatewayUrl = $aliConfig['gatewayUrl'];
		//appid
		$aop->appId = $aliConfig['appId'];
		//开发者私钥
		$aop->rsaPrivateKey = $aliConfig['rsaPrivateKey'];
		//支付宝公钥
		$aop->alipayrsaPublicKey = $aliConfig['alipayrsaPublicKey'];
		$aop->apiVersion = '1.0';
		//固定参数
		$aop->postCharset = 'utf-8';//固定参数
		$aop->format = 'json';//固定参数
		$aop->signType = 'RSA2';//固定参数
		//商户订单编号
		$out_trade_no = $order_sn;//商家自己生成的订单id
		//描述
		$body = 'test';
		//标题
//		$subject = $subject;
		//查询订单价格，支付宝的价格和微信的有所不同，如果是1元的话就是1.00，10元的话就是10.00
		$order_amount = number_format($total_amount, 2);
		$request = new \AlipayTradeAppPayRequest();
		$bizContent = "{" . "    \"body\":\"$body.\"," . "    \"subject\":\"$subject\"," . "    \"out_trade_no\":\"$out_trade_no\"," . "    \"timeout_express\":\"30m\"," . "    \"total_amount\":$order_amount," . "    \"product_code\":\"QUICK_WAP_WAY\"" . "  }";
		//异步回调地址
		$request->setNotifyUrl($aliConfig['notifyUrl']);
		//发送数据内容到支付宝
		$request->setBizContent($bizContent);
		$response = $aop->sdkExecute($request);
		if ($response)
		{
			return $response;
			//返回这一大串给前端就完成的支付宝APP支付的调用
		}

	}
}