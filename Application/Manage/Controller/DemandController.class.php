<?php
namespace Manage\Controller;
use Think\Controller;
class DemandController extends BaseController {

	public function __construct(){
		parent::__construct();
		$skill_status_arr = [
			0 => '待审核',
			1 => '应征中',
			2 => '审核不通过',
			3 => '已完成',
			4 => '待付款',
			5 => '付款失败',
		];

		$data['demand_status_arr'] = $skill_status_arr;
		$this->assign($data);
	}

    public function index(){

		$demand_model = D('UserDemand');

		$page = I('get.p');
		$demand_list = $demand_model->get_demand_list(null, $page);
		$demand_count = $demand_model->get_count();
		$data['list'] = $demand_list;
		// 加上分页
		$data['page'] = $this->page_new($demand_count);

		$this->assign($data);
		$this->display();
    }

	public function edit(){
		$skill_type_model = D('SkillType');
		$demand_model = D('UserDemand');

		if (IS_POST) {
			$params = I('post.');

			$insert_data = [
				'status' => (int)$params['status'],
			];

			if(!$params['id']){
				//后台不能新增技能
			}else{
				//只能修改状态,如果是已经完成的,则不能再次修改
				$demand_info = $demand_model->get_one(['id' => $params['id']]);

				if($demand_info['status'] == 3){
					$this->result_return(null, 500, '已经完成的需求不能修改状态');
				}

				$update_result = $demand_model->update_data(['id' => $params['id']], $insert_data);
				if($update_result !== false){
					$this->result_return(['result' => 1]);
				}else{
					$this->result_return(null, 500, '修改状态失败');
				}
			}
		}

		//参数
		if (!empty(I('get.id'))){
			$skill_info = $demand_model->get_demand_by_id(I('get.id'));
		}

		// 加上所有技能类型下拉列表
		$parent_skill_type = $skill_type_model->get_list();
		$data['skill_type_list'] = $parent_skill_type;

		$data['list'] = $skill_info;
		$this->assign($data);
		$this->display();
	}

	public function change_status()
	{
		$demand_model = D('UserDemand');

		//判断当前传的参数和数据库中是否相同,如果相同则报错
		$where['id'] = I('post.id');
		$data['status'] = I('post.status');
		$result = $demand_model->update_data($where, $data);
		if ($result) {
			$this->result_return(['result' => 1]);
		} else {
			$this->result_return(null, 500, '修改状态失败,请重试');
		}
	}
}