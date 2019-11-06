<?php
namespace App\Controller;
use Think\Controller;
use Lib\Wx;
class PaymentController extends CommonController {

	// APP支付成功后,会调用你填写的回调地址 .
	// 返回参数详见微信文档:https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_7&index=3
	// 微信支付回调 案例
	protected function wx_notify(){
		//接收微信返回的数据数据,返回的xml格式
		$xmlData = file_get_contents('php://input');
		//将xml格式转换为数组
//		$data = \wx\WxPay::instance()->FromXml($xmlData);
//		//用日志记录检查数据是否接受成功，验证成功一次之后，可删除。
//		$file = fopen('./wx_log.txt', 'a+');
//		fwrite($file,var_export($data,true));
//		//为了防止假数据，验证签名是否和返回的一样。
//		//记录一下，返回回来的签名，生成签名的时候，必须剔除sign字段。
//		$sign = $data['sign'];
//		unset($data['sign']);
//		if($sign == $this->getSign($data)){
//			//签名验证成功后，判断返回微信返回的
//			if ($data['result_code'] == 'SUCCESS') {
//				//验证签名通过后 根据返回的订单号做业务逻辑 $data['out_trade_no']
//				//比如修改订单状态
//				$re = Db::name('order')->where(['order_sn'=>$data['out_trade_no']])->update(['status' => 1]);
//				// 处理完成之后，告诉微信成功结果！
//				if($re){
//					// 成功返回的
//					return '<xml>
//                                      <return_code><![CDATA[SUCCESS]]></return_code>
//                                      <return_msg><![CDATA[OK]]></return_msg>
//                              </xml>';
//				} else {
//					// 失败返回
//					return '<xml>
//                              <return_code><![CDATA[FAIL]]></return_code>
//                              <return_msg><![CDATA[ERROR]]></return_msg>
//                          </xml>';
//				}
//			} else{
//				//支付失败，输出错误信息
//				$file = fopen('./wx_log.txt', 'a+');
//				fwrite($file,"错误信息：".$data['return_msg'].date("Y-m-d H:i:s"),time()."\r\n");
//			}
//		} else{
//			$file = fopen('./wx_log.txt', 'a+');
//			fwrite($file,"错误信息：签名验证失败".date("Y-m-d H:i:s"),time()."\r\n");
//		}
	}

	/**
	 * 阿里支付异步通知接口
	 * @date   2019/11/6 下午5:30
	 * @url    app/payment/alipay_notify/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function alipay_notify(){

		$params = I('post.');

		$aliConfig = C('ALIPAY_CONFIG');

		Vendor('Alipay.aop.AopClient');
		$aop = new \AopClient();

		$aop->alipayrsaPublicKey = $aliConfig['alipayrsaPublicKey'];
		$flag = $aop->rsaCheckV1($params, NULL, "RSA2");

		file_put_contents('log/1.txt', json_encode($flag), FILE_APPEND);
		file_put_contents('log/2.txt', json_encode($params), FILE_APPEND);

		die();
		//导入支付宝类
		Vendor('Alipay.aop.AopClient');
		$aop = new \AopClient;
		$aop->alipayrsaPublicKey = C('ALI_CONFIG')['alipayrsaPublicKey'];
		$flag = $aop->rsaCheckV1($_POST, NULL, "RSA2");
		if($flag){
			//商户订单号
			$out_trade_no = $_POST['out_trade_no'];
			//支付宝交易号
			$trade_no = $_POST['trade_no'];
			//交易状态
			$trade_status = $_POST['trade_status'];
			//订单的实际金额
			$total_amount = $_POST['total_amount'];
			//appid
			$appid = $_POST['app_id'];
			$seller_id = $_POST['seller_id'];
			//验证app_id是否为商户本身
			if($appid != C('ALI_CONFIG')['appId']){
				exit('fail');
			}
			//判断交易通知状态是否为TRADE_SUCCESS或TRADE_FINISH
			if($trade_status!='TRADE_FINISH' && $trade_status !='TRADE_SUCCESS'){
				exit('fail');
			}
			//验证订单的准确性
			if(!empty($out_trade_no)){
				//在这里可以通过返回的商家订单号查询出该订单的信息
				$res = xxx;
				if(!$res){
					exit('fail');
				}

				//判断total_amount是否确实为该订单的实际金额
				if($total_amount != $res){
					exit('fail');
				}
				//判断seller_id是否与商户的id相同
				if($seller_id != C('ALI_CONFIG')['seller_id']){
					exit('fail');
				}
			}
			//全部验证成功后修改订单状态
			//doAliPay方法用于进行修改订单状态的逻辑，可以放手发挥了
            $data = $this->doAliPay($out_trade_no,$trade_no);
            if($data){
				//处理业务逻辑
				echo 'success';
			} else {
				echo 'fail';
			}
        } else {
			echo 'fail';
		}
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
		$user_id = $this->user_id;

		$order_model = D('Order');
		$res = $order_model->update_result($order_id, $user_id, $result);

		if(is_string($res)){
			$this->result_return(null, 500, $res);
		}

		if($res !==false){
			$this->result_return(['result' => 1]);
		}else{
			$this->result_return(null, 500, '更新状态失败');
		}
	}
}