<?php
namespace Manage\Controller;
use Think\Controller;
class OrderController extends BaseController {

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

	public function withdraw(){

		$order_model = D('Order');

		$where = ['source_type' => 4];

		$page = I('get.p');
		$order_list = $order_model->get_page_list($where, $page, 10);
		$order_count = $order_model->get_count($where);
		$data['list'] = $order_list;
		// 加上分页
		$data['page'] = $this->page_new($order_count);

		$this->assign($data);
		$this->display('index');
	}

	public function edit(){
		$order_model = D('Order');

		if (IS_POST) {
			$params = I('post.');

			$insert_data = [
				'status' => (int)$params['status'],
				'pay_time' => strtotime($params['pay_time']),
				'payment_id' => $params['payment_id'],
				'remark' => $params['remark'],
			];

			if(!$params['id']){
				//后台不能新增
			}else{
				//修改
				$order_info = $order_model->get_one(['id' => $params['id']]);

				$update_result = $order_model->update_data(['id' => $params['id']], $insert_data);
				if($update_result !== false){
					// 这个时候要更新账户流水表
					$account_log_model = D('AccountBalanceLog');
					//写入账户流水
					$withdraw_user_balace_log_data = [
						'user_id' => $order_info['user_id'],
						'action' => 'USER_WITHDRAW',
						'note' => '用户提现',
						'balance' => $order_info['price'],
						'item_id' => 0,
						'order_id' => $order_info['order_id'],
					];

					$invite_balace_res = $account_log_model->insert_one($withdraw_user_balace_log_data);

					// 这个时候还要减少用户的余额
					$user_model = D('Users');
					$user_info  = $user_model->get_one(['id' => $order_info['user_id']]);

					$update_result = $user_model->update_data(['id' => $order_info['user_id']], ['account_balance' => $user_info['account_balance'] - $order_info['price']]);

					$this->result_return(['result' => 1]);
				}else{
					$this->result_return(null, 500, '提现审核失败');
				}
			}
		}

		//参数
		if (!empty(I('get.id'))){
			$order_info = $order_model->get_one(['id' => I('get.id')]);
		}

		$data['list'] = $order_info;
		$this->assign($data);
		$this->display();
	}
}