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
		$skill_type_result = $skill_type_model->get_list();

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
			$this->result_return(null, 500, '技能类型有误');
		}

		//上传技能图片
		if(!$_FILES){
			$this->result_return(null, 500, '请上传技能图片');
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
			$this->result_return(null, 500, $upload->getError());
		}else{// 上传成功 获取上传文件信息
			foreach($info as $file){
				$file_path = $file['savepath'] . $file['savename'];
			}
		}

		$skill_mode = D('UserSkill');
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

		if($skill_count > 4){
			$this->result_return(null, 500, '你今天已经发布了5条,请明天再来吧');
		}

		$insert_result = $skill_mode->insert_one($skill_insert_data);

		if($insert_result === false){
			$this->result_return(null, 500, '发布技能失败');
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

		if($user_id && $user_id != $this->user_id){
			$is_other = 1;
		}

		$skill_mode = D('UserSkill');
		$skill_info = $skill_mode->get_one(['id' => $skill_id]);

		if(!$skill_info){
			$this->result_return(null, 500, '没有找到该项技能');
		}

		if($is_other && $skill_info['status'] != 1){
			$this->result_return(null, 500, '该项技能未审核通过或者待审核');
		}

		if(!$is_other){
			//如果是自己的,则把预约信息放出来
			$skill_reserve_model = D('SkillReserve');
			$skill_reserve_list = $skill_reserve_model->get_skill_reserve_by_skill_id($skill_id, null, null);

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
		$skill_info = $skill_reserve_model->get_reserve_info_by_id($reserve_id);

		$skill_info['head_img'] = UPLOAD_URL . $skill_info['head_img'];
		$skill_info['earnest_money'] = 50;

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
			$this->result_return(null, 500, '该技能不存在或者未通过审核');
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

		if($reserve_count < 3 && $is_vip){
			$insert_data['status'] = 2;
		}

		$reserve_id = $skill_reserve_model->insert_one($insert_data);

		if($reserve_id === false){
			$this->result_return(null, 500, '预约失败');
		}

		//预约成功以后增加预约人数
		$skill_model->update_data(['id' => $skill_id], ['reservation_count' => $skill_info['reservation_count'] + 1]);

		// 预约完成以后要写到对话框里
		$dialog_model = D('Dialog');
		$message_model = D('Message');

		$dialog_result = $dialog_model->get_dialog_by_uids($this->user_id, $skill_info['user_id']);

		if($dialog_result){
			//更新对话框为启用状态
			$dialog_model->update_dialog_active($dialog_result['id'], $skill_info['user_id']);
			$dialog_id = $dialog_result['id'];
		}else{
			// 创建对话框
			$dialog_id = $dialog_model->create_dialog($skill_info['user_id'], $this->user_id);
		}

		//插入一条数据
		$insert_message = [
			'type' => 2,
			'dialog_id' => $dialog_id,
			'type_id' => $skill_id,
			'uid' => $skill_info['user_id'],
			'content' => '技能类型',
		];
		$message_model->insert_one($insert_message);

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
			$this->result_return(null, 500, '该技能未通过审核,不能进行此操作');
		}

		if($skill_reserve_info['publish_user_id'] != $this->user_id){
			$this->result_return(null, 500, '不能操作别人的技能');
		}

		if($skill_reserve_info['status'] != 2){
			$this->result_return(null, 500, '该条预约不符合审核规范');
		}

		$update_result = $skill_reserve_model->update_data(['id' => $reserve_id], ['status' => $status]);

		//更新order表中的状态
		//如果拒绝了预约,则需要将钱退给预约者
		if($status == 4){
			// 收费模式下,更新order表中的状态,更新账户流水,更新账户余额

			// 如果有订单信息,说明是付过款的
			$order_model = D('Order');
			$order_info = $order_model->get_one(['source_type' => 3, 'source_id' => $reserve_id, 'user_id' => $skill_reserve_info['user_id'], 'status' => 1], 'add_time desc');

			if($order_info){
				$skill_reserve_model->update_refund_info(3, $reserve_id, $skill_reserve_info['user_id']);
			}

//			if($skill_reserve_info['free_type'] == 1){
//				$order_model = D('Order');
//				$users_model = D('Users');
//				$balance_log_model = D('AccountBalanceLog');
//
//				//开启事务
//				$order_model->startTrans();
//
//				// 更新订单
//				$order_info = $order_model->get_one(['source_type' => 3, 'source_id' => $reserve_id, 'user_id' => $skill_reserve_info['user_id']]);
//
//				$order_res = $order_model->update_data(['source_type' => 3, 'source_id' => $reserve_id, 'user_id' => $skill_reserve_info['user_id']], ['status' => 3, 'refund_time' => time()]);
//
//				// 更新账户余额
//				$user_info = $users_model->get_one(['id' => $skill_reserve_info['user_id']]);
//
//				$user_res = $users_model->update_data(['id' => $skill_reserve_info['user_id']], ['account_balance' => $user_info['account_balance'] - $order_info['price']]);
//
//				//更新流水
//				$insert_balance_log_data = [
//					'user_id' => $skill_reserve_info['user_id'],
//					'action' => 'SKILL_REJECT_REFUND',
//					'note' => '技能预约被拒绝退款',
//					'balance' => $order_info['price'],
//					'item_id' => $reserve_id,
//				];
//
//				$balace_res = $balance_log_model->insert_one($insert_balance_log_data);
//
//				if(!empty($order_res) && !empty($user_res) && !empty($balace_res) ){
//					$order_model->commit();
//				}else{
//					$order_model->rollback();
//					//加入日志
//				}
//			}
		}

		if($update_result === false){
			$this->result_return(null, 500, '审核失败');
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

		// 该技能如果已经审核通过,则不能再次编辑
		$skill_info = $skill_mode->get_one(['id' => $skill_id]);
		if(!$skill_info || $skill_info['status'] != 2){
			$this->result_return(null, 500, '该条技能不能重新编辑');
		}

		// 不存在的技能则不插入数据库中
		$skill_type_model = D('SkillType');
		$skill_type_info = $skill_type_model->get_one(['id' => $type_id]);
		if(!$skill_type_info || !$skill_type_info['is_show']){
			$this->result_return(null, 500, '技能类型有误');
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
				$this->result_return(null, 500, $upload->getError());
			}else{// 上传成功 获取上传文件信息
				foreach($info as $file){
					$file_path = $file['savepath'] . $file['savename'];
				}

				$skill_insert_data['img'] = $file_path;
			}
		}

		$insert_result = $skill_mode->update_data(['id' => $skill_id], $skill_insert_data);

		if($insert_result === false){
			$this->result_return(null, 500, '编辑技能失败');
		}

		$this->result_return(['result' => 1]);
	}
}