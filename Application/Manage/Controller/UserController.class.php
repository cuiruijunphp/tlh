<?php
namespace Manage\Controller;
use Think\Controller;
class UserController extends BaseController {

	public function __construct(){
		parent::__construct();
		$user_type_arr = [
			1 => '普通用户',
			2 => '认证用户',
			3 => '代理',
		];

		$data['user_type_arr'] = $user_type_arr;
		$this->assign($data);
	}

	public function index(){

		$user_model = D('Users');

		$page = I('get.p');
		$user_list = $user_model->get_page_list(null, $page);
		$user_count = $user_model->get_count();
		$data['list'] = $user_list;
		// 加上分页
		$data['page'] = $this->page_new($user_count);
		$this->assign($data);
		$this->display();
    }

    public function edit(){
    	$this->display();
	}

	public function add(){
		$this->display();
	}

	public function add_deal(){
		//处理新增代理
		$params = I('post.');

		$insert_user_data = [
			'user_name' => $params['user_name'],
			'password' => compile_password($params['password']),
			'type' => 3,
			'mobile_number' => $params['mobile_number'],
		];


		//插入user表
		$user_model = D('Users');

		$users_insert_result = $user_model->insert_one($insert_user_data);
		if($users_insert_result === false){
			$this->result_return(null, 500, '添加代理商失败');
		}

		//更新地址信息
		$user_address_model = D('UserAddress');

		$address_data = [
			'province' => $params['province'],
			'city' => $params['city'],
			'area' => $params['area'],
			'address' => $params['address'],
			'user_id' => $users_insert_result,
			'user_type' => 3,
		];

		$address_result = $user_address_model->insert_one($address_data);

		if($address_result === false){
			$this->result_return(null, 500, '更新地址信息失败');
		}else{
			$this->result_return(['result' => 1]);
		}
	}

	public function logout()
	{
		if (!$this->user_id)
		{
			$this->result_return(null, 500, '获取登录状态失败');
		}

		session('user_id', null);
		cookie('user_id', null);

		$this->redirect(U(('Manage/login/login')));
	}
}