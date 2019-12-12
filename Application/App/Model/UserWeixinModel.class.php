<?php
namespace App\Model;
use Think\Model;

class UserWeixinModel extends CommonModel{

	const WEIXIN_API = 'https://api.weixin.qq.com/cgi-bin/';

	const WEIXIN_FILE_API = 'http://file.api.weixin.qq.com/cgi-bin/';

	const WEIXIN_OAUTH_API = 'https://api.weixin.qq.com/sns/';

	public function get_sns_access_token_by_authorization_code($code, $app_id = NULL, $app_secret = NULL)
	{
		$url = self::WEIXIN_OAUTH_API . 'oauth2/access_token?appid=' . $app_id . '&secret=' . $app_secret . '&code=' . $code . '&grant_type=authorization_code';
		//$result = curl_get_contents(self::WEIXIN_OAUTH_API . 'oauth2/access_token?appid=' . get_setting('weixin_app_id') . '&secret=' . get_setting('weixin_app_secret') . '&code=' . $code . '&grant_type=authorization_code');
		$result = file_get_contents($url, false, stream_context_create(array(
				'http' => array(
					'timeout' => 30
				))
		));

		if (!$result)
		{
			return false;
		}

		$result = json_decode($result, true);

		return $result;
	}


	public function get_user_info_by_oauth_openid($access_token, $openid, $curl_retry = true)
	{
		$request_uri = self::WEIXIN_OAUTH_API . 'userinfo?access_token=' . $access_token . '&openid=' . $openid;

		$result = http_get_request($request_uri);

		// 无响应重试一次
		if (!$result AND $curl_retry)
		{
			return $this->get_user_info_by_oauth_openid($access_token, $openid, false);
		}

		if (!$result)
		{
			return false;
		}

		$result = json_decode($result, true);

		if ($result['errcode'] AND $result['errcode'] != 48001)
		{
			write_log('log/wxlogin/', '微信获取个人信息接口返回:' . json_encode($result));
		}

		return $result;
	}
}