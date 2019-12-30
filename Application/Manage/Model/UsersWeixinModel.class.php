<?php
namespace Manage\Model;
use Think\Model;

class UsersWeixinModel extends CommonModel{

	/**
	 * 微信支付结果查询
	 * @param string $order_sn 订单号
	 * @return array
	 */
	public function get_pay_result_by_order_id($order_sn) {
		// 微信支付 是已分为单位 所以这里需要乘以100 元转换为分
		$nonce_str = rand_code();//生成随机字符串

		$wxapp_pay_config = C('WXAPP_PAY_CONFIG');
		$data['appid'] = $wxapp_pay_config['appid'];          //appid
		$data['mch_id'] = $wxapp_pay_config['mch_id'];        //商户号
		$data['out_trade_no'] = $order_sn;                  //商户订单号,不能重复
		$data['nonce_str'] = $nonce_str;                    //随机字符串
//		$data['notify_url'] = $_SERVER['HTTP_HOST'] . $wxapp_pay_config['notify_url'];//回调地址,用户接收支付后的通知,必须为能直接访问的网址,不能跟参数
//		$data['trade_type'] = 'APP';                        //支付方式
		//将参与签名的数据保存到数组
		//注意：以上几个参数是追加到$data中的，$data中应该同时包含开发文档中要求必填的剔除sign以外的所有数据
		$data['sign'] = $this->get_sign($data);           //获取签名
		$xml = to_xml($data);//数组转xml
		//		echo $xml;
		//curl 传递给微信方
		$url = 'https://api.mch.weixin.qq.com/pay/orderquery';
		//		header("Content-type:text/xml");

		$data = http_post_request($url, $xml);

		//返回结果
		if($data){
			//返回成功,将xml数据转换为数组.
			$res = from_xml($data);
			if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
				//接收微信返回的数据,传给APP
				return  [
					'result' => 'success',
					'total_amount' => number_format($res['total_fee']/100, 2),
					'pay_time' => strtotime($res['time_end']),
					'payment_id' => $res['transaction_id'],
				];

			} else{
				return false;
			}
		} else {
			return false;
		}
	}

	//这里是微信比较重要的一步了,这个方法会多次用到!生成签名
	protected function get_sign($params) {
		//将参数数组按照参数名ASCII码从小到大排序
		ksort($params);
		$newArr = [];
		foreach ($params as $key => $item) {
			//剔除参数值为空的参数
			if (!empty($item)) {
				// 整合新的参数数组
				$newArr[] = $key.'='.$item;
			}
		}
		//使用 & 符号连接参数
		$stringA = implode("&", $newArr);
		$wxapp_pay_config = C('WXAPP_PAY_CONFIG');
		//拼接key 注意：key是在商户平台API安全里自己设置的
		$stringSignTemp = $stringA."&key=".$wxapp_pay_config['wx_sign_key'];
		//将字符串进行MD5加密
		$stringSignTemp = md5($stringSignTemp);
		//将所有字符转换为大写
		$sign = strtoupper($stringSignTemp);
		return $sign;
	}
}