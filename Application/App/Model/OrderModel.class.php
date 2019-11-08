<?php
namespace App\Model;
use Think\Model;

class OrderModel extends CommonModel{

	/*
	 * 更新付款结果
	 */
	public function update_result($order_id, $user_id, $result){

		$order_model = D('Order');
		$order_info = $order_model->get_one(['order_id' => $order_id]);

		// 查看是否存在
//				if(!$order_info['payment_id']){
//					return '未找到该付款信息';
//				}

		//先判断是成功还是失败
		if(!in_array($result, ['success', 'fail'])){
			return '参数非法';
		}

		if($order_info['user_id'] != $user_id){
			return '非法操作';
		}

		// 改变订单状态
		$update_data = [
			'payment_time' => time(),
			'status' => $result == 'success' ? 1 : 2,
		];

		$update_res = $order_model->update_data(['id' => $order_info['id']], $update_data);

		if($update_res !== false){

			//增加业务逻辑
			if($order_info['source_type'] == 1){
				// 将会员时间延长
				$user_model = D('Users');

				$vip_aging_type = json_decode($order_info['extra_info'], true);
				$souce_type_arr = C('source_type_arr');
				$vip_aging_time = $souce_type_arr[$vip_aging_type['vip_aging_type']]['time'] * 24 * 3600;

				if($this->user_info['vip_expire_time'] < time()){
					//会员已经过期
					$vip_expire_time = time() + $vip_aging_time;
				}else{
					// 未过期的会员,在此基础上增加时效
					$vip_expire_time = (int)$this->user_info['vip_expire_time'] + $vip_aging_time;

					//首冲送一年
					$is_first_vip_charge = $order_model->get_one(['status' => 1, 'user_id' => $user_id, 'source_type' => 1]);

					if(!$is_first_vip_charge){
						$vip_expire_time += 365 * 24 * 3600;
					}
				}

				$user_update_data = [
					'is_vip' => 1,
					'vip_expire_time' => $vip_expire_time,
				];
				$update_result = $user_model->update_data(['id' => $user_id], $user_update_data);
			}elseif($order_info['source_type'] == 2){

				// 需求发布成功
				$demand_status = ($result == 'success') ? 0 : 5;
				$demand_model = D('UserDemand');
				$update_result = $demand_model->update_data(['id' => $order_info['source_id']], ['status' => $demand_status]);
			}elseif($order_info['source_type'] == 3){

				$reserve_status = ($result == 'success') ? 2 : 1;
				//技能预约成功
				$skill_reserve_model = D('SkillReserve');
				$update_result = $skill_reserve_model->update_data(['id' => $order_info['source_id']], ['status' => $reserve_status]);
			}
		}

		return $update_res;
	}
}