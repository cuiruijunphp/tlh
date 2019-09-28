<?php
namespace App\Controller;
use Think\Controller;
class BaseController extends CommonController {

	protected $user_info;
	protected $user_id;
	/**
	 * 接口登录验证
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 *
	 * @param  int param
	 * @return  array
	 */
    public function __construct(){
		$token_id = $_SERVER['HTTP_TLHTOKEN'];

		$session_app_model = D('UsersSessionApp');
		$user_model = D('Users');

		$token_info = $session_app_model->get_one(['id' => $token_id]);
		if(!$token_info)
		{
			$this->result_return(null, 401, '请先登录');
		}

		//获取当前用户的个人信息
		$this->user_info = $user_model->get_one(['id' => $token_info['user_id']]);
		$this->user_id = $token_info['user_id'];
    }
}