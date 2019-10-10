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