<?php
namespace App\Controller;
use Think\Controller;
class LoginController extends CommonController {

	/**
	 * 注册
	 * @author cuirj
	 * @date   2019/9/27 下午12:16
	 * @url    app/login/register/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
    public function register(){
		//接收参数
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$phone_number = $params['mobile_number'];
		$code = $params['code'];
		$password = $params['password'];
		$invite_mobile_number = $params['invite_mobile_number'];

		$code_model = D('Code');
		$send_result = $code_model->get_one(['mobile_number' => $phone_number, 'type' => 'register'], 'add_time desc');

		if(!$send_result){
			$this->result_return(null, 1, '验证码错误');
		}

		if($send_result['code'] != $code){
			$this->result_return(null, 1, '验证码错误');
		}

		if($send_result['add_time'] + 30 * 60 < time()){
			$this->result_return(null, 1, '验证码已经过期,请重新发送');
		}

		$user_model = D('Users');

		// 查询邀请者是否存在
		$invite_info = $user_model->get_one(['mobile_number' => $invite_mobile_number]);
		if($invite_mobile_number && !$invite_info){
			$this->result_return(null, 1, '请填入正确的邀请人手机号');
		}

		// 插入数据库中
		//先查看手机号是否被注册
		$is_exist_phone = $user_model->get_one(['mobile_number' => $phone_number]);
		if($is_exist_phone){
			$this->result_return(null, 1, '手机号已经被注册了');
		}

		$insert_user_data = [
			'mobile_number' => $phone_number,
			'password' => compile_password($password),
			'invite_user_id' => (int)$invite_info['id'],
		];
		$insert_result = $user_model->insert_one($insert_user_data);

		if($insert_result === false){
			$this->result_return(null, 1, '注册失败');
		}

		$this->result_return(['result' => 1]);
    }

	/**
	 * 发送验证码
	 * @author cuirj
	 * @date   2019/9/27 下午12:19
	 * @url    app/login/send_code
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
    public function send_code(){
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$type = $params['type'];
		$phone_number = $params['mobile_number'];

		// type对应的短信模板id
		$template_arr = [
			'register' => C('RegisterTemplateCode'),
			'login' => C('LoginTemplateCode'),
			're_password' => C('PasswordTemplateCode'),
		];

		$code = rand(100000, 999999);
//		$code = 123456;

		$code_model = D('Code');
		$send_result = $code_model->get_sms_code($phone_number, $template_arr[$type], $code);
//		$send_result = 1;

		if($send_result['Code'] == 'OK'){
			//发送成功,往数据库里写入数据
			$insert_data = [
				'mobile_number' => $phone_number,
				'code' => $code,
				'type' => $type,
				'add_time' => time(),
			];
			$insert_result = $code_model->insert_one($insert_data);
		}else{
			$this->result_return(null, 1, $send_result['Message']);
		}

		$this->result_return(['result' => 1]);
	}

	/**
	 * 验证码验证
	 * @author cuirj
	 * @date   2019/9/27 下午12:20
	 * @url    app/login/valid_code
	 * @method get
	 *
	 * @param  int param
	 *             return  array
	 */
	public function valid_code(){
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$type = $params['type'];
		$phone_number = $params['mobile_number'];
		$code = $params['code'];

		$code_model = D('Code');
		$send_result = $code_model->get_one(['mobile_number' => $phone_number, 'type' => $type], 'add_time desc');

		if(!$send_result){
			$this->result_return(null, 1, '验证码错误');
		}

		if($send_result['code'] != $code){
			$this->result_return(null, 1, '验证码错误');
		}

		if($send_result['add_time'] + 30 * 60 < time()){
			$this->result_return(null, 1, '验证码已经过期,请重新发送');
		}

		//是否判断过期时间

		$this->result_return(['result' => 1]);
	}

	/**
	 * 登录
	 * @author cuirj
	 * @date   2019/9/27 下午12:22
	 * @url    app/login/login
	 * @method post
	 *
	 * @param  user_name 用户名
	 * @param  password 密码,md5加密
	 * @param  user_name 用户名
	 * @return  array
	 */
	public function login(){
//		$params = I('post.');

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$mobile_number = $params['mobile_number'];
		$password = $params['password'];

		//实例化model
		$user_model = D('Users');

		$user_info = $user_model->get_one(['mobile_number' => $mobile_number, 'password' => compile_password($password)]);

		if(!$user_info)
		{
			$this->result_return(null, 1, '用户名或者密码错误');
		}

		//返回个人信息和token信息
		$data = [
			'user_name' => $user_info['user_name'],
			'is_vefify' => $user_info['is_vefify'],
			'is_vip' => $user_info['is_vip'],
			'vip_expire_time' => $user_info['vip_expire_time'],
			'type' => $user_info['type'],
			'head_img' => $user_info['head_img'] ? UPLOAD_URL . $user_info['head_img'] : '',
			'sex' => $user_info['sex'],
			'is_online' => $user_info['is_online'],
			'company' => $user_info['company'],
			'desc' => $user_info['desc'],
			'birthday' => $user_info['birthday'],
			'weixin_account' => $user_info['weixin_account'],
			'id' => $user_info['id'],
			'weibo_account' => $user_info['weibo_account'],
			'alipay_account' => $user_info['alipay_account'],
			'alipay_real_name' => $user_info['alipay_real_name'],
			'account_balance' => $user_info['account_balance'],
		];

		$token = '';

		//地址信息
		$user_address_model = D('UserAddress');

		$address_info = $user_address_model->get_one(['user_id' => $user_info['id']]);
		if($address_info){
			$data['province'] = $address_info['province'];
			$data['city'] = $address_info['city'];
			$data['area'] = $address_info['area'];
			$data['address'] = $address_info['address'];
		}

		//用uuid换token
		//如果有uuid,则直接取出来,如果没有uuid,则先存到数据库中,过期时间设置为30天
		$session_app_model = D('UsersSessionApp');
		$token_info = $session_app_model->get_one(['uuid' => $params['uuid']]);

		if($token_info){
			//更新登录时间
			$session_app_model->update_data(['uuid' => $params['uuid']], ['modified' => time(), 'password' => compile_password($password), 'lifetime' => 30 * 24 * 3600]);
		}else{
			//插入token信息
			$token_info = $session_app_model->insert_user_session_app($params['uuid'], compile_password($password), $user_info['id']);
		}

		$data['token'] = $token_info['id'];

		$this->result_return($data);
	}

	/**
	 * 找回密码
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 * @url    app/login/forget_password/
	 * @param  int param
	 * @method post
	 * @return  array
	 */
	public function forget_password(){
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$phone_number = $params['mobile_number'];
		$code = $params['code'];
		$password = $params['password'];

		$user_model = D('Users');
		//先查看手机号是否被注册
		$is_exist_phone = $user_model->get_one(['mobile_number' => $phone_number]);
		if(!$is_exist_phone){
			$this->result_return(null, 1, '请您先注册成为会员');
		}

		$code_model = D('Code');
		$send_result = $code_model->get_one(['mobile_number' => $phone_number, 'type' => 're_password'], 'add_time desc');

		//先验证验证码
		if(!$send_result){
			$this->result_return(null, 1, '验证码错误');
		}

		if($send_result['code'] != $code){
			$this->result_return(null, 1, '验证码错误');
		}

		if($send_result['add_time'] + 30 * 60 < time()){
			$this->result_return(null, 1, '验证码已经过期,请重新发送');
		}

		$update_result = $user_model->update_data(['id' => $is_exist_phone['id']], ['password' => compile_password($password)]);

		if($update_result === false){
			$this->result_return(null, 1, '找回密码失败');
		}

		// 这个时候要更新session表中的密码
		$session_app_model = D('UsersSessionApp');
		$token_info = $session_app_model->get_one(['id' => $_SERVER['HTTP_TLHTOKEN']]);

		//更新密码
		$session_app_model->update_data(['id' => $_SERVER['HTTP_TLHTOKEN']], ['password' => compile_password($password)]);

		$this->result_return(['result' => 1]);
	}
}