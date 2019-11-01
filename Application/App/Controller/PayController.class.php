<?php
namespace App\Controller;
use Think\Controller;
use Lib\Wxpay;
class PayController extends BaseController {

	public function pay(){
		$wx_pay = new \Lib\Wxpay\Wxpay();
//		$wx_pay = new \Lib\Person;
//		import("Lib.Person");
//		$p = new \Person();

//		$p = new \Lib\Person();
//		$p->Say();

		$order_sn = session_create_random_id(32);
		$result = $wx_pay->wx_pay('测试', $order_sn, 0.1);
		var_dump($result);
	}

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

}