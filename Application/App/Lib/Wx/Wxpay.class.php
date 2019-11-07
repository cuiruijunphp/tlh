<?php

namespace Lib\Wx;

class Wxpay
{
	/**
	 * 微信支付 生成预支付订单
	 * @param string $subject 商品名称
	 * @param string $order_sn 订单号
	 * @param int $total_amount 商品金额
	 * @return array
	 */
	public function wxapp_pay($subject, $order_sn, $total_amount) {
		// 微信支付 是已分为单位 所以这里需要乘以100 元转换为分
		$total_amount = 100 * $total_amount;
		$nonce_str = rand_code();//生成随机字符串

		$wxapp_pay_config = C('WXAPP_PAY_CONFIG');
		$data['appid'] = $wxapp_pay_config['appid'];          //appid
		$data['mch_id'] = $wxapp_pay_config['mch_id'];        //商户号
		$data['body'] = $subject;                           //商品描述
		$data['spbill_create_ip'] = get_client_ip_new();   //ip地址
		$data['total_fee'] = $total_amount;                 //金额
		$data['out_trade_no'] = $order_sn;                  //商户订单号,不能重复
		$data['nonce_str'] = $nonce_str;                    //随机字符串
		$data['notify_url'] = $_SERVER['HTTP_HOST'] . $wxapp_pay_config['notify_url'];//回调地址,用户接收支付后的通知,必须为能直接访问的网址,不能跟参数
		$data['trade_type'] = 'APP';                        //支付方式
		//将参与签名的数据保存到数组
		//注意：以上几个参数是追加到$data中的，$data中应该同时包含开发文档中要求必填的剔除sign以外的所有数据
		$data['sign'] = $this->get_sign($data);           //获取签名
		$xml = to_xml($data);                         //数组转xml
		//curl 传递给微信方
		$url = $wxapp_pay_config['url'];
		//header("Content-type:text/xml");
		$data = http_post_request($url, $xml);

		//返回结果
		if($data){
			//返回成功,将xml数据转换为数组.
			$res = from_xml($data);
//			var_dump($res);
//			die();
			if($res['return_code'] != 'SUCCESS'){
				return out_json(0,$res['return_msg']);
			} else{
				//接收微信返回的数据,传给APP
				$result = [
					'prepayid' => $res['prepay_id'],
					'appid' => $wxapp_pay_config['appid'],
					'partnerid' => $wxapp_pay_config['mch_id'],
					'package'  => 'Sign=WXPay',
					'noncestr' => $nonce_str,
					'timestamp' => time(),
				];
				//第二次生成签名
				$sign = $this->get_sign($result);
				$result['sign'] = $sign;
				return out_json(1,'微信预支付订单创建成功',$result);
			}
		} else {
			return out_json(0,"调用微信支付出错");
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