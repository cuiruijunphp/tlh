<?php
/**
 * Created by PhpStorm.
 * User: cui
 * Date: 2019/9/27
 * Time: 下午5:32
 */
/**
 * 读取 /dev/urandom 获取随机数
 * @param	$length
 * @return	mixed|string
 */
function random_from_dev($length)
{
	if (!$length)
	{
		return false;
	}

	$fp = @fopen('/dev/urandom', 'rb');

	$result = '';

	if ($fp !== FALSE)
	{
		$result .= @fread($fp, $length);

		@fclose($fp);
	}
	else
	{
		throw new Zend_Exception('Can not open /dev/urandom');
	}

	// convert from binary to string
	$result = base64_encode($result);

	// remove none url chars
	$result = strtr($result, '+/', '-_');

	return substr($result, 0, $length);
}

/**
 * 根据两点间的经纬度计算距离(单位:米)
 * @param $lng1
 * @param $lat1
 * @param $lng2
 * @param $lat2
 * @return int
 */
function getDistance($lng1, $lat1, $lng2, $lat2)
{
	//将角度转为狐度
	$radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
	$radLat2 = deg2rad($lat2);
	$radLng1 = deg2rad($lng1);
	$radLng2 = deg2rad($lng2);
	$a = $radLat1 - $radLat2;
	$b = $radLng1 - $radLng2;
	$s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
	return $s;
}

/**
 * 根据 salt 混淆密码
 *
 * @param  string
 * @param  string
 * @return string
 */
function compile_password($password)
{
	// md5 password...
	if (strlen($password) == 32)
	{
		return md5($password);
	}

	$password = md5(md5($password));

	return $password;
}

function rand_code(){
	//62个字符
	$str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$str = str_shuffle($str);
	$str = substr($str,0,32);
	return  $str;
}

// 传输给微信的参数要组装成xml格式发送,传如参数数组
function to_xml($data = [])
{
	if(!is_array($data) || count($data) <= 0)
	{
		return '数组异常';
	}
	$xml = "<xml>";
	foreach ($data as $key=>$val)
	{
		//		if (is_numeric($val)){
		//			$xml.="<".$key.">".$val."</".$key.">";
		//		}else{
		//			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
		//		}

		$xml.="<".$key.">".$val."</".$key.">";
	}
	$xml.="</xml>";
	return $xml;
}

/**
 * 函数的含义说明：CURL发送post请求    获取数据
 *
 * @access public
 * @param str          $url     发送接口地址
 * @param array/json   $data    要发送的数据
 * @param false/true   $json    false $data数组格式  true $data json格式
 * @return  返回json数据
 */
function http_post_request($url, $data){

	//	header("Content-type:text/xml");
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
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	//运行curl
	$data = curl_exec($ch);
	//关闭这个curl会话资源
	curl_close($ch);
	return $data;
}


// 将xml数据转换为数组,接收微信返回数据时用到
function from_xml($xml)
{
	if(!$xml){
		echo "xml数据异常！";
	}
	//将XML转为array
	//禁止引用外部xml实体
	libxml_disable_entity_loader(true);
	$data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
	return $data;
}