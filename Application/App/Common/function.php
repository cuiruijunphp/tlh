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