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