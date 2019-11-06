<?php
namespace Manage\Model;
use Think\Model;

class OrderModel extends CommonModel{

	/*
	 * 更新退款相关信息
	 */
	public function update_refund_info($source_type, $source_id, $user_id){

//		$order_model = $this->model;
		$users_model = D('Users');
		$balance_log_model = D('AccountBalanceLog');
		//开启事务
		$this->startTrans();

		// 更新订单
		$order_info = $this->get_one(['source_type' => $source_type, 'source_id' => $source_id, 'user_id' => $user_id]);

		$order_res = $this->update_data(['source_type' => $source_type, 'source_id' => $source_id, 'user_id' => $user_id], ['status' => 3, 'refund_time' => time()]);

		// 更新账户余额
		$user_info = $users_model->get_one(['id' => $user_id]);

		$user_res = $users_model->update_data(['id' => $user_id], ['account_balance' => number_format(($user_info['account_balance'] - $order_info['price']), 2)]);

		if($source_type == 2){
			$action = 'DEMAND_REJECT_REFUND';
			$note = '需求审核不通过';
		}else{
			$action = 'SKILL_REJECT_REFUND';
			$note = '技能预约被拒绝退款';
		}

		//更新流水
		$insert_balance_log_data = [
			'user_id' => $user_id,
			'action' => $action,
			'note' => $note,
			'balance' => number_format($order_info['price'], 2),
			'item_id' => $source_id,
		];

		$balace_res = $balance_log_model->insert_one($insert_balance_log_data);

		if(!empty($order_res) && !empty($user_res) && !empty($balace_res) ){
			$this->commit();

			return true;
		}else{
			$this->rollback();
			//加入日志
			return false;
		}
	}
}