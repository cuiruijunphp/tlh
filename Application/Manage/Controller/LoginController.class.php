<?php
namespace Manage\Controller;
use Think\Controller;
use App\Controller\CommonController;

class LoginController extends CommonController {

    public function login(){

		if (IS_POST) {
			$params = I('post.');
			if (empty($params['user_name']) || empty($params['password'])) {
				$this->result_return(null , 500, '用户名或密码不得为空！');
			}

			$admin_model = D('Admin');
			$user_info = $admin_model->get_one(['user_name' => $params['user_name'], 'password' => compile_password($params['password'])]);

			if (!$user_info) {
				$this->result_return(null, 500, '用户名或密码不正确');
			}

			session('user_id', $user_info['id']);

			$this->result_return(['result' => 1]);
		}

		$this->display('Admin/login');
    }
}