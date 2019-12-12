<?php
namespace App\Controller;
use Think\Controller;
class VerifyController extends BaseController {

	/**
	 * 微信认证
	 * @date   2019/12/11 下午6:14
	 * @url    app/verify/weixin_verify
	 * @method post
	 *
	 * @param  string code
	 * @return  array
	 */
	public function weixin_verify(){
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$code = $params['code'];

		$user_weixin_model = D('UserWeixin');

		//加载微信配置
		$weixin_config = C('WXAPP_PAY_CONFIG');

		$response = $user_weixin_model->get_sns_access_token_by_authorization_code($code, $weixin_config['appid'], $weixin_config['appkey']);

		if (!$response)
		{
			$this->result_return(null, 1, '与微信通信超时，请稍后再试');
		}

		if ($response['errcode'])
		{
			$this->result_return(null, 1, $response['errcode'] . $response['errmsg']);
		}

		//查询是否已经认证过,如果已经认证过,就报错

		//access_token请求用户信息
		$user_info_response = $user_weixin_model->get_user_info_by_oauth_openid($response['access_token'], $response['openid']);

		if (!$user_info_response)
		{
			$this->result_return(null, 1, '获取用户信息失败');
		}

		if ($user_info_response['errcode'])
		{
			$this->result_return(null, 1, $user_info_response['errcode'] . $user_info_response['errmsg']);
		}

		//插入数据库,更新认证信息
		$insert_data = [
			'user_id' => $this->user_id,
			'openid' => $response['openid'],
			'access_token' => $response['access_token'],
			'headimgurl' => $user_info_response['headimgurl'],
			'nickname' => $user_info_response['nickname'],
			'sex' => $user_info_response['sex'],
			'province' => $user_info_response['province'],
			'city' => $user_info_response['city'],
			'country' => $user_info_response['country'],
			'unionid' => $user_info_response['unionid'],
		];

		$insert_result = $user_weixin_model->insert_one($insert_data);

		if($insert_result){
			// 更新用户表
			$user_model = D('User');

			$user_model->update_data(['id' => $this->user_id], ['is_weixin_verify' => 1, 'is_vefify' => 1]);
		}

		$this->result_return(['result' => 1]);
	}

	/**
	 * 支付宝认证
	 * @date   2019/12/11 下午10:20
	 * @url    app/verify/alipay_verify/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function alipay_verify(){
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$code = $params['code'];

		$user_alipay_model = D('UserAlipay');

		$response = $user_alipay_model->get_access_token_by_code($code);

		if (!$response)
		{
			$this->result_return(null, 1, '与支付宝通信超时，请稍后再试');
		}

		if ($response['alipay_system_oauth_token_response']['code'])
		{
			$this->result_return(null, 1, $response['alipay_system_oauth_token_response']['code'] . $response['alipay_system_oauth_token_response']['sub_msg']);
		}

		//查询是否已经认证过,如果已经认证过,就报错

		//access_token请求用户信息
		$user_info_response = $user_alipay_model->get_user_info_by_access_token($response['alipay_system_oauth_token_response']['access_token']);

		if (!$user_info_response)
		{
			$this->result_return(null, 1, '获取用户信息失败');
		}

		if ($user_info_response['alipay_user_info_share_response']['code'] != 10000)
		{
			$this->result_return(null, 1, $user_info_response['alipay_user_info_share_response']['code'] . $user_info_response['alipay_user_info_share_response']['sub_msg']);
		}

		//插入数据库,更新认证信息
		$user_info_response_tmp = $user_info_response['alipay_user_info_share_response'];
		$insert_data = [
			'user_id' => $this->user_id,
			'alipay_user_id' => $user_info_response_tmp['user_id'],
			'avatar' => $user_info_response_tmp['avatar'],
			'province' => $user_info_response_tmp['province'],
			'city' => $user_info_response_tmp['city'],
			'nick_name' => $user_info_response_tmp['nick_name'],
			'user_type' => $user_info_response_tmp['user_type'],
			'user_status' => $user_info_response_tmp['user_status'],
			'is_certified' => $user_info_response_tmp['is_certified'],
			'gender' => $user_info_response_tmp['gender'],
		];

		$insert_result = $user_alipay_model->insert_one($insert_data);

//		if($insert_result){
//			// 更新用户表
//			$user_model = D('User');
//
//			$user_model->update_data(['id' => $this->user_id], ['is_weixin_verify' => 1, 'is_vefify' => 1]);
//		}

		$this->result_return(['result' => 1]);
	}
}