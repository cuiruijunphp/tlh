<?php
namespace App\Model;
use Think\Model;

class UsersAlipayModel extends CommonModel{

	/*
	 * app的支付宝授权登录,是需要和支付一样,把授权字符串,直接返回给客户端
	 */
	public function get_login_info_str($appid, $pid, $private_key, $public_key){

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

	/*
	 * 支付宝code换access_token
	 */
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

	/*
	 * 获取支付宝授权用户信息
	 */
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

	/*
	 * 微博code换access_token
	 */
	public function get_weibo_access_token_by_code($code, $app_id, $app_secret, $redirect_uri){
		$url = 'https://api.weibo.com/oauth2/access_token';

		$data = [
			'client_id' => $app_id,
			'client_secret' => $app_secret,
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $redirect_uri,
		];

		$result = http_post_request($url, $data);

		if (!$result)
		{
			return false;
		}

		$result = json_decode($result, true);

		return $result;
	}

	/*
	 * 获取微博用户信息
	 */
	public function get_weibo_user_info($access_token){
		$url = 'https://api.weibo.com/oauth2/get_token_info';

		$data = [
			'access_token' => $access_token,
		];

		$result = http_post_request($url, http_build_query($data));

		if (!$result)
		{
			return false;
		}

		$result = json_decode($result, true);

		return $result;
	}
}