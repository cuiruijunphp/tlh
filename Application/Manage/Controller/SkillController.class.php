<?php
namespace Manage\Controller;
use Think\Controller;
class SkillController extends BaseController {

	public function __construct(){
		parent::__construct();
		$skill_status_arr = [
			0 => '待审核',
			1 => '审核通过',
			2 => '审核不通过',
		];

		$data['skill_status_arr'] = $skill_status_arr;
		$this->assign($data);
	}

    public function index(){

		$skill_model = D('UserSkill');

		$page = I('get.p') ? I('get.p') : 1;
		$skill_list = $skill_model->get_skill_list(null, $page, 10, 's.add_time desc');
		$skill_count = $skill_model->get_count();
		$data['list'] = $skill_list;
		// 加上分页
		$data['page'] = $this->page_new($skill_count);

		$this->assign($data);
		$this->display();
    }

	public function edit(){
		$skill_type_model = D('SkillType');
		$skill_model = D('UserSkill');

		if (IS_POST) {
			$params = I('post.');

			$insert_data = [
				'status' => (int)$params['status'],
			];

			if(!$params['id']){
				//后台不能新增技能
			}else{
				//修改
				$update_result = $skill_model->update_data(['id' => $params['id']], $insert_data);
				if($update_result !== false){
					$this->result_return(['result' => 1]);
				}else{
					$this->result_return(null, 500, '修改状态失败');
				}
			}
		}

		//参数
		if (!empty(I('get.id'))){
			$skill_info = $skill_model->get_skill_by_id(I('get.id'));
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
		$user_skill_model = D('UserSkill');

		//判断当前传的参数和数据库中是否相同,如果相同则报错
		$where['id'] = I('post.id');
		$data['status'] = I('post.status');
		$result = $user_skill_model->update_data($where, $data);
		if ($result) {
			$this->result_return(['result' => 1]);
		} else {
			$this->result_return(null, 500, '修改状态失败,请重试');
		}
	}
}