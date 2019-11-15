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

		$page = I('get.p') ? I('get.p') : 1;
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
			// 如果不通过的话则判断该需求是否是免费发布的
			if(I('post.status') == 2){
				//查看是否有相应订单,如果有相应订单才会有退钱一系列操作
				$order_model = D('Order');
				$demand_info = $demand_model->get_one(['id' => I('post.id')]);
				$order_info = $order_model->get_one(['source_type' => 2, 'source_id' => I('post.id'), 'user_id' => $demand_info['user_id'], 'status' => 1], 'add_time desc');

				if($order_info){
					//如果有订单信息,说明有付款信息,则进行退款操作
					$order_model->update_refund_info(2, I('post.id'), $demand_info['user_id']);
				}
			}

			$this->result_return(['result' => 1]);
		} else {
			$this->result_return(null, 500, '修改状态失败,请重试');
		}
	}
}