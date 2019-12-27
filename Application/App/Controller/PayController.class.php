<?php
namespace App\Controller;
use Think\Controller;
use Lib\Wx;
class PayController extends BaseController {

	/**
	 * 支付
	 * @date   2019/11/6 下午5:05
	 * @url    app/pay/submit_pay/
	 * @method get
	 *
	 * @param  string order_id
	 * @param  string pay_type wx_app/alipay_app
	 * @return  array
	 */
	public function submit_pay(){

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$order_id = $params['order_id'];
		$pay_type = $params['pay_type'];

		if(!in_array($pay_type, ['wx_app', 'alipay_app'])){
			$this->result_return(null, 1, '请选择正确的支付方式');
		}

		$user_id = $this->user_id;

		$order_model = D('Order');
		$order_info = $order_model->get_one(['order_id' => $order_id]);

		if(!$order_info){
			$this->result_return(null, 1, '未查询到订单信息');
		}

		if($order_info['user_id'] != $user_id){
			$this->result_return(null, 1, '不能给别人付款哦~');
		}

		//更新付款方式
		$order_model->update_data(['id' => $order_info['id']],['pay_type' => $pay_type]);

		$source_type_title = [
			1 => '会员充值',
			2 => '需求发布诚意金',
			3 => '技能预约',
		];

		if($pay_type == 'alipay_app'){
			$ali_pay = new \Lib\Ali\Alipay();
			$order_string = $ali_pay->alipay_app_pay($source_type_title[$order_info['source_type']], $order_id, $order_info['price']);
		}else{
			$wxapp_pay = new \Lib\Wx\Wxpay();
			$wx_res = $wxapp_pay->wxapp_pay($source_type_title[$order_info['source_type']], $order_id, $order_info['price']);

			if($wx_res){
				if($wx_res['status'] == 1){
					// 说明调用成功,则返回给app端正确的order_string
					$order_string = $wx_res['data'];
				}else{
					$this->result_return(null, 1, $wx_res['msg']);
				}
			}else{
				$this->result_return(null, 1, '调用微信支付失败');
			}
		}

		$this->result_return(['order_string' => $order_string]);
	}

	/**
	 * 成功了以后的回调
	 * @date   2019/11/4 下午5:53
	 * @url    app/pay/result_callback/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function result_callback(){
		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$order_id = $params['order_id'];
		$result = $params['result'];
		$payment_time = $params['payment_time'];
		$payment_id = $params['payment_id'];
		$total_amount = $params['total_amount'];

		$user_id = $this->user_id;

		$order_model = D('Order');
		$res = $order_model->update_result($order_id, $result, $total_amount, $payment_time, $payment_id);

		if(is_string($res)){
			$this->result_return(null, 1, $res);
		}

		if($res !==false){
			$this->result_return(['result' => 1]);
		}else{
			$this->result_return(null, 1, '更新状态失败');
		}
	}

	/*
	 * 主动获取支付订单状态
	 */
	public function get_order_result(){
		$order_id = I('get.order_id');

		$aliConfig = C('ALIPAY_CONFIG');

		Vendor('Alipay.aop.AopClient');
		Vendor('Alipay.aop.Request.AlipayTradeQueryRequest');

		$aop = new \AopClient ();
		$aop->gatewayUrl = $aliConfig['gatewayUrl'];
		//appid
		$aop->appId = $aliConfig['appId'];
		//开发者私钥
		$aop->rsaPrivateKey = $aliConfig['rsaPrivateKey'];
		//支付宝公钥
		$aop->alipayrsaPublicKey = $aliConfig['alipayPublicKey'];


		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='UTF-8';
		$aop->format='json';
		$request = new \AlipayTradeQueryRequest();

		$bizcontent = json_encode([
			'out_trade_no' => $order_id,//此订单号为商户唯一订单号
		]);

		$request->setBizContent($bizcontent);
		$result = $aop->execute ( $request);

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;

		if(!empty($resultCode) && $resultCode == 10000){
			$result_update = json_decode(json_encode($result->alipay_trade_query_response), true);

			$order_model = D('Order');
			$res = $order_model->update_result($order_id, 'success', $result_update['total_amount'], strtotime($result_update['send_pay_date']), $result_update['trade_no']);
		}
	}
}