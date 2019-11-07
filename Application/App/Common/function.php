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
		return false;
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
function get_distance($lng1, $lat1, $lng2, $lat2)
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

//function session_create_id(){
//
//}

function session_create_random_id($desired_output_length=128, $bits_per_character=4)
{
	$bytes_needed = ceil($desired_output_length * $bits_per_character / 8);
	$random_input_bytes = random_bytes($bytes_needed);

	// The below is translated from function bin_to_readable in the PHP source (ext/session/session.c)
	static $hexconvtab = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,-';

	$out = '';

	$p = 0;
	$q = strlen($random_input_bytes);
	$w = 0;
	$have = 0;

	$mask = (1 << $bits_per_character) - 1;

	$chars_remaining = $desired_output_length;
	while ($chars_remaining--) {
		if ($have < $bits_per_character) {
			if ($p < $q) {
				$byte = ord( $random_input_bytes[$p++] );
				$w |= ($byte << $have);
				$have += 8;
			} else {
				// Should never happen. Input must be large enough.
				break;
			}
		}

		// consume $bits_per_character bits
		$out .= $hexconvtab[$w & $mask];
		$w >>= $bits_per_character;
		$have -= $bits_per_character;
	}

	return $out;
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

//这里是微信比较重要的一步了,这个方法会多次用到!生成签名
function getSign($params) {
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

/**
 * 函数的含义说明：CURL发送get请求    获取数据
 *
 * @access public
 * @param str $url 发送接口地址  https://api.douban.com/v2/movie/in_theaters?city=广州&start=0&count=10
 * @return  返回json数据
 */
function http_get_request($url){

	$curl = curl_init(); // 启动一个CURL会话
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HEADER, 0);

	if (substr($url, 0, 8) == 'https://')
	{
		//设置为FALSE 禁止 cURL 验证对等证书（peer’s certificate）
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		//设置为 1 是检查服务器SSL证书中是否存在一个公用名(common name)
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	}

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($curl);     //返回api的json对象
	//关闭URL请求
	curl_close($curl);
	return $output;    //返回json对象
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

// 获取实际ip
function get_client_ip_new($type = 0,$adv=true) {
	$type       =  $type ? 1 : 0;
	static $ip  =   NULL;
	if ($ip !== NULL) return $ip[$type];
	if($adv){
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$pos    =   array_search('unknown',$arr);
			if(false !== $pos) unset($arr[$pos]);
			$ip     =   trim($arr[0]);
		}elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip     =   $_SERVER['HTTP_CLIENT_IP'];
		}elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$ip     =   $_SERVER['REMOTE_ADDR'];
		}
	}elseif (isset($_SERVER['REMOTE_ADDR'])) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	// IP地址合法验证
	$long = sprintf("%u",ip2long($ip));
	$ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
	return $ip[$type];
}

function rand_code(){
	//62个字符
	$str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$str = str_shuffle($str);
	$str = substr($str,0,32);
	return  $str;
}

/**
 * 输出json数组
 * @param int $code
 * @param string $msg
 * @param array $data
 * @return array
 */
function out_json($code = 0, $msg = '', $data = [])
{
	return [
		"status" => $code,
		"msg" =>  $msg,
		"data" => $data
	];
}


/**
 * 写入日志,日志目录格式为 定义的目录/年-月-日.log,日志格式为 2018-06-05 14:22:53 url 访问ip 写入日志内容
 * @author cuirj
 * @param  string path 写入日志文件夹
 * @param  string content 写入日志内容
 * return  array
 */
function write_log($path, $content)
{
	if (!file_exists($path))
	{
		mkdir($path, 0777, true);
	}

	$content_default = date('Y-m-d H:i:s', time()) . ' ' . ROUTES_URI_CONTROLLER . '/' . ROUTES_URI_ACT . '/	' . get_client_ip_new(). ' ';

	file_put_contents(ROOT_PATH . $path . date('Y-m-d', time()) . '.log', $content_default . $content . PHP_EOL, FILE_APPEND);
}