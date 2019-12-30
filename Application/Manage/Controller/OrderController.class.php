<?php
namespace Manage\Controller;
use Think\Controller;
class OrderController extends BaseController {

	protected $souce_type;
	protected $status_arr;
	protected $withdraw_status_arr;

	public function __construct(){
		parent::__construct();
		$status_arr = [
			0 => '待支付',
			1 => '支付成功',
			2 => '支付失败',
			3 => '已退款',
		];

		$withdraw_status_arr = [
			0 => '待提现',
			1 => '提现成功',
			2 => '提现失败',
		];

		$source_type = [
			1 => '充值会员',
			2 => '发布需求',
			3 => '预约技能',
		];

		$this->souce_type = $source_type;
		$this->status_arr = $status_arr;
		$this->withdraw_status_arr = $withdraw_status_arr;

		$data['withdraw_status_arr'] = $withdraw_status_arr;
		$data['status_arr'] = $status_arr;
		$data['source_type'] = $source_type;
		$this->assign($data);
	}

	/**
	 * 订单管理
	 * @date   2019/11/12 下午9:34
	 */
	public function index(){

		$order_model = D('Order');

		$where['source_type'] = ['in', '1,2,3'];

		$params = I('get.');

		$page = I('get.p') ? I('get.p') : 1;

		$begin_date = strtotime($params['begin_date']);
		$end_date = strtotime($params['end_date']);

		if($params['begin_date'] && $params['end_date']){
			$where['add_time'] = [
				['gt', $begin_date],
				['lt', $end_date],
			];
		}

		if($params['status'] != '-1'){
			$where['status'] = $params['status'];
		}

		if($params['order_id']){
			$where['order_id'] = $params['order_id'];
		}

		if($params['pay_type']){
			$where['pay_type'] = $params['pay_type'];
		}

		$order_list = $order_model->get_page_list($where, $page, 10);
		$order_count = $order_model->get_count($where);
		$data['list'] = $order_list;
		// 加上分页
		$data['page'] = $this->page_new($order_count);

		$data['begin_date'] = $params['begin_date'];
		$data['end_date'] = $params['end_date'];
		$data['order_id'] = $params['order_id'];
		$data['pay_type'] = $params['pay_type'];
		$data['status'] = $params['status'];

		$this->assign($data);
		$this->display();
	}

	/**
	 * 提现管理
	 * @date   2019/11/12 下午9:34
	 */
	public function withdraw(){

		$order_model = D('Order');

		$where = ['source_type' => 4];

		$params = I('get.');

		$page = I('get.p') ? I('get.p') : 1;

		$begin_date = strtotime($params['begin_date']);
		$end_date = strtotime($params['end_date']);

		if($params['begin_date'] && $params['end_date']){
			$where['add_time'] = [
				['gt', $begin_date],
				['lt', $end_date],
			];
		}

		if($params['status'] != '-1'){
			$where['status'] = $params['status'];
		}

		$order_list = $order_model->get_page_list($where, $page, 10);
		$order_count = $order_model->get_count($where);
		$data['list'] = $order_list;
		// 加上分页
		$data['page'] = $this->page_new($order_count);

		$data['begin_date'] = $params['begin_date'];
		$data['end_date'] = $params['end_date'];
		$data['status'] = $params['status'];

		$this->assign($data);
		$this->display();
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

					//减少用户余额要用真实扣除的钱
					$original_money = json_decode($order_info['extra_info'], true);

					$update_result = $user_model->update_data(['id' => $order_info['user_id']], ['account_balance' => number_format($user_info['account_balance'] - $original_money['original_money'], 2)]);

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


	/**
	 * 导出订单数据
	 * @author cuirj
	 * @date   2019/5/10 下午3:43
	 * @method get
	 *
	 * @param  int param
	 */
	public function import_order_data(){
		$order_model = D('Order');

		$where['source_type'] = ['in', '1,2,3'];

		$params = I('get.');

		$page = I('get.p');

		$begin_date = strtotime($params['begin_date']);
		$end_date = strtotime($params['end_date']);

		if($params['begin_date'] && $params['end_date']){
			$where['add_time'] = [
				['gt', $begin_date],
				['lt', $end_date],
			];
		}

		if($params['status'] != '-1'){
			$where['status'] = $params['status'];
		}

		if($params['order_id']){
			$where['order_id'] = $params['order_id'];
		}

		if($params['pay_type']){
			$where['pay_type'] = $params['pay_type'];
		}

		$order_list = $order_model->get_page_list($where, null, null);

		$company_export = [];
		foreach($order_list as $k => $v){
			$company_export[] = [
				'order_id' => $v['order_id'],
				'user_id' => $v['user_id'],
				'price' => $v['price'],
				'status' => $this->status_arr[$v['status']],
				'source_type' => $this->souce_type[$v['source_type']],
				'pay_type' => $v['pay_type'] == 'wx_app' ? '微信' : ($v['pay_type'] == 'alipay_app' ? '支付宝': ''),
				'add_time' => date('Y-m-d H:i:s', $v['add_time']),
				'pay_time' => $v['pay_time'] ? date('Y-m-d H:i:s', $v['pay_time']) : '',
				'refund_time' => $v['refund_time'] ? date('Y-m-d H:i:s', $v['refund_time']) : '',
				'payment_id' => $v['payment_id'],
			];
		}

		$xlsCell = array(
			array('order_id', '订单ID'),
			array('user_id', '用户id'),
			array('price', '订单金额'),
			array('status', '状态'),
			array('source_type', '订单类型'),
			array('pay_type', '支付方式'),
			array('add_time', '下单时间'),
			array('pay_time', '支付时间'),
			array('refund_time', '退款时间'),
			array('payment_id', '支付流水号'),
		);

		$this->exportExcel('订单',$xlsCell,$company_export);
	}

	/**
	 * 导出代理数据
	 * @author cuirj
	 * @date   2019/5/10 下午3:43
	 * @method get
	 *
	 * @param  int param
	 */
	public function import_withdraw_data(){
		$order_model = D('Order');

		$where['source_type'] = 4;

		$params = I('get.');

		$begin_date = strtotime($params['begin_date']);
		$end_date = strtotime($params['end_date']);

		if($params['begin_date'] && $params['end_date']){
			$where['add_time'] = [
				['gt', $begin_date],
				['lt', $end_date],
			];
		}

		if($params['status'] != '-1'){
			$where['status'] = $params['status'];
		}

		$order_list = $order_model->get_page_list($where, null, null);

		$company_export = [];
		foreach($order_list as $k => $v){
			$company_export[] = [
				'order_id' => $v['order_id'],
				'user_id' => $v['user_id'],
				'price' => $v['price'],
				'alipay_account' => json_decode($v['extra_info'], true)['alipay_account'],
				'alipay_real_name' => json_decode($v['extra_info'], true)['alipay_real_name'],
				'status' => $this->withdraw_status_arr[$v['status']],
				'add_time' => date('Y-m-d H:i:s', $v['add_time']),
				'pay_time' => $v['pay_time'] ? date('Y-m-d H:i:s', $v['pay_time']) : '',
				'payment_id' => $v['payment_id'],
				'remark' => $v['remark'],
			];
		}

		$xlsCell = array(
			array('order_id', '提现ID'),
			array('user_id', '用户id'),
			array('price', '提现金额'),
			array('alipay_account', '支付宝账户'),
			array('alipay_real_name', '真实姓名'),
			array('status', '提现状态'),
			array('add_time', '申请提现时间'),
			array('pay_time', '打款时间'),
			array('payment_id', '支付流水号'),
			array('remark', '备注'),
		);

		$this->exportExcel('提现管理',$xlsCell,$company_export);
	}

	/**
	 * 查看付款结果-修复异常订单数据使用
	 * @author cuirj
	 * @date   2019/12/30 上午10:08
	 * @url    app/order/get_order_result
	 * @method get
	 *
	 * @param  string order_id
	 * @return  array
	 */
	public function get_order_result(){
		$order_id = I('get.order_id');

		$order_model = D('Order');
		$order_info = $order_model->get_one(['order_id' => $order_id]);
		$pay_type = $order_info['pay_type'];

		$result = $order_model->get_order_result_by_order_id($order_id, $pay_type);

		if($result){
			$order_info['res'] = $result;
		}


		$this->assign(['order_info' => $order_info]);

		$this->display('abnormal_order');
	}

	/**
	 * 修复数据
	 * @author cuirj
	 * @date   2019/12/30 上午11:49
	 * @url    app/order/pair_order
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function pair_order(){
		$order_data_tmp = I('post.order_data');

		$order_data = json_decode(htmlspecialchars_decode($order_data_tmp), true);
		$order_id = I('post.order_id');

		$order_model = D('Order');
		$res = $order_model->update_result($order_id, 'success', $order_data['total_amount'], ($order_data['pay_time']), $order_data['payment_id']);

		if($res){
			$this->result_return(['result' => 1]);
		}else{
			$this->result_return(null, 1, '修复数据失败');
		}
	}
}