<?php
namespace App\Controller;
use Think\Controller;
class SkillController extends BaseController {

	/**
	 * 获取技能类型列表
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 * @url    /app/skill/get_skill_type_list/
	 * @param  int param
	 * @method post
	 * @return  array
	 */
    public function get_skill_type_list(){

		$skill_type_model = D('SkillType');
		$skill_type_result = $skill_type_model->get_list(['is_show' => 1]);

		foreach($skill_type_result as $k => $v){
			$skill_type_result[$k]['img'] = UPLOAD_URL . $v['img'];
		}

		$this->result_return($skill_type_result);
    }

	/**
	 * 获取当前登录用户的消息列表
	 * @author cuirj
	 * @date   2019/9/27 下午6:27
	 * @url    app/skill/publish_skill/
	 * @method post
	 *
	 * @param  int page
	 * @param  int page_size
	 * @return  array
	 */
    public function publish_skill(){
		$params = I('post.');

		$skill_name = $params['skill_name'];
		$type_id = $params['type_id'];
		$desc = $params['desc'];
		$price = $params['price'];
		$superiority = $params['superiority'];
		$mode = $params['mode'];
		$good_at = $params['good_at'];
		$longitude = $params['longitude'];
		$latitude = $params['latitude'];


		// 不存在的技能则不插入数据库中
		$skill_type_model = D('SkillType');
		$skill_type_info = $skill_type_model->get_one(['id' => $type_id]);
		if(!$skill_type_info || !$skill_type_info['is_show']){
			$this->result_return(null, 1, '技能类型有误');
		}

		if(!$this->user_info['is_vefify']){
			$this->result_return(null, 1, '仅限认证用户发布');
		}

		//上传技能图片
		if(!$_FILES){
			$this->result_return(null, 1, '请上传技能图片');
		}

		//同一种技能类型的只能发布一个
		$skill_mode = D('UserSkill');
		$is_exist_type_skill = $skill_mode->get_one(['user_id' => $this->user_id, 'type_id' => $type_id]);

		if($is_exist_type_skill){
			$this->result_return(null, 1, '同一种技能类型只能发布一个技能');
		}

		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     2048000 ;// 设置附件上传大小
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath  =      './Uploads/'; // 设置附件上传根目录
		$upload->savePath  =      $this->user_id . '/' ; // 设置附件上传（子）目录
		//		$upload->saveName  =      $this->user_id . '_' . time(); // 设置附件上传（子）目录
		// 上传文件
		$info  =  $upload->upload();
		if(!$info) {// 上传错误提示错误信息
			$this->result_return(null, 1, $upload->getError());
		}else{// 上传成功 获取上传文件信息
			foreach($info as $file){
				$file_path = $file['savepath'] . $file['savename'];
			}
		}

		$skill_insert_data = [
			'skill_name' => $skill_name,
			'type_id' => $type_id,
			'desc' => $desc,
			'price' => $price,
			'superiority' => $superiority,
			'mode' => $mode,
			'good_at' => $good_at,
			'img' => $file_path,
			'user_id' => $this->user_id,
			'longitude' => $longitude,
			'latitude' => $latitude,
		];

		//判断今天发了几次
		$start_time = strtotime(date('Y-m-d', time()));
		$end_time = $start_time + 24 * 3600;
		//查看今天发布了多少条需求/技能
		$skill_where = [
			'add_time' => [['gt', $start_time], ['lt', $end_time]],
			'user_id' => $this->user_id,
		];

		$skill_count = $skill_mode->get_pulish_count($skill_where);

		$max_limit = C('max_publish_limit');

		if($skill_count > ($max_limit['skill'] - 1)){
			$this->result_return(null, 1, '你今天已经发布了' . $max_limit['skill'] . '条,请明天再来吧');
		}

		$insert_result = $skill_mode->insert_one($skill_insert_data);

		if($insert_result === false){
			$this->result_return(null, 1, '发布技能失败');
		}

		$this->result_return(['result' => 1]);
	}

	/**
	 * 获取技能列表
	 * @author cuirj
	 * @date   2019/10/11 下午6:14
	 * @url    app/skill/get_skill_list/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_skill_list(){
		$user_id = I('get.user_id');
		$page = I('get.page');
		$page_size = I('get.page_size');

		if(!$user_id){
			// 如果是在自己的列表里,则需要给个标识
			$user_id = $this->user_id;
			$is_self = 1;
		}elseif($user_id == $this->user_id){
			$is_self = 1;
		}

		$where = [
			'user_id' => $user_id,
		];

		if(!$is_self){
			$where['status'] = 1;
		}

		$skill_mode = D('UserSkill');
		$skill_reserve_mode = D('SkillReserve');
		$skill_list = $skill_mode->get_skill_list($where, $page, $page_size);

		if($skill_list){
			foreach($skill_list  as $k => $v){
				$skill_list[$k]['img'] = UPLOAD_URL . $v['img'];

				// 每个技能已经预约的
				if($v['reservation_count'] > 0){
					$skill_reserve_list = $skill_reserve_mode->get_skill_reserve_by_skill_id($v['id']);
					$user_info_img = array_column($skill_reserve_list, 'head_img');

					$skill_list[$k]['reserve_head_img'] = array_map(function($v)
					{
						return UPLOAD_URL . $v;
					}, $user_info_img);
				}else{
					$skill_list[$k]['reserve_head_img'] = [];
				}
			}
		}

		$this->result_return($skill_list);
	}

	/**
	 * 获取技能详细信息
	 * @author cuirj
	 * @date   2019/10/11 下午6:14
	 * @url    app/skill/get_skill_info/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_skill_info(){
		$skill_id = I('get.skill_id');
		$user_id = I('get.user_id');

		if(!$user_id){
			$user_id = $this->user_id;
		}

		if($user_id && $user_id != $this->user_id){
			$is_other = 1;
		}

		$skill_mode = D('UserSkill');
		$skill_info = $skill_mode->get_one(['id' => $skill_id]);

		if(!$skill_info){
			$this->result_return(null, 1, '没有找到该项技能');
		}

		if($is_other && $skill_info['status'] != 1){
			$this->result_return(null, 1, '该项技能未审核通过或者待审核');
		}

		if(!$is_other){
			//如果是自己的,则把预约信息放出来
			$skill_reserve_model = D('SkillReserve');
			$skill_reserve_list = $skill_reserve_model->get_skill_reserve_by_skill_id($skill_id, null, null, '0,2,3,4');

			foreach($skill_reserve_list as $s_k => $s_v){
				$skill_reserve_list[$s_k]['head_img'] = UPLOAD_URL . $s_v['head_img'];
			}
		}

		$skill_info['img'] = UPLOAD_URL . $skill_info['img'];
		$skill_info['skill_reserve'] = $skill_reserve_list ? $skill_reserve_list : [];

		$this->result_return($skill_info);
	}

	/**
	 * 获取预约详情
	 * @date   2019/10/31 下午5:31
	 * @url    app/skill/get_skill_reserve
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_skill_reserve(){

		$reserve_id = I('get.reserve_id');

		$skill_reserve_model = D('SkillReserve');
		$order_model = D('Order');

		$skill_info = $skill_reserve_model->get_reserve_info_by_id($reserve_id);

		$skill_info['head_img'] = UPLOAD_URL . $skill_info['head_img'];

		$order_info = $order_model->get_one(['source_id' => $reserve_id, 'source_type' => 3]);

		$skill_info['earnest_money'] = $order_info ? $order_info['price'] : '0';

		$this->result_return($skill_info);
	}
	/**
	 * 预约
	 * @author cuirj
	 * @date   2019/10/31 下午2:23
	 * @url   app/skill/skill_reserve/
	 * @method post
	 *
	 * @param  int skill_id
	 * @return  array
	 */
	public function skill_reserve(){
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$skill_id = $params['skill_id'];

		$insert_data = [
			'user_id' => $this->user_id,
			'skill_id' => $skill_id,
			'status' => 0,
		];

		$skill_reserve_model = D('SkillReserve');
		$skill_model = D('UserSkill');

		//判断是否存在
		$skill_info = $skill_model->get_one(['id' => $skill_id]);
		if(!$skill_info || $skill_info['status'] != 1){
			$this->result_return(null, 1, '该技能不存在或者未通过审核');
		}

		if($this->user_id == $skill_info['user_id']){
			$this->result_return(null, 1, '不能预约自己发布的技能');
		}

		$skill_type_model = D('SkillType');
		$skill_type_info = $skill_type_model->get_one(['id' => $skill_info['type_id']]);

		if($skill_type_info['free_type'] == 2){
			//如果是农林类型的,则直接将状态置为待发布者审核状态
			$insert_data['status'] = 2;
		}

		// 会员可以每天免费预约3条
		$start_time = strtotime(date('Y-m-d', time()));
		$end_time = $start_time + 24 * 3600;

		$vip_expire_time = $this->user_info['vip_expire_time'];

		if($vip_expire_time < time()){
			$is_vip = 0;
		}else{
			$is_vip = 1;
		}

		$reserve_where = [
			'status' => ['NEQ', '1'],
			'add_time' => [['gt', $start_time], ['lt', $end_time]],
			'user_id' => $this->user_id,
		];

		$reserve_count = $skill_reserve_model->get_reserve_count($reserve_where);

		$max_limit = C('max_publish_limit');

		if($reserve_count < $max_limit['vip_free_reserve'] && $is_vip){
			$insert_data['status'] = 2;
		}

		$reserve_id = $skill_reserve_model->insert_one($insert_data);

		if($reserve_id === false){
			$this->result_return(null, 1, '预约失败');
		}

		//预约成功以后增加预约人数
		$skill_model->update_data(['id' => $skill_id], ['reservation_count' => $skill_info['reservation_count'] + 1]);

		$this->result_return(['reserve_id' => $reserve_id]);
	}

	/**
	 * 发布者审核预约者
	 * @date   2019/10/31 下午2:44
	 * @url    app/skill/pulisher_review
	 * @method post
	 *
	 * @param  int param
	 * @return  array
	 */
	public function pulisher_review(){

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$reserve_id = $params['reserve_id'];
		$status = $params['status'];

		$skill_reserve_model = D('SkillReserve');
		$skill_reserve_info = $skill_reserve_model->get_skill_info_by_reserve_id($reserve_id);

		if($skill_reserve_info['pulish_status'] != 1){
			$this->result_return(null, 1, '该技能未通过审核,不能进行此操作');
		}

		if($skill_reserve_info['publish_user_id'] != $this->user_id){
			$this->result_return(null, 1, '不能操作别人的技能');
		}

		if($skill_reserve_info['status'] != 2){
			$this->result_return(null, 1, '该条预约不符合审核规范');
		}

		$update_result = $skill_reserve_model->update_data(['id' => $reserve_id], ['status' => $status]);

		if($update_result === false){
			$this->result_return(null, 1, '操作失败');
		}

		//更新order表中的状态
		//如果拒绝了预约,则需要将钱退给预约者
		// 如果有订单信息,说明是付过款的
		$order_model = D('Order');
		$order_info = $order_model->get_one(['source_type' => 3, 'source_id' => $reserve_id, 'user_id' => $skill_reserve_info['user_id'], 'status' => 1], 'add_time desc');

		$skill_reserve_model = D('SkillReserve');

		if($order_info){
			if($status == 4){
				// 收费模式下,更新order表中的状态,更新账户流水,更新账户余额
				$skill_reserve_model->update_refund_info(3, $reserve_id, $skill_reserve_info['user_id']);
			}elseif($status == 3){
				// 将钱打到技能发布者账号里
				$skill_reserve_model->update_ear_money_info($order_info['order_id'], 3, $reserve_id, $skill_reserve_info['user_id']);
			}
		}

		// 审核同意以后要写到对话框里
		if($status == 3){
			$dialog_model = D('Dialog');
			$message_model = D('Message');

			$dialog_result = $dialog_model->get_dialog_by_uids($skill_reserve_info['user_id'], $this->user_id);

			if($dialog_result){
				//更新对话框为启用状态
				$dialog_model->update_dialog_active($dialog_result['id'], $this->user_id);
				$dialog_id = $dialog_result['id'];
			}else{
				// 创建对话框
				$dialog_id = $dialog_model->create_dialog($this->user_id, $skill_reserve_info['user_id']);
			}

			//插入一条数据
			$insert_message = [
				'type' => 3,
				'dialog_id' => $dialog_id,
				'type_id' => $reserve_id,
				'uid' => $this->user_id,
				'content' => '技能类型',
			];
			$message_model->insert_one($insert_message);
		}

		$this->result_return(['result' => 1]);
	}

	/**
	 * 编辑技能
	 * @date   2019/10/31 下午3:11
	 * @url    app/skill/edit_skill
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function edit_skill(){
		$params = I('post.');

		$skill_name = $params['skill_name'];
		$type_id = $params['type_id'];
		$desc = $params['desc'];
		$price = $params['price'];
		$superiority = $params['superiority'];
		$mode = $params['mode'];
		$good_at = $params['good_at'];
		$longitude = $params['longitude'];
		$latitude = $params['latitude'];
		$skill_id = $params['skill_id'];

		$skill_mode = D('UserSkill');

		if(!$this->user_info['is_vefify']){
			$this->result_return(null, 1, '仅限认证用户发布');
		}

		// 该技能如果已经审核通过,则不能再次编辑
		$skill_info = $skill_mode->get_one(['id' => $skill_id]);
		if(!$skill_info || $skill_info['status'] != 2){
			$this->result_return(null, 1, '该条技能不能重新编辑');
		}

		// 不存在的技能则不插入数据库中
		$skill_type_model = D('SkillType');
		$skill_type_info = $skill_type_model->get_one(['id' => $type_id]);
		if(!$skill_type_info || !$skill_type_info['is_show']){
			$this->result_return(null, 1, '技能类型有误');
		}

		$skill_insert_data = [
			'skill_name' => $skill_name,
			'type_id' => $type_id,
			'desc' => $desc,
			'price' => $price,
			'superiority' => $superiority,
			'mode' => $mode,
			'good_at' => $good_at,
			'longitude' => $longitude,
			'latitude' => $latitude,
		];

		// 上传技能图片
		if($_FILES){
			$upload = new \Think\Upload();// 实例化上传类
			$upload->maxSize   =     2048000 ;// 设置附件上传大小
			$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->rootPath  =      './Uploads/'; // 设置附件上传根目录
			$upload->savePath  =      $this->user_id . '/' ; // 设置附件上传（子）目录
			//		$upload->saveName  =      $this->user_id . '_' . time(); // 设置附件上传（子）目录
			// 上传文件
			$info  =  $upload->upload();
			if(!$info) {// 上传错误提示错误信息
				$this->result_return(null, 1, $upload->getError());
			}else{// 上传成功 获取上传文件信息
				foreach($info as $file){
					$file_path = $file['savepath'] . $file['savename'];
				}

				$skill_insert_data['img'] = $file_path;
			}
		}

		$insert_result = $skill_mode->update_data(['id' => $skill_id], $skill_insert_data);

		if($insert_result === false){
			$this->result_return(null, 1, '编辑技能失败');
		}

		$this->result_return(['result' => 1]);
	}
}