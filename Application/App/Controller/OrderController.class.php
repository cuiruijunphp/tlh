<?php
namespace App\Controller;
use Think\Controller;
class OrderController extends BaseController {

	/**
	 * 创建订单
	 * @date   2019/11/4 下午4:43
	 * @url    app/order/create_order/
	 * @method post
	 *
	 * @param  int page
	 * @param  int page_size
	 * @return  array
	 */
    public function create_order(){
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		//1-购买会员,2-发布需求,3-预约技能
		$source_type = $params['source_type'];
		$source_id = $params['source_id'];
		$vip_aging_type = $params['vip_aging_type'];//vip时效

		$price = $params['price'];
//		$pay_type = $params['pay_type'];

		//验证支付方式

		$user_id = $this->user_id;

    	//创建订单号
		$order_id = session_create_random_id(32);

		$insert_data = [
			'order_id' => $order_id,
			'user_id' => $user_id,
//			'price' => $price,
//			'pay_type' => $pay_type,
			'source_type' => $source_type,
			'source_id' => $source_id,
		];

		// 如果是vip时效
		if($source_type == 1){
			//充值会员
			$source_type_arr = C('source_type_arr');

//			if($source_type_arr[$vip_aging_type]['price'] != $price){
//				$this->result_return(null, 500, '创建订单失败,金额非法');
//			}

			$insert_data['price'] = $source_type_arr[$vip_aging_type]['price'];

			// 如果是充会员,则需要写入会员套餐信息
			$insert_data['extra_info'] = json_encode(['vip_aging_type' => $vip_aging_type]);

		}else{
			if($source_type == 2){
				//验证是否存在该条需求
				$demand_model = D('UserDemand');
				$demand_info = $demand_model->get_one(['id' => $source_id]);
				if(!$demand_info){
					$this->result_return(null, 500, '该条需求不满足付款条件');
				}

			}elseif($source_type == 3){
				//验证是否存在该条需技能
				$reserve_model = D('SkillReserve');
				$reserve_info = $reserve_model->get_one(['id' => $source_id]);

				if(!$reserve_info){
					$this->result_return(null, 500, '不存在的预约,不能付款哦');
				}
			}

			// 判断诚意金是否在可选范围内
			$earnest_money_list = C('earnest_money_arr');
			if(!in_array($price, $earnest_money_list)){
				$this->result_return(null, 500, '诚意金不在可选范围');
			}

			$insert_data['price'] = $price;
		}


		$order_model = D('Order');
		$insert_result = $order_model->insert_one($insert_data);

		if($insert_result === false){
			$this->result_return(null, 500, '创建订单失败');
		}

		$this->result_return(['order_id' => $order_id]);
	}
}