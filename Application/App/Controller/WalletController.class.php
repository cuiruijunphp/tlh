<?php
namespace App\Controller;
use Think\Controller;
class WalletController extends BaseController {

	/**
	 * 获取账户流水
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 *
	 * @param  int param
	 * @method post
	 * @return  array
	 */
    public function get_wallet_log(){
		$params = I('get.');
    }

	/**
	 * 用户提现
	 * @date   2019/11/11 上午11:20
	 * @url    app/wallet/withdraw/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function withdraw(){

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		//提现金额
		$money = $params['money'];

		//先判断是否已经绑定了支付宝账号
		$user_id = $this->user_id;

		if(!$this->user_info['alipay_account']){
			$this->result_return(null, 500, '未绑定支付宝');
		}

		if($this->user_info['account_balance'] < $money){
			$this->result_return(null, 500, '账户可提现金额小于申请提现金额');
		}

		//提现时候只能一笔笔提现,上笔提现申请通过了才能进行下一笔的提现
		$order_model = D('Order');
		$withdraw_info = $order_model->get_one(['status' => 0, 'user_id' => $user_id, 'source_type' => 4]);

//		if($withdraw_info){
//			$this->result_return(null, 500, '你还有未完成的提现申请');
//		}

		//创建订单
		//创建订单号
		$order_id = session_create_random_id(32);

		$insert_data = [
			'order_id' => $order_id,
			'user_id' => $user_id,
			'price' => $money,
			'pay_type' => 'alipay_app',
			'source_type' => 4,
			'source_id' => 0,
			//把本次转账的支付宝账号记录下来
			'extra_info' => json_encode(['alipay_account' => $this->user_info['alipay_account']]),
		];

		$insert_result = $order_model->insert_one($insert_data);

		if($insert_result === false){
			$this->result_return(null, 500, '提现申请失败');
		}

		$this->result_return(['order_id' => $order_id]);
	}

	/**
	 * 获取该账户下的纪录
	 * @date   2019/11/11 下午3:22
	 * @url    app/wallet/get_balance_log/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_balance_log(){
		$params = I('get.');

		$type = $params['type'];
		$start_time = $params['start_time'];
		$end_time = $params['end_time'];
		$page = $params['page'] ? $params['page'] : 1;
		$page_size = $params['page_size'] ? $params['page_size'] : 5;

		$limit = ($page - 1) * $page_size;

		$balance_list = [];

		//消费记录的查询条件
		$order_where = [
			'source_type_id' => ['in', '2,3'],
			'user_id' => $this->user_id,
			'add_time' => [
				['gt', $start_time],
				['lt', $end_time],
			]
		];

		// 提现记录查询条件
		$withdraw_where = [
			'action' => 'USER_WITHDRAW',
			'user_id' => $this->user_id,
			'status' => 1,
			'add_time' => [
				['gt', $start_time],
				['lt', $end_time],
			]
		];

		$order_model = D('Order');
		$account_log_model = D('AccountBalanceLog');

		if(in_array($type, [1,2,3,4])){
			$where = [];
			if($type == 1){
				// 提现
				$where['action'] = 'USER_WITHDRAW';
			}elseif ($type == 2){
				// 邀请充值记录
				$where['action'] = 'INVITE_RECHARGE_VIP';
			}elseif($type == 3){
				// 需求或者技能被拒绝退钱
				$where['action'] = ['in', 'DEMAND_REJECT_REFUND,SKILL_REJECT_REFUND'];
			}else{
				//代理
				if($this->user_info['type'] != 3){
					// 如果不是代理,则不能传这个参数,
					$this->result_return(null, 500, '非法参数');
				}

				$where['action'] = 'PROXY_RECHARGE_VIP';
			}

			$where['user_id'] = $this->user_id;

			$where['add_time'] = [
				['gt', $start_time],
				['lt', $end_time],
			];

			$balance_tmp_list = $account_log_model->get_list($where, $limit. ',' . $page_size, 'add_time desc');

			foreach($balance_tmp_list as $k => $v){
				$balance_list[$k] = [
					'note' => $v['note'],
					'add_time' => $v['add_time'],
					'balance' => $v['balance'],
				];
			}

		}else{
			//消费记录
			$order_list = $order_model->get_list($order_where, $limit. ',' . $page_size, 'add_time desc');

			foreach($order_list as $o_k => $o_v){
				$balance_list[$o_k] = [
					'note' => '诚意金',
					'add_time' => $o_v['add_time'],
					'balance' => '-' . $o_v['price'],
				];
			}
		}

		// 消费总额
		$pay_sum = $order_model->get_sum_demand_skill_price($order_where);

		//提现记录
		$withdraw_sum = $account_log_model->get_sum_withdraw_price($withdraw_where);

		$res_data = [
			'balance_list' => $balance_list ? $balance_list : [],
			'withdraw_sum' => $withdraw_sum ? $withdraw_sum : '0.00',
			'pay_sum' => $pay_sum ? $pay_sum : '0.00',
			'account_balance' => $this->user_info['account_balance']
		];
		$this->result_return($res_data);
	}
}