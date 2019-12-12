<?php
namespace App\Model;
use Think\Model;

class UserAlipayModel extends CommonModel{

	public function get_access_token_by_code($code){
		//导入支付宝类
		Vendor('Alipay.aop.AopClient');
		Vendor('Alipay.aop.request.AlipaySystemOauthTokenRequest');

		$aop = new \AopClient ();
		$aliConfig = C('ALIPAY_CONFIG');

		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $aliConfig['appId'];
		$aop->rsaPrivateKey = $aliConfig['rsaPrivateKey'];
		$aop->alipayrsaPublicKey = $aliConfig['alipayPublicKey'];
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='GBK';
		$aop->format='json';
		$request = new \AlipaySystemOauthTokenRequest ();
		$request->setGrantType("authorization_code");
		$request->setCode($code);
		//		$request->setRefreshToken("201208134b203fe6c11548bcabd8da5bb087a83b");
		$result = $aop->execute($request);

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;

		return $result;
		//
		//		if(!empty($resultCode)&&$resultCode != 10000){
		//			echo "失败";
		//		} else {
		//			echo "成功";
		//		}
	}

	public function get_user_info_by_access_token($access_token){
		//导入支付宝类
		Vendor('Alipay.aop.AopClient');
		Vendor('Alipay.aop.request.AlipayUserInfoShareRequest');

		$aop = new \AopClient ();
		$aliConfig = C('ALIPAY_CONFIG');

		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $aliConfig['appId'];
		$aop->rsaPrivateKey = $aliConfig['rsaPrivateKey'];
		$aop->alipayrsaPublicKey = $aliConfig['alipayPublicKey'];

		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='GBK';
		$aop->format='json';
		$request = new \AlipayUserInfoShareRequest();
		$result = $aop->execute ($request , $access_token );

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;

		return $result;
		//		if(!empty($resultCode)&&$resultCode == 10000){
		//			echo "成功";
		//		} else {
		//			echo "失败";
		//		}
	}
}