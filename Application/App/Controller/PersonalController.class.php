<?php
namespace App\Controller;
use Think\Controller;
class PersonalController extends BaseController {

	/**
	 * 设置用户名
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 *
	 * @param  int param
	 * @method post
	 * @return  array
	 */
    public function set_user_name(){
//		$params = I('post.');

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$user_name = $params['user_name'];

		$user_model = D('Users');

		$update_result = $user_model->update_data(['id' => $this->user_id], ['user_name' => $user_name]);

		if($update_result === false){
			$this->result_return(null, 1, '更新用户名失败,请换一个用户名试试');
		}

		$this->result_return(['result' => 1]);
    }

	/**
	 * 设置基本资料
	 * @author cuirj
	 * @date   2019/9/27 下午6:27
	 * @url    app/personal/set_pro
	 * @method post
	 *
	 * @param  int param
	 * @return  array
	 */
    public function set_profile(){
//		$params = I('post.');

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$sex = $params['sex'] ? $params['sex'] : 1;
		$birthday = $params['birthday'];
		$company = $params['company'];
		$desc = $params['desc'];
		$address = $params['address'];
		$province = $params['province'];
		$city = $params['city'];
		$area = $params['area'];
		$user_name = $params['user_name'];

		$user_model = D('Users');

		//用户名不能重复
		if($user_name){
			$is_exist_user_name = $user_model->get_one(['user_name' => $user_name, 'id' => ['neq', $this->user_id]]);

			if($is_exist_user_name){
				$this->result_return(null, 1, '该用户名已经存在');
			}
		}

		$user_data = [
			'sex' => $sex,
			'birthday' => $birthday,
			'company' => $company,
			'desc' => $desc,
			'user_name' => $user_name,
		];
		$users_update_result = $user_model->update_data(['id' => $this->user_id], $user_data);

		if($users_update_result === false){
			$this->result_return(null, 1, '更新用户资料失败');
		}

		//更新地址信息
		$user_address_model = D('UserAddress');

		$address_info = $user_address_model->get_one(['user_id' => $this->user_id]);
		$address_data = [
			'province' => $province,
			'city' => $city,
			'area' => $area,
			'address' => $address,
		];

		if($address_data){
			//更新地址信息
			if($address_info){
				//更新
				$address_result = $user_address_model->update_data(['id' => $address_info['id']], $address_data);
			}else{
				//插入
				$address_data['user_id'] = $this->user_id;
				$address_result = $user_address_model->insert_one($address_data);
			}

			if($address_result === false){
				$this->result_return(null, 1, '更新地址信息失败');
			}
		}

		$this->result_return(['result' => 1]);

	}

	/**
	 * 设置头像
	 * @author cuirj
	 * @date   2019/9/27 下午7:00
	 * @url    app/personal/set_head_img/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function set_head_img(){
		if(!$_FILES){
			$this->result_return(null, 1, '请上传文件');
		}

		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     2048000 ;// 设置附件上传大小
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath  =      './Uploads/'; // 设置附件上传根目录
		$upload->savePath  =      ''; // 设置附件上传（子）目录
		$upload->saveName  =      $this->user_id . '_' . time(); // 设置附件上传（子）目录
		// 上传文件
		$info  =  $upload->upload();
		if(!$info) {// 上传错误提示错误信息
			$this->error($upload->getError());
		}else{// 上传成功 获取上传文件信息
			foreach($info as $file){
				$file_path =  $file['savepath'] . $file['savename'];

				$user_model = D('Users');

				$update_result = $user_model->update_data(['id' => $this->user_id], ['head_img' => $file_path]);

				if($update_result === false){
					$this->result_return(null, 1, '更新头像失败');
				}

				$this->result_return(['result' => 1]);
			}
		}
	}

	/**
	 * 设置在线状态
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 *
	 * @param  int param
	 * @method post
	 * @return  array
	 */
	public function set_online(){
//		$params = I('post.');

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$is_online = $params['is_online'];

		$user_model = D('Users');

		$update_result = $user_model->update_data(['id' => $this->user_id], ['is_online' => $is_online]);

		if($update_result === false){
			$this->result_return(null, 1, '设置在线状态失败');
		}

		$this->result_return(['result' => 1]);
	}

	/**
	 * 设置在线状态
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 *
	 * @param  int param
	 * @method post
	 * @return  array
	 */
	public function bind_account(){
		//		$params = I('post.');

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$weixin_account = $params['weixin_account'];
		$weibo_account = $params['weibo_account'];
		$alipay_account = $params['alipay_account'];
		$alipay_real_name = $params['alipay_real_name'];

		if(!$weixin_account && !$weibo_account && !$alipay_account){
			$this->result_return(null, 1, '请绑定认证账户哦');
		}
		$user_model = D('Users');

		$data['is_vefify'] = 1;

		if($alipay_account){
			if(!$alipay_real_name){
				$this->result_return(null, 1, '请填写支付宝绑定的真实姓名');
			}

			$data['alipay_account'] = $alipay_account;
			$data['alipay_real_name'] = $alipay_real_name;
		}

		if($weixin_account){
			$data['weixin_account'] = $weixin_account;
		}

		if($weibo_account){
			$data['weibo_account'] = $weibo_account;
		}

		$update_result = $user_model->update_data(['id' => $this->user_id], $data);

		if($update_result === false){
			$this->result_return(null, 1, '绑定账号失败');
		}

		$this->result_return(['result' => 1]);
	}

	/**
	 * 获取个人信息
	 * @author cuirj
	 * @date   2019/9/27 下午7:49
	 * @url    app/personal/get_user_info
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_user_info(){
		//实例化model
		$user_model = D('Users');

		$user_id = I('get.user_id');
		if(!$user_id || ($this->user_id == $user_id)){
			$user_id = $this->user_id;
			$is_self = 1;
		}

		$user_info = $user_model->get_one(['id' => $user_id]);

		if(!$user_info)
		{
			$this->result_return(null, 1, '未查询到个人信息');
		}

		//如果不是自己访问的主页,则需要将view+1
		if(!$is_self){
			$user_model->update_data(['id' => $user_id], ['view' => $user_info['view'] + 1]);
		}

		//返回个人信息和token信息
		$data = [
			'id' => $user_info['id'],
			'user_name' => $user_info['user_name'],
			'is_vefify' => $user_info['is_vefify'],
			'is_vip' => $user_info['is_vip'],
			'head_img' => $user_info['head_img'] ? UPLOAD_URL . $user_info['head_img'] : '',
			'sex' => $user_info['sex'],
			'is_online' => $user_info['is_online'],
			'company' => $user_info['company'],
			'desc' => $user_info['desc'],
			'birthday' => $user_info['birthday'],
			'weixin_account' => $user_info['weixin_account'],
			'weibo_account' => $user_info['weibo_account'],
			'alipay_account' => $user_info['alipay_account'],
			'alipay_real_name' => $user_info['alipay_real_name'],
			'view' => $user_info['view'],
			'type' => $user_info['type'],
			'is_weixin_verify' => $user_info['is_weixin_verify'],
			'is_alipay_verify' => $user_info['is_alipay_verify'],
			'is_weibo_verify' => $user_info['is_weibo_verify'],
		];

		// 如果是主态,则返回多余信息
		if($is_self){
			// 返回账户余额
			$data['account_balance'] = $user_info['account_balance'];
			$data['vip_expire_time'] = $user_info['vip_expire_time'];
			$data['mobile_number'] = $user_info['mobile_number'];
		}

		//地址信息
		$user_address_model = D('UserAddress');

		$address_info = $user_address_model->get_one(['user_id' => $user_id]);
		if($address_info){
			$data['province'] = $address_info['province'];
			$data['city'] = $address_info['city'];
			$data['area'] = $address_info['area'];
			$data['address'] = $address_info['address'];
		}

		// 显示动态
		$user_trends_model = D('UserTrends');

		$user_trends_one = $user_trends_model->get_one(['user_id' => $user_id], 'add_time desc');

		if($user_trends_one){
			//如果有动态,则显示第一条动态的前三章图片
			$img_list = array_map(function($v){
				return UPLOAD_URL . $v;
			}, explode(',', $user_trends_one['img_list']));

			$data['trends_img_list'] = array_slice($img_list, 0, 3);
		}else{
			$data['trends_img_list'] = [];
		}

		$this->result_return($data);
	}

	/**
	 * 发布动态
	 * @author cuirj
	 * @date   2019/10/3 下午6:40
	 * @url    app/personal/pulish_trends
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function pulish_trends(){

		if(!$_FILES){
			$this->result_return(null, 1, '请上传文件');
		}

//		$get_param = file_get_contents('php://input');
		$params = I('post.');

		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     2048000 ;// 设置附件上传大小
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath  =      './Uploads/'; // 设置附件上传根目录
		$upload->savePath  =      $this->user_id . '/' ; // 设置附件上传（子）目录
//		$upload->saveName  =      $this->user_id . '_' . time(); // 设置附件上传（子）目录
		// 上传文件
		$info  =  $upload->upload();
		if(!$info) {// 上传错误提示错误信息
			$this->error($upload->getError());
		}else{// 上传成功 获取上传文件信息

			$file_path = '';

			foreach($info as $file){
				$file_path .=  $file['savepath'] . $file['savename'] . ',';
			}

			if($file_path){
				$user_trends_model = D('UserTrends');
				$insert_data = [
					'user_id' => $this->user_id,
					'desc' => $params['desc'],
					'img_list' => trim($file_path, ','),
					'add_time' => time(),
				];
				$insert_result = $user_trends_model->insert_one($insert_data);
				if($insert_result === false){
					$this->result_return(null, 1, '发布动态失败');
				}

				$this->result_return(['result' => 1]);
			}else{
				$this->result_return(null, 1, '上传失败');
			}
		}
	}

	/**
	 * 获取某人动态
	 * @author cuirj
	 * @date   2019/10/3 下午7:45
	 * @url    app/personal/get_trend_list/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_trend_list(){
		$user_id = I('get.user_id');

		$page =  I('get.page') ? I('get.page') : 1;
		$page_size =  I('get.page_size') ? I('get.page_size') : 6;

		$limit = ($page - 1) * $page_size;

		$user_trends_model = D('UserTrends');

		$user_trends_list = $user_trends_model->get_list(['user_id' => $user_id], $limit. ',' . $page_size, 'add_time desc');

		if($user_trends_list){
			foreach($user_trends_list as $k => $v)
			{
				$img_list = array_map(function($v){
					return UPLOAD_URL . $v;
				}, explode(',', $v['img_list']));
				$user_trends_list[$k]['img_list'] = $img_list;
			}
		}

		$this->result_return($user_trends_list);
	}

	/**
	 * 获取某条动态
	 * @author cuirj
	 * @date   2019/10/3 下午8:44
	 * @url    app/personal/get_trend_by_id/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_trend_by_id(){
		$trend_id = I('get.trend_id');

		$user_trends_model = D('UserTrends');

		$user_trends_result = $user_trends_model->get_one(['id' => $trend_id]);

		if($user_trends_result){
			$img_list = array_map(function($v){
				return UPLOAD_URL . $v;
			}, explode(',', $user_trends_result['img_list']));

			$user_trends_result['img_list'] = $img_list;

			$this->result_return($user_trends_result);
		}else{

			$this->result_return(null, 1, '这条动态不存在');
		}
	}

	/**
	 * 删除某条动态
	 * @author cuirj
	 * @date   2019/10/3 下午8:44
	 * @url    app/personal/del_trend/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function del_trend(){
//		$trend_id = I('post.trend_id');

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$trend_id = $params['trend_id'];

		$user_trends_model = D('UserTrends');

		$user_trends_result = $user_trends_model->get_one(['id' => $trend_id]);

		if($user_trends_result){
			if($user_trends_result['user_id'] != $this->user_id){
				$this->result_return(null, 1, '不能删除别人的动态');
			}

			$img_list = explode(',', $user_trends_result['img_list']);

			//删除该条动态以及相应图片
			$user_trends_model->del_data(['id' => $trend_id]);

			//删除图片
			foreach($img_list as $i_k => $i_v){
				unlink('Uploads/' . $i_v);
			}

			$this->result_return(['result' => 1]);
		}else{

			$this->result_return(null, 1, '这条动态不存在');
		}
	}

	/**
	 * @description 退出登录
	 * @author      cuirj
	 * @date        2018/9/25 下午4:45
	 * @url         api/app/personal/logout/
	 * @method get
	 * @return  array
	 */
	public function logout()
	{
		if (!$this->user_id)
		{
			$this->result_return(null, 1, '获取登录状态失败');
		}

		$session_app_model = D('UsersSessionApp');

		$token_info = $session_app_model->get_one(['id' => $_SERVER['HTTP_TLHTOKEN']]);

		if ($token_info)
		{
			// 更新登录以后的剩余时间
			if ($token_info['user_id'] != $this->user_id)
			{
				$this->result_return(null, 1, '退出登录失败');
			}

			$session_app_model->update_data(['id' => $token_info['id']], ['lifetime' => -1]);

			$this->result_return(['result' => 1]);
		}
		else
		{
			$this->result_return(null, 1, '退出登录失败');
		}
	}

	/**
	 * vip套餐列表信息
	 * @date   2019/11/6 下午8:55
	 * @url    app/personal/get_vip_package_info/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_vip_package_info(){
		$vip_package_info = C('source_type_arr');

		foreach($vip_package_info as $k => $v){
			$vip_package_info[$k]['vip_type_id'] = $k;
		}

		$this->result_return(array_values($vip_package_info));
	}

	/**
	 * 获取今天发布的需求和预约的技能总数
	 * @date   2019/11/6 下午9:20
	 * @url    app/personal/get_today_demand_skill_count/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_today_demand_skill_count(){
		$user_id = $this->user_id;
		$vip_expire_time = $this->user_info['vip_expire_time'];

		if($vip_expire_time < time()){
			$is_vip = 0;
		}else{
			$is_vip = 1;
		}

		$start_time = strtotime(date('Y-m-d', time()));
		$end_time = $start_time + 24 * 3600;
		//查看今天发布了多少条需求/技能
		$skill_model = D('UserSkill');
		$demand_model = D('UserDemand');

		$skill_where = [
			'add_time' => [['gt', $start_time], ['lt', $end_time]],
			'user_id' => $user_id,
		];

		$demand_where = [
			'status' => ['not in', '4,5'],
			'add_time' => [['gt', $start_time], ['lt', $end_time]],
			'user_id' => $user_id,
		];
		$skill_count = $skill_model->get_pulish_count($skill_where);
		$demand_count = $demand_model->get_pulish_count($demand_where);

		$max_limit = C('max_publish_limit');
		// 发布需求或者技能 1天限制5次
		if($skill_count < $max_limit['skill']){
			$is_skill_pulish = 1;
		}

		if($demand_count < $max_limit['demand']){
			$is_demand_pulish = 1;

			//如果是会员,则每天能免费发1条需求
			if($demand_count < 1 && $is_vip){
				$is_vip_demand_pulish_free = 1;
			}
		}

		// 如果是会员,则每天只能免费预约3条
		if($is_vip){
			$reserve_model = D('SkillReserve');
			$reserve_where = [
				'status' => ['NEQ', '1'],
				'add_time' => [['gt', $start_time], ['lt', $end_time]],
				'user_id' => $user_id,
			];

			$reserve_count = $reserve_model->get_reserve_count($reserve_where);

			if($reserve_count < $max_limit['vip_free_reserve'] && $is_vip){
				$is_vip_reserve_free = 1;
			}
		}

		$data = [
			'is_skill_pulish' => intval($is_skill_pulish),
			'is_vip_demand_pulish_free' => intval($is_vip_demand_pulish_free),
			'is_vip_reserve_free' => intval($is_vip_reserve_free),
			'is_demand_pulish' => intval($is_demand_pulish),
		];

		$this->result_return($data);
	}

	/**
	 * 获取该账号下邀请人数
	 * @author cuirj
	 * @date   2019/11/8 下午10:54
	 * @url    app/personal/get_invite_users
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_invite_users(){
		$user_id = $this->user_id;
		$page = I('get.page') ? I('get.page') : 1;
		$page_size = I('get.page_size') ? I('get.page_size') : 3;

		$limit = ($page - 1) * $page_size;

		$user_model = D('Users');

		$where = ['invite_user_id' => $user_id];
		$user_list = $user_model->get_list($where, $limit. ',' . $page_size, 'vip_expire_time desc,add_time desc');

		$invite_count = $user_model->get_condition_count($where);

		$data = [];
		foreach($user_list as $k => $v){
			$data_tmp = [
				'add_time' => $v['add_time']
			];

			$data_tmp['head_img'] = $v['head_img'] ? UPLOAD_URL . $v['head_img'] : '';

			if($v['vip_expire_time'] > time()){
				$data_tmp['is_vip'] = 1;
			}else{
				$data_tmp['is_vip'] = 0;
			}

			$data_tmp['user_name'] = $v['user_name'];

			$data[] = $data_tmp;
		}

		$this->result_return(['invite_count' => (int)$invite_count, 'invite_user_list' => $data]);

	}
}