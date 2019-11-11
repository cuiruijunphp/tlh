<?php
namespace App\Model;
use Think\Model;

class OrderModel extends CommonModel{

	/*
	 * 更新付款结果
	 * @order_id 商户订单号
	 * @result 结果-success/fail
	 * @total_amount 订单金额
	 * @payment_time 付款时间
	 * @payment_id 支付方式流水号
	 */
	public function update_result($order_id, $result, $total_amount, $payment_time, $payment_id){

		$order_model = D('Order');
		$order_info = $order_model->get_one(['order_id' => $order_id]);

		if(!$order_info){
			return '订单不存在';
		}

		//先判断是成功还是失败
		if(!in_array($result, ['success', 'fail'])){
			return '参数非法';
		}

		if($order_info['price'] != $total_amount){
			return '订单金额非法';
		}

		$user_id = $order_info['user_id'];

		// 改变订单状态
		$update_data = [
			'pay_time' => $payment_time,
			'payment_id' => $payment_id,
			'status' => $result == 'success' ? 1 : 2,
		];

		$update_res = $order_model->update_data(['id' => $order_info['id']], $update_data);

		if($update_res !== false){

			//增加业务逻辑
			if($order_info['source_type'] == 1){
				// 将会员时间延长
				$user_model = D('Users');
				$user_info = $user_model->get_one(['id' => $user_id]);

				$vip_aging_type = json_decode($order_info['extra_info'], true);
				$souce_type_arr = C('source_type_arr');
				$vip_aging_time = $souce_type_arr[$vip_aging_type['vip_aging_type']]['time'] * 24 * 3600;

				if($user_info['vip_expire_time'] < time()){
					//会员已经过期
					$vip_expire_time = time() + $vip_aging_time;
				}else{
					// 未过期的会员,在此基础上增加时效
					$vip_expire_time = $user_info['vip_expire_time'] + $vip_aging_time;

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

				$account_log_model = D('AccountBalanceLog');

				//判断是否有邀请人,如果有邀请人,则更新邀请人账户余额,写入账户流水
				if($user_info['invite_user_id']){
					//更新账户余额
					$user_model->update_data(['id' => $user_info['invite_user_id']], ['account_balance' => $user_info['account_balance'] + $souce_type_arr[$vip_aging_type['vip_aging_type']['invite_income']]]);

					//写入账户流水
					$invite_user_balace_log_data = [
						'user_id' => $user_info['invite_user_id'],
						'action' => 'INVITE_RECHARGE_VIP',
						'note' => '邀请人充值会员',
						'balance' => $souce_type_arr[$vip_aging_type['vip_aging_type']]['invite_income'],
						'item_id' => $user_id,
						'order_id' => $order_id,
					];

					$invite_balace_res = $account_log_model->insert_one($invite_user_balace_log_data);
				}

				//查看当前县级代理,写入账户流水表中-暂时用这种方式来取
				//地址信息
				$user_address_model = D('UserAddress');

				$address_info = $user_address_model->get_one(['user_id' => $user_id]);
				if($address_info){
					$province = $address_info['province'];
					$city = $address_info['city'];
					$area = $address_info['area'];

					if($province && $city && $area){
						// 查找代理的user_id
						$proxy_user_info = $user_address_model->get_one(['province' => $province, 'city' => $city, 'area' => $area, 'user_type' => 3]);

						if($proxy_user_info){
							// 如果当前有代理,则写入流水表中
							//写入账户流水
							$proxy_user_balace_log_data = [
								'user_id' => $proxy_user_info['user_id'],
								'action' => 'PROXY_RECHARGE_VIP',
								'note' => '代理城市用户充值会员',
								'balance' => $souce_type_arr[$vip_aging_type['vip_aging_type']]['proxy_income'],
								'item_id' => $user_id,
								'order_id' => $order_id,
							];

							$proxy_balace_res = $account_log_model->insert_one($proxy_user_balace_log_data);
						}
					}
				}

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

	/*
	 * 获取该条件下,消费记录总数
	 */
	public function get_sum_demand_skill_price($where){
		return $this->where($where)->sum('price');
	}
}