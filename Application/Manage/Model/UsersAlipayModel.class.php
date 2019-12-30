<?php
namespace Manage\Model;
use Think\Model;

class UsersAlipayModel extends CommonModel{

	protected $aop;
	public function __construct()
	{
		parent::__construct();

		$aliConfig = C('ALIPAY_CONFIG');

		Vendor('Alipay.aop.AopClient');

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

		$this->aop = $aop;
	}

	public function get_pay_result_by_order_id($order_id){

		Vendor('Alipay.aop.request.AlipayTradeQueryRequest');

		$request = new \AlipayTradeQueryRequest();

		$bizcontent = json_encode([
			'out_trade_no' => $order_id,//此订单号为商户唯一订单号
		]);

		$request->setBizContent($bizcontent);
		$result = $this->aop->execute ( $request);

		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;

		if(!empty($resultCode) && $resultCode == 10000){
			$result_update = json_decode(json_encode($result->alipay_trade_query_response), true);

//			$order_model = D('Order');
//			$res = $order_model->update_result($order_id, 'success', $result_update['total_amount'], strtotime($result_update['send_pay_date']), $result_update['trade_no']);

			return [
				'result' => 'success',
				'total_amount' => $result_update['total_amount'],
				'pay_time' => strtotime($result_update['send_pay_date']),
				'payment_id' => $result_update['trade_no'],
			];
		}else{
			return false;
		}
	}
}