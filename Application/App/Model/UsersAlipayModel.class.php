<?php
namespace App\Model;
use Think\Model;

class UsersAlipayModel extends CommonModel{

	public function get_login_info_str($appid, $pid, $private_key){

		$infoStr = http_build_query([
			'apiname' => 'com.alipay.account.auth',
			'method' => 'alipay.open.auth.sdk.code.get',
			'app_id' => $appid,
			'app_name' => 'mc',
			'biz_type' => 'openservice',
			'pid' => $pid,
			'product_id' => 'APP_FAST_LOGIN',
			'scope' => 'kuaijie',
			'target_id' => session_create_random_id(32), //商户标识该次用户授权请求的ID，该值在商户端应保持唯一
			'auth_type' => 'AUTHACCOUNT', // AUTHACCOUNT代表授权；LOGIN代表登录
			'sign_type' => 'RSA2',
		]);
		$infoStr .= '&sign='.$this->enRSA2($infoStr, $private_key);

		return $infoStr;
	}

	public function get_login_info_str1($appid, $pid, $private_key, $public_key){

		Vendor('Alipay.aop.AopClient');

		$infoStr = [
			'apiname' => 'com.alipay.account.auth',
			'method' => 'alipay.open.auth.sdk.code.get',
			'app_id' => $appid,
			'app_name' => 'mc',
			'biz_type' => 'openservice',
			'pid' => $pid,
			'product_id' => 'APP_FAST_LOGIN',
			'scope' => 'kuaijie',
			'target_id' => session_create_random_id(32), //商户标识该次用户授权请求的ID，该值在商户端应保持唯一
			'auth_type' => 'AUTHACCOUNT', // AUTHACCOUNT代表授权；LOGIN代表登录
			'sign_type' => 'RSA2',
		];

		$aop = new \AopClient ();

		$aop->appId = $appid;
		$aop->rsaPrivateKey = $private_key;
		$aop->alipayrsaPublicKey = $public_key;
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='GBK';
		$aop->format='json';

		$sign = urlencode(($aop->rsaSign($infoStr, 'RSA2')));
		$infoStr = $aop->getSignContent($infoStr) . '&sign=' . $sign;

		return $infoStr;
	}

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
		$aop->postCharset='UTF-8';
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
		$aop->postCharset='UTF-8';
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

	private function enRSA2($data, $private_key){
		$str = chunk_split(trim($private_key), 64, "\n");
		$key = "-----BEGIN RSA PRIVATE KEY-----\n$str-----END RSA PRIVATE KEY-----\n";
		// $key = file_get_contents(storage_path('rsa_private_key.pem')); 为文件时这样引入R
		$signature = '';
		$signature = openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256)?base64_encode($signature):NULL;
		return $signature;
	}
}