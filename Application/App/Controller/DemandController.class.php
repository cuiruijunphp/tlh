<?php
namespace App\Controller;
use Think\Controller;
class DemandController extends BaseController {

	/**
	 * 我发布的需求列表
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 * @url    /app/skill/get_skill_type_list/
	 * @param  int param
	 * @method post
	 * @return  array
	 */
    public function get_my_demand_list(){
		$user_id = $this->user_id;

		$page = I('get.page') ? I('get.page') : 1;
		$page_size = I('get.page_size') ? I('get.page_size') : 5;

		$limit = ($page - 1) * $page_size;

		$user_demand_model = D('UserDemand');
		$user_demand_result = $user_demand_model->get_list(['user_id' => $user_id], $limit. ',' . $page_size);

		if($user_demand_result){
			// 将技能类型查询出来
			$skill_type_model = D('SkillType');
			$skill_type_list = $skill_type_model->get_list();

			$skill_type_ids = array_column($skill_type_list, 'id');
			$skill_type_names = array_column($skill_type_list, 'type_name');

			$skill_type_array = array_combine($skill_type_ids, $skill_type_names);

			foreach($user_demand_result as $k => $v){
				$user_demand_result[$k]['type_name'] = $skill_type_array[$v['type_id']];

				//应征人员
				if($v['applicants']){
					$applicants = explode(',', $v['applicants']);

					$applicants_count = count($applicants);

					if($applicants_count > 3){
						// 取前三个人的头像
						$uids = trim(implode(',', [$applicants[0], $applicants[1], $applicants[2]]), ',');
					}else{
						$uids = $v['applicants'];
					}

					$user_model = D('Users');
					$user_info_list = $user_model->get_user_info_part_by_uids($uids);
					$user_info_img = array_column($user_info_list, 'head_img');

					$user_info_img_list = array_map(function($v){
						return UPLOAD_URL . $v;
					}, $user_info_img);

				}else{
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
	 * @url    app/skill/get_my_demand_info/
	 * @method get
	 *
	 * @param  int page
	 * @param  int page_size
	 * @return  array
	 */
    public function get_my_demand_info(){
		$params = I('get.');

		$demand_id = $params['demand_id'];
		$user_id = $this->user_id;

		$user_demand_model = D('UserDemand');
		$user_demand_result = $user_demand_model->get_one(['id' => $demand_id]);

		$skill_type_model = D('SkillType');
		$skill_type_list = $skill_type_model->get_list();

		$skill_type_ids = array_column($skill_type_list, 'id');
		$skill_type_names = array_column($skill_type_list, 'type_name');

		$skill_type_array = array_combine($skill_type_ids, $skill_type_names);

		$user_demand_result['type_name'] = $skill_type_array[$user_demand_result['type_id']];

		// 拼接应征者信息
		if($user_demand_result['applicants']){
			$user_model = D('Users');

			$user_info_list = $user_model->get_user_info_part_by_uids($user_demand_result['applicants']);

			foreach($user_info_list as $u_k => $u_v){
				$user_info_list[$u_k]['head_img'] = UPLOAD_URL . $u_v['head_img'];
			}

			$user_demand_result['applicants_user_info'] =$user_info_list;

		}else{
			$user_demand_result['applicants_user_info'] = [];
		}

		$this->result_return($user_demand_result);
	}
}