<?php
namespace App\Model;
use Think\Model;

class SkillReserveModel extends CommonModel{

	/*
	 * 取某个技能下预约成功,预约中和预约被拒绝的预约信息
	 */
	public function get_skill_reserve_by_skill_id($skill_id, $page = 1, $page_size = 3, $status = '2,3,4'){
		$where = [
			'r.skill_id' => $skill_id,
			'r.status' => ['in', $status],
		];

		if(!$page && !$page_size){
			$limit = null;
		}else{
			$limit = ($page - 1) * $page_size . ',' . $page_size;
		}

		return $this->field('r.*,u.user_name,u.head_img,u.is_vefify,u.birthday,s.province,s.city,s.address,s.area,u.sex')
			->alias('r')
			->join('users u on r.user_id = u.id', 'left')
			->join('user_address as s on r.user_id=s.user_id', 'left')
			->where($where)
			->limit($limit)
			->select();
	}

	/*
	 * 根据reserve_id 获取技能信息
	 */
	public function get_skill_info_by_reserve_id($reserve_id){

		return $this->field('r.*,s.user_id as publish_user_id,s.status as pulish_status,t.free_type')
			->alias('r')
			->join('user_skill s on r.skill_id = s.id', 'left')
			->join('skill_type t on s.type_id = t.id', 'left')
			->where(['r.id' => $reserve_id])
			->find();
	}

	/*
	 * 获取预约详情
	 */
	public function get_reserve_info_by_id($reserve_id){
		return $this->field('r.*,k.user_id as publish_user_id,k.status as pulish_status,u.user_name,u.head_img,u.is_vefify,u.birthday,s.province,s.city,s.address,s.area,u.weixin_account,u.mobile_number,u.sex')
			->alias('r')
			->join('user_skill k on r.skill_id = k.id', 'left')
			->join('users u on r.user_id = u.id', 'left')
			->join('user_address as s on r.user_id=s.user_id', 'left')
			->where(['r.id' => $reserve_id])
			->find();
	}

	/*
	 * 获取今天/查询条件下预约的条数
	 */
	public function get_reserve_count($where){
		return $this->where($where)->count();
	}

	/*
	 * 更新退款相关信息
	 */
	public function update_refund_info($source_type, $source_id, $user_id){

		$order_model = D('Order');
		$users_model = D('Users');
		$balance_log_model = D('AccountBalanceLog');

		//开启事务
		$order_model->startTrans();

		// 更新订单
		$order_info = $order_model->get_one(['source_type' => $source_type, 'source_id' => $source_id, 'user_id' => $user_id]);

		$order_res = $order_model->update_data(['source_type' => $source_type, 'source_id' => $source_id, 'user_id' => $user_id], ['status' => 3, 'refund_time' => time()]);

		// 更新账户余额
		$user_info = $users_model->get_one(['id' => $user_id]);

		$user_res = $users_model->update_data(['id' => $user_id], ['account_balance' => number_format(($user_info['account_balance'] + $order_info['price']), 2)]);

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
			'order_id' => $order_info['order_id'],
		];

		$balace_res = $balance_log_model->insert_one($insert_balance_log_data);

		if(!empty($order_res) && !empty($user_res) && !empty($balace_res) ){
			$order_model->commit();

			return true;
		}else{
			$order_model->rollback();
			//加入日志
			return false;
		}
	}

	/*
	 * 更新诚意金转到对方账户相关信息
	 */
	public function update_ear_money_info($order_id, $source_type, $source_id, $update_user_id){

		$order_model = D('Order');
		$users_model = D('Users');
		$balance_log_model = D('AccountBalanceLog');

		//开启事务
		$order_model->startTrans();

		// 查询订单
		$order_info = $order_model->get_one(['order_id' => $order_id]);

		// 更新账户余额
		$user_info = $users_model->get_one(['id' => $update_user_id]);

		$user_res = $users_model->update_data(['id' => $update_user_id], ['account_balance' => number_format(($user_info['account_balance'] + $order_info['price']), 2)]);

		if($source_type == 2){
			$action = 'DEMAND_RECRUIT_SUCCESS';
			$note = '需求应征成功';
		}else{
			$action = 'SKILL_RESERVE_SUCCESS';
			$note = '技能预约成功';
		}
		//更新流水
		$insert_balance_log_data = [
			'user_id' => $update_user_id,
			'action' => $action,
			'note' => $note,
			'balance' => number_format($order_info['price'], 2),
			'item_id' => $source_id,
			'order_id' => $order_info['order_id'],
		];

		$balace_res = $balance_log_model->insert_one($insert_balance_log_data);

		if(!empty($order_res) && !empty($user_res) && !empty($balace_res) ){
			$order_model->commit();

			return true;
		}else{
			$order_model->rollback();
			//加入日志
			return false;
		}
	}
}