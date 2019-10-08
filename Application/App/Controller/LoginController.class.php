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
    }

	/**
	 * 发送验证码
	 * @author cuirj
	 * @date   2019/9/27 下午12:19
	 * @url    app/login/send_
	 * @method get
	 *
	 * @param  int param
	 *             return  array
	 */
    public function send_code(){

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

		$user_info = $user_model->get_one(['mobile_number' => $mobile_number, 'password' => md5($password)]);

		if(!$user_info)
		{
			$this->result_return(null, 500, '用户名或者密码错误');
		}

		//返回个人信息和token信息
		$data = [
			'user_name' => $user_info['user_name'],
			'is_vefify' => $user_info['is_vefify'],
			'is_vip' => $user_info['is_vip'],
			'head_img' => $user_info['head_img'],
			'sex' => $user_info['sex'],
			'is_online' => $user_info['is_online'],
			'company' => $user_info['company'],
			'desc' => $user_info['desc'],
			'birthday' => $user_info['birthday'],
		];

		$token = '';

		//用uuid换token
		//如果有uuid,则直接取出来,如果没有uuid,则先存到数据库中,过期时间设置为30天
		$session_app_model = D('UsersSessionApp');
		$token_info = $session_app_model->get_one(['uuid' => $params['uuid']]);

		if($token_info){
			//更新登录时间
			$session_app_model->update_data(['uuid' => $params['uuid']], ['modified' => time()]);
		}else{
			//插入token信息
			$token_info = $session_app_model->insert_user_session_app($params['uuid'], $password, $user_info['id']);
		}

		$data['token'] = $token_info['id'];

		$this->result_return($data);
	}
}