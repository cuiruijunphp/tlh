<?php

namespace Lib\Wx;

class Wxpay
{
	// 通用参数
	protected $wxConfig = [
		'appid' => 'wxfb950a8a1b9d7884',// 应用ID
		'mch_id' => 'xxxx',// 商户号
//		'mch_id' => '506de18212b58c9fbc1195505a7832a0',// 商户号
		'notify_url' => '',//异步通知地址
		// 注：key为商户平台设置的密钥key
		'wx_sign_key' => 'xxxx'
	];

	/**
	 * 微信支付 生成预支付订单
	 * @param string $subject 商品名称
	 * @param string $order_sn 订单号
	 * @param int $total_amount 商品金额
	 * @return array
	 */
	public function wx_pay($subject, $order_sn, $total_amount) {
		// 微信支付 是已分为单位 所以这里需要乘以100 元转换为分
		$total_amount = 100 * $total_amount;
		$nonce_str = rand_code();//生成随机字符串
		$data['appid'] = $this->wxConfig['appid'];          //appid
		$data['mch_id'] = $this->wxConfig['mch_id'];        //商户号
		$data['body'] = $subject;                           //商品描述
		$data['spbill_create_ip'] = get_client_ip_new();   //ip地址
		$data['total_fee'] = $total_amount;                 //金额
		$data['out_trade_no'] = $order_sn;                  //商户订单号,不能重复
		$data['nonce_str'] = $nonce_str;                    //随机字符串
		$data['notify_url'] = $this->wxConfig['notify_url'];//回调地址,用户接收支付后的通知,必须为能直接访问的网址,不能跟参数
		$data['trade_type'] = 'APP';                        //支付方式
		//将参与签名的数据保存到数组
		//注意：以上几个参数是追加到$data中的，$data中应该同时包含开发文档中要求必填的剔除sign以外的所有数据
		$data['sign'] = $this->get_sign($data);           //获取签名
		$xml = to_xml($data);                         //数组转xml
		//curl 传递给微信方
		$url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
		//header("Content-type:text/xml");
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		} else {
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
		}
		//设置header
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		//传输文件
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
		$data = curl_exec($ch);
		//返回结果
		if($data){
			curl_close($ch);
			//返回成功,将xml数据转换为数组.
			$res = from_xml($data);
			var_dump($res);
			if($res['return_code'] != 'SUCCESS'){
				return out_json(0,"微信预支付订单,签名失败！");
			} else{
				//接收微信返回的数据,传给APP
				$result = [
					'prepayid' => $res['prepay_id'],
					'appid' => $this->wxConfig['appid'],
					'partnerid' => $this->wxConfig['mch_id'],
					'package'  => 'Sign=WXPay',
					'noncestr' => $nonce_str,
					'timestamp' => time(),
				];
				//第二次生成签名
				$sign = $this->getSign($result);
				$result['sign'] = $sign;
				return out_json(1,'微信预支付订单创建成功',$result);
			}
		} else {
			$error = curl_errno($ch);
			curl_close($ch);
			return $this->outJson(0,"调用微信支付出错,curl错误码:$error");
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
		//拼接key 注意：key是在商户平台API安全里自己设置的
		$stringSignTemp = $stringA."&key=".$this->wxConfig['wx_sign_key'];
		//将字符串进行MD5加密
		$stringSignTemp = md5($stringSignTemp);
		//将所有字符转换为大写
		$sign = strtoupper($stringSignTemp);
		return $sign;
	}
}