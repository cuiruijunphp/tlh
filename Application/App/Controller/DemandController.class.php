<?php
namespace App\Controller;

use Think\Controller;

class DemandController extends BaseController
{

	/**
	 * 我发布的需求列表
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 * @url    /app/demand/get_my_demand_list/
	 *
	 * @param  int param
	 * @method post
	 *
	 * @return  array
	 */
	public function get_my_demand_list()
	{
		$user_id = $this->user_id;

		$page = I('get.page') ? I('get.page') : 1;
		$page_size = I('get.page_size') ? I('get.page_size') : 5;

		$limit = ($page - 1) * $page_size;

		$user_demand_model = D('UserDemand');
		$user_demand_result = $user_demand_model->get_list(['user_id' => $user_id], $limit . ',' . $page_size);

		if ($user_demand_result)
		{
			// 将技能类型查询出来
			$skill_type_model = D('SkillType');
			$skill_type_list = $skill_type_model->get_list();

			$skill_type_ids = array_column($skill_type_list, 'id');
			$skill_type_names = array_column($skill_type_list, 'type_name');

			$skill_type_array = array_combine($skill_type_ids, $skill_type_names);

			foreach ($user_demand_result as $k => $v)
			{
				$user_demand_result[$k]['type_name'] = $skill_type_array[$v['type_id']];

				//应征人员
				if ($v['applicants'])
				{
					$applicants = explode(',', $v['applicants']);

					$applicants_count = count($applicants);

					if ($applicants_count > 3)
					{
						// 取前三个人的头像
						$uids = trim(implode(',', [
							$applicants[0],
							$applicants[1],
							$applicants[2],
						]), ',');
					}
					else
					{
						$uids = $v['applicants'];
					}

					$user_model = D('Users');
					$user_info_list = $user_model->get_user_info_part_by_uids($uids);
					$user_info_img = array_column($user_info_list, 'head_img');

					$user_info_img_list = array_map(function($v)
					{
						return UPLOAD_URL . $v;
					}, $user_info_img);

				}
				else
				{
					// 没有应征人员,则总数为0
					$applicants_count = 0;
					$user_info_img_list = [];
				}

				$user_demand_result[$k]['applicants_count'] = $applicants_count;
				$user_demand_result[$k]['applicants_user_head_img'] = $user_info_img_list;
			}
		}

		$this->result_return($user_demand_result);
	}

	/**
	 * 获取需求详情
	 * @author cuirj
	 * @date   2019/9/27 下午6:27
	 * @url    app/demand/get_demand_info/
	 * @method get
	 *
	 * @param  int page
	 * @param  int page_size
	 *
	 * @return  array
	 */
	public function get_demand_info()
	{
		$params = I('get.');

		$demand_id = $params['demand_id'];
		$user_id = I('get.user_id');

		if($user_id && $user_id != $this->user_id){
			$is_other = 1;
		}else{
			$user_id = $this->user_id;
		}

		$user_demand_model = D('UserDemand');
		$user_demand_result = $user_demand_model->get_one(['id' => $demand_id]);

		$skill_type_model = D('SkillType');
		$skill_type_list = $skill_type_model->get_list();

		$skill_type_ids = array_column($skill_type_list, 'id');
		$skill_type_names = array_column($skill_type_list, 'type_name');

		$skill_type_array = array_combine($skill_type_ids, $skill_type_names);

		$user_demand_result['type_name'] = $skill_type_array[$user_demand_result['type_id']];

		// 拼接应征者信息
		if ($user_demand_result['applicants'])
		{
			$user_model = D('Users');

			// 获取应征者个人信息、地址信息、技能优势
			$user_info_list = $user_model->get_user_info_skill_by_uids($user_demand_result['applicants'], $user_demand_result['type_id']);

			foreach ($user_info_list as $u_k => $u_v)
			{
				$user_info_list[$u_k]['head_img'] = $u_v['head_img'] ? UPLOAD_URL . $u_v['head_img'] : '';
				$user_info_list[$u_k]['add_time'] = $u_v['add_time'];

				// 应征成功者信息
				if ($user_demand_result['selected_uid'] && $user_demand_result['selected_uid'] == $u_v['id'])
				{
					$user_demand_result['selected_user_info'] = $user_info_list[$u_k];
				}
				else
				{
					$user_demand_result['selected_user_info'] = (object)[];
				}
			}

			$user_demand_result['applicants_user_info'] = $user_info_list;

		}
		else
		{
			$user_demand_result['applicants_user_info'] = [];
		}

		$this->result_return($user_demand_result);
	}

	/**
	 * 确认应征者
	 * @author cuirj
	 * @date   2019/10/12 上午10:01
	 * @url    app/demand/confirm_applicant/
	 * @method get
	 *
	 * @param  int param
	 *
	 * @return  array
	 */
	public function confirm_applicant()
	{
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$user_id = $params['user_id'];
		$demand_id = $params['demand_id'];

		// 先查看是否存在
		$user_demand_model = D('UserDemand');
		$user_demand_result = $user_demand_model->get_one(['id' => $demand_id]);
		if (!$user_demand_result)
		{
			$this->result_return(null, 1, '该条需求不存在');
		}

		if ($user_demand_result['status'] == 3 && $user_demand_result['selected_uid'])
		{
			$this->result_return(null, 1, '该条需求已经完成');
		}

		if (!in_array($user_id, explode(',', $user_demand_result['applicants'])))
		{
			$this->result_return(null, 1, '请在应征者列表中确认应征者');
		}

		if ($user_demand_result['end_time'] < time())
		{
			$this->result_return(null, 1, '该条需求已经过期');
		}

		$update_result = $user_demand_model->update_data(['id' => $demand_id], [
			'selected_uid' => $user_id,
			'status' => 3,
		]);

		if ($update_result === false)
		{
			$this->result_return(null, 1, '确认应征者失败');
		}

		$this->result_return(['result' => 1]);
	}

	/**
	 * 发布需求
	 * @author cuirj
	 * @date   2019/10/12 下午3:13
	 * @url    app/demand/publish_demand/
	 * @method get
	 *
	 * @param  int param
	 *
	 * @return  array
	 */
	public function publish_demand()
	{
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$user_id = $this->user_id;
		$title = $params['title'];
		$type_id = $params['type_id'];
		$start_time = $params['start_time'];
		$end_time = $params['end_time'];
		$earnest_money = $params['earnest_money'];
		$longitude = $params['longitude'];
		$latitude = $params['latitude'];

		$skill_type_model = D('SkillType');
		$skill_type_info = $skill_type_model->get_one(['id' => $type_id, 'is_show' => 1]);

		if(!$skill_type_info){
			$this->result_return(null, 1, '请选择有效的服务类型');
		}

		$user_demand_model = D('UserDemand');
		$insert_data = [
			'user_id' => $user_id,
			'title' => $title,
			'type_id' => $type_id,
			'start_time' => strtotime($start_time),
			'end_time' => $end_time,
			'earnest_money' => $earnest_money,
			'longitude' => $longitude,
			'latitude' => $latitude,
			'status' => 4,//待付款状态
		];


		$today_start_time = strtotime(date('Y-m-d', time()));
		$today_end_time = $start_time + 24 * 3600;
		$demand_where = [
			'status' => ['not in', '4,5'],
			'add_time' => [['gt', $today_start_time], ['lt', $today_end_time]],
			'user_id' => $user_id,
		];

		$demand_count = $user_demand_model->get_pulish_count($demand_where);
		$max_limit = C('max_publish_limit');

		if($demand_count > ($max_limit['demand'] - 1) ){
			$this->result_return(null, 1, '你今天已经发布了'. $max_limit['demand'] .'条,请明天再来吧');
		}

		// 如果是免费类型的需求,则不需要付款,直接将状态更改
		if($skill_type_info['free_type'] == 2){
			//待审核状态(付款完成)
			$insert_data['status'] = 0;
		}

		// 如果是会员,则可以免费发布一条
		$vip_expire_time = $this->user_info['vip_expire_time'];

		if($vip_expire_time < time()){
			$is_vip = 0;
		}else{
			$is_vip = 1;
		}

		if($demand_count < 1 && $is_vip){
			$insert_data['status'] = 0;
		}

		$insert_result = $user_demand_model->insert_one($insert_data);

		if($insert_result === false){
			$this->result_return(null, 1, '发布需求失败');
		}

		$this->result_return(['demand_id' => $insert_result]);
	}

	/**
	 * 应征
	 * @author cuirj
	 * @date   2019/10/12 下午2:37
	 * @url    app/demand/recruited/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function recruited(){
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$user_id = $this->user_id;
		$demand_id = $params['demand_id'];

		//查看当前的需求是否存在
		// 先查看是否存在
		$user_demand_model = D('UserDemand');
		$user_demand_result = $user_demand_model->get_one(['id' => $demand_id]);
		if(!$user_demand_result){
			$this->result_return(null, 1, '该条需求不存在');
		}

		if($user_demand_result['status'] != 1){
			$this->result_return(null, 1, '该条需求不在应征状态');
		}

		if($user_demand_result['status'] == 3 && $user_demand_result['selected_uid']){
			$this->result_return(null, 1, '该条需求已经完成');
		}

		if($user_id == $user_demand_result['user_id']){
			$this->result_return(null, 1, '你不能应征自己的需求哦');
		}

		//查看自己是否有相应的技能,如果没有,则不能应征
		$user_skill_model = D('UserSkill');
		$user_skill_result = $user_skill_model->get_one(['type_id' => $user_demand_result['type_id'], 'user_id' => $user_id, 'status' => 1]);

		if(!$user_skill_result){
			$this->result_return(null, 1, '需要先发布相应的技能才能应征哦');
		}

		if($user_demand_result['applicants']){
			// 不能重复应征
			if(in_array($user_id, explode(',', $user_demand_result['applicants']))){
				$this->result_return(null, 1, '你已经应征过这个需求了哦');
			}

			$applicants = $user_demand_result['applicants'] . ',' . $user_id;
		}else{
			$applicants = $user_id;
		}

		$update_result = $user_demand_model->update_data(['id' => $demand_id], ['applicants' => $applicants]);

		if($update_result === false){
			$this->result_return(null, 1, '应征失败');
		}

		// 应征完成以后要写到对话框里
		$dialog_model = D('Dialog');
		$message_model = D('Message');

		$dialog_result = $dialog_model->get_dialog_by_uids($user_id, $user_demand_result['user_id']);

		if($dialog_result){
			//更新对话框为启用状态
			$dialog_model->update_dialog_active($user_id, $dialog_result['id']);
			$dialog_id = $dialog_result['id'];
		}else{
			// 创建对话框
			$dialog_id = $dialog_model->create_dialog($user_demand_result['user_id'], $user_id);
		}

		//插入一条数据
		$insert_message = [
			'type' => 2,
			'dialog_id' => $dialog_id,
			'type_id' => $demand_id,
			'uid' => $user_id,//我应征需求,应该是我给需求发布者发消息
			'content' => '需求类型',
		];
		$message_model->insert_one($insert_message);

		$this->result_return(['result' => 1]);
	}

	/**
	 * 诚意金列表
	 * @date   2019/11/6 下午8:39
	 * @url    app/demand/get_earnest_money_list/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_earnest_money_list(){
		$earnest_money_arr = C('earnest_money_arr');

		$this->result_return(['earnest_money_list' => $earnest_money_arr]);
	}
}