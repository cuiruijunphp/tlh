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

		$params = I('get.');

		$page = I('get.p');

		// 查询条件
		$province = $params['province'];
		$city = $params['city'];
		$area = $params['area'];

		$begin_date = strtotime($params['begin_date']);
		$end_date = strtotime($params['end_date']);

		$keyword = $params['keyword'];

		$type = $params['type'];

		if($begin_date && $end_date){
			$where['u.add_time']= [
				['gt', $begin_date],
				['lt', $end_date],
			];
		}

		if($type){
			$where['u.type']= $type;
		}

		if($province){
			$where['s.province'] = $province;
		}

		if($city){
			$where['s.city'] = $city;
		}

		if($area){
			$where['s.area'] = $area;
		}

		$user_list = $user_model->get_user_info_by_where($where, $page);
		$user_count = $user_model->get_user_count_by_where($where);
		$data['list'] = $user_list;
		// 加上分页
		$data['page'] = $this->page_new($user_count);

		// 加上查询参数
		$data['province'] = $province ? $province : '';
		$data['city'] = $city ? $city : '';
		$data['area'] = $area ? $area : '';
		$data['begin_date'] = $params['begin_date'];
		$data['end_date'] = $params['end_date'];
		$data['keyword'] = $keyword;
		$data['type'] = $type;

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
			'sex' => $params['sex'],
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

	/*
	 * 查看代理注册的会员
	 */
	public function proxy(){

		$user_model = D('Users');

		$params = I('get.');

		$page = I('get.p');

		$id = $params['id'];

		$user_address_model = D('UserAddress');
		$user_address_info = $user_address_model->get_one(['user_id' => $id]);

		// 查询条件
		$province = $user_address_info['province'];
		$city = $user_address_info['city'];
		$area = $user_address_info['area'];

		$begin_date = strtotime($params['begin_date']);
		$end_date = strtotime($params['end_date']);

		$type = $params['type'];

		if($begin_date && $end_date){
			$where['u.add_time']= [
				['gt', $begin_date],
				['lt', $end_date],
			];
		}

		$where['u.type']= 1;

		if($province){
			$where['s.province'] = $province;
		}

		if($city){
			$where['s.city'] = $city;
		}

		if($area){
			$where['s.area'] = $area;
		}

		$user_list = $user_model->get_user_info_by_where($where, $page);
		$user_count = $user_model->get_user_count_by_where($where);
		$data['list'] = $user_list;
		// 加上分页
		$data['page'] = $this->page_new($user_count);

		// 加上查询参数
		$data['begin_date'] = $params['begin_date'];
		$data['end_date'] = $params['end_date'];
		$data['id'] = $params['id'];

		$this->assign($data);
		$this->display();
	}

	/**
	 * 导出数据
	 * @author cuirj
	 * @date   2019/5/10 下午3:43
	 * @method get
	 *
	 * @param  int param
	 */
	public function import_data(){
		$user_model = D('Users');

		$params = I('get.');

		$page = I('get.p');

		// 查询条件
		$province = $params['province'];
		$city = $params['city'];
		$area = $params['area'];

		$begin_date = strtotime($params['begin_date']);
		$end_date = strtotime($params['end_date']);

		$type = $params['type'];

		if($begin_date && $end_date){
			$where['u.add_time']= [
				['gt', $begin_date],
				['lt', $end_date],
			];
		}

		if($type){
			$where['u.type']= $type;
		}

		if($province){
			$where['s.province'] = $province;
		}

		if($city){
			$where['s.city'] = $city;
		}

		if($area){
			$where['s.area'] = $area;
		}

		$user_list = $user_model->get_user_info_by_where($where, null, null);
		$user_count = $user_model->get_user_count_by_where($where);

		$company_export = [];
		foreach($user_list as $k => $v){
			$company_export[] = [
				'id' => $v['id'],
				'user_name' => $v['user_name'],
				'type' => $v['type']== 1 ? '普通会员' : '代理',
				'mobile_number' => $v['mobile_number'],
				'is_vip' => $v['is_vip'] ? '是' : '否',
				'vip_expire_time' => $v['vip_expire_time'] ? date('Y-m-d H:i:s', $v['vip_expire_time']) : '',
				'is_vefify' => $v['is_vefify'] ? '是' : '否',
				'sex' => $v['sex'] == 1 ? '男' : '女',
				'add_time' => date('Y-m-d H:i:s', $v['add_time']),
				'address' => $v['province'] . $v['city'] . $v['area'] . $v['address'],
			];
		}

		$xlsCell = array(
			array('id', 'ID'),
			array('user_name', '用户名'),
			array('type', '账户类型'),
			array('mobile_number', '手机号'),
			array('is_vip', '是否vip'),
			array('vip_expire_time', 'vip过期时间'),
			array('is_vefify', '是否认证'),
			array('sex', '性别'),
			array('add_time', '注册时间'),
			array('address', '地址'),
		);

		$this->exportExcel('注册用户',$xlsCell,$company_export);
	}

	/**
	 * 导出代理数据
	 * @author cuirj
	 * @date   2019/5/10 下午3:43
	 * @method get
	 *
	 * @param  int param
	 */
	public function import_proxy_data(){
		$user_model = D('Users');

		$params = I('get.');

		$id = $params['id'];

		$user_address_model = D('UserAddress');
		$user_address_info = $user_address_model->get_one(['user_id' => $id]);

		// 查询条件
		$province = $user_address_info['province'];
		$city = $user_address_info['city'];
		$area = $user_address_info['area'];

		$begin_date = strtotime($params['begin_date']);
		$end_date = strtotime($params['end_date']);

		$type = $params['type'];

		if($begin_date && $end_date){
			$where['u.add_time']= [
				['gt', $begin_date],
				['lt', $end_date],
			];
		}

		$where['u.type']= 1;

		if($province){
			$where['s.province'] = $province;
		}

		if($city){
			$where['s.city'] = $city;
		}

		if($area){
			$where['s.area'] = $area;
		}

		$user_list = $user_model->get_user_info_by_where($where, null, null);

		$company_export = [];
		foreach($user_list as $k => $v){
			$company_export[] = [
				'id' => $v['id'],
				'user_name' => $v['user_name'],
				'type' => $v['type']== 1 ? '普通会员' : '代理',
				'mobile_number' => $v['mobile_number'],
				'is_vip' => $v['is_vip'] ? '是' : '否',
				'vip_expire_time' => $v['vip_expire_time'] ? date('Y-m-d H:i:s', $v['vip_expire_time']) : '',
				'is_vefify' => $v['is_vefify'] ? '是' : '否',
				'sex' => $v['sex'] == 1 ? '男' : '女',
				'add_time' => date('Y-m-d H:i:s', $v['add_time']),
				'address' => $v['province'] . $v['city'] . $v['area'] . $v['address'],
			];
		}

		$xlsCell = array(
			array('id', 'ID'),
			array('user_name', '用户名'),
			array('type', '账户类型'),
			array('mobile_number', '手机号'),
			array('is_vip', '是否vip'),
			array('vip_expire_time', 'vip过期时间'),
			array('is_vefify', '是否认证'),
			array('sex', '性别'),
			array('add_time', '注册时间'),
			array('address', '地址'),
		);

		$this->exportExcel('代理用户',$xlsCell,$company_export);
	}
}